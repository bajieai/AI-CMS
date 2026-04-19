<?php
declare(strict_types=1);

namespace app\middleware;

use think\App;
use think\Response;
use app\service\JwtService;
use app\exception\BusinessException;

/**
 * JWT认证中间件
 */
class AuthMiddleware
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * JWT服务
     */
    protected JwtService $jwtService;

    /**
     * 不需要认证的路由(相对路径)
     */
    protected array $except = [
        'api/auth/login',
        'api/status-codes',
    ];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->jwtService = new JwtService();
    }

    /**
     * 中间件处理
     */
    public function handle($request, \Closure $next): Response
    {
        // 检查是否跳过认证
        if ($this->shouldSkip()) {
            return $next($request);
        }
        
        // 获取Token
        $token = $this->getToken();
        
        if (empty($token)) {
            throw new BusinessException('未提供访问令牌', 401, [], 401);
        }
        
        // 验证Token
        try {
            $payload = $this->jwtService->verifyAccessToken($token);
            
            // 检查Token是否在黑名单
            if ($this->jwtService->isBlacklisted($payload['jti'] ?? '')) {
                throw new BusinessException('令牌已失效，请重新登录', 401, [], 401);
            }
            
            // 将用户ID注入到request
            $this->app->request->user_id = (int) ($payload['sub'] ?? 0);
            $this->app->request->user_data = [
                'id' => (int) ($payload['sub'] ?? 0),
                'username' => $payload['username'] ?? '',
                'roles' => $payload['roles'] ?? [],
            ];
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new BusinessException('访问令牌已过期', 401, [], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new BusinessException('访问令牌无效', 401, [], 401);
        } catch (\UnexpectedValueException $e) {
            throw new BusinessException('访问令牌验证失败', 401, [], 401);
        }
        
        return $next($request);
    }

    /**
     * 检查是否跳过认证
     */
    protected function shouldSkip(): bool
    {
        $path = $this->app->request->path();
        
        foreach ($this->except as $route) {
            if (stripos($path, $route) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 获取Token
     */
    protected function getToken(): ?string
    {
        $authHeader = $this->app->request->header('Authorization', '');
        
        // Bearer Token格式
        if (str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        
        // 也支持query参数
        $token = $this->app->request->param('access_token', '');
        if (!empty($token)) {
            return $token;
        }
        
        return null;
    }

    /**
     * 获取当前用户ID
     */
    public static function getCurrentUserId(): ?int
    {
        return app()->request->user_id ?? null;
    }

    /**
     * 获取当前用户数据
     */
    public static function getCurrentUserData(): ?array
    {
        return app()->request->user_data ?? null;
    }
}
