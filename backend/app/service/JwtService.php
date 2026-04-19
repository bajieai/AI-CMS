<?php
declare(strict_types=1);

namespace app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Cache;
use think\facade\Config;

/**
 * JWT服务
 */
class JwtService
{
    /**
     * 密钥
     */
    protected string $secret;

    /**
     * Access Token过期时间(秒)
     */
    protected int $accessExpire;

    /**
     * Refresh Token过期时间(秒)
     */
    protected int $refreshExpire;

    /**
     * 算法
     */
    protected string $algorithm;

    /**
     * 黑名单前缀
     */
    protected string $blacklistPrefix;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->secret = Config::get('jwt.secret');
        $this->accessExpire = (int) Config::get('jwt.access_expire', 7200);
        $this->refreshExpire = (int) Config::get('jwt.refresh_expire', 604800);
        $this->algorithm = Config::get('jwt.algorithm', 'HS256');
        $this->blacklistPrefix = Config::get('jwt.blacklist_prefix', 'jwt:blacklist:');
    }

    /**
     * 生成令牌对
     */
    public function generateTokens(array $user): array
    {
        $now = time();
        $jti = $this->generateJti();
        
        // Access Token Payload
        $accessPayload = [
            'iss' => 'AI-CMS',
            'aud' => 'AI-CMS-API',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->accessExpire,
            'jti' => $jti,
            'sub' => $user['id'],
            'username' => $user['username'] ?? '',
            'roles' => $user['roles'] ?? [],
            'type' => 'access',
        ];
        
        // Refresh Token Payload
        $refreshJti = $this->generateJti();
        $refreshPayload = [
            'iss' => 'AI-CMS',
            'aud' => 'AI-CMS-API',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->refreshExpire,
            'jti' => $refreshJti,
            'sub' => $user['id'],
            'username' => $user['username'] ?? '',
            'type' => 'refresh',
        ];
        
        return [
            'access_token' => JWT::encode($accessPayload, $this->secret, $this->algorithm),
            'refresh_token' => JWT::encode($refreshPayload, $this->secret, $this->algorithm),
            'token_type' => 'Bearer',
            'expires_in' => $this->accessExpire,
            'expires_at' => date('Y-m-d H:i:s', $now + $this->accessExpire),
        ];
    }

    /**
     * 验证Access Token
     */
    public function verifyAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $payload = (array) $decoded;
            
            // 检查Token类型
            if (($payload['type'] ?? '') !== 'access') {
                throw new \Exception('无效的令牌类型');
            }
            
            return $payload;
        } catch (ExpiredException $e) {
            throw new \Firebase\JWT\ExpiredException('令牌已过期');
        } catch (SignatureInvalidException $e) {
            throw new \Firebase\JWT\SignatureInvalidException('令牌签名无效');
        } catch (\Exception $e) {
            throw new \UnexpectedValueException('令牌验证失败: ' . $e->getMessage());
        }
    }

    /**
     * 验证Refresh Token
     */
    public function verifyRefreshToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $payload = (array) $decoded;
            
            // 检查Token类型
            if (($payload['type'] ?? '') !== 'refresh') {
                throw new \Exception('无效的令牌类型');
            }
            
            return $payload;
        } catch (ExpiredException $e) {
            throw new \Firebase\JWT\ExpiredException('刷新令牌已过期');
        } catch (\Exception $e) {
            throw new \UnexpectedValueException('刷新令牌验证失败');
        }
    }

    /**
     * 刷新Token
     */
    public function refreshTokens(string $refreshToken): array
    {
        // 验证Refresh Token
        $payload = $this->verifyRefreshToken($refreshToken);
        
        // 检查是否在黑名单
        if ($this->isBlacklisted($payload['jti'])) {
            throw new \Exception('刷新令牌已失效');
        }
        
        // 将旧Token加入黑名单
        $this->blacklist($payload['jti'], $payload['exp']);
        
        // 生成新Token
        $user = [
            'id' => $payload['sub'],
            'username' => $payload['username'],
            'roles' => [],
        ];
        
        return $this->generateTokens($user);
    }

    /**
     * 将Token加入黑名单
     */
    public function blacklist(string $jti, int $expireTime): bool
    {
        $cacheKey = $this->blacklistPrefix . $jti;
        $ttl = max(0, $expireTime - time());
        
        // 如果已过期，不加入黑名单
        if ($ttl <= 0) {
            return true;
        }
        
        return Cache::set($cacheKey, true, $ttl);
    }

    /**
     * 检查Token是否在黑名单
     */
    public function isBlacklisted(string $jti): bool
    {
        $cacheKey = $this->blacklistPrefix . $jti;
        return Cache::has($cacheKey);
    }

    /**
     * 作废用户的所有Token
     */
    public function invalidateUser(int $userId): bool
    {
        $invalidateKey = "jwt:user:invalidated:{$userId}";
        return Cache::set($invalidateKey, true, $this->refreshExpire);
    }

    /**
     * 检查用户Token是否已作废
     */
    public function isUserInvalidated(int $userId): bool
    {
        $invalidateKey = "jwt:user:invalidated:{$userId}";
        return Cache::has($invalidateKey);
    }

    /**
     * 生成JTI
     */
    protected function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 从Token中获取用户ID
     */
    public function getUserIdFromToken(string $token): ?int
    {
        try {
            $payload = $this->verifyAccessToken($token);
            return (int) ($payload['sub'] ?? 0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 解码Token(不验证)
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode($parts[1]), true);
            return is_array($payload) ? $payload : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
