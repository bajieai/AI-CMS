<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Cache;
use think\facade\Db;

/**
 * V2.9.35 SEC-3: 统一加密服务
 * AES-256-CBC加密/解密 + 密钥轮换 + 兼容V2.9.31 AES-256-GCM
 *
 * 兼容策略：
 * - V2.9.35新增数据使用AES-256-CBC
 * - V2.9.31已加密数据保留AES-256-GCM格式（密文头部GCM:前缀区分）
 * - 解密时自动检测算法标识
 */
class EncryptionService
{
    /**
     * 密文算法前缀标识
     */
    public const PREFIX_GCM = 'GCM:';   // V2.9.31 AES-256-GCM
    public const PREFIX_CBC = 'CBC:';   // V2.9.35 AES-256-CBC

    /**
     * 密钥缓存
     */
    protected static array $keyCache = [];

    /**
     * 加密数据
     */
    public function encrypt(string $plaintext, ?string $keyId = null): string
    {
        $key = $this->getKey($keyId);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('加密失败: ' . openssl_error_string());
        }

        // 格式: CBC:base64(iv+encrypted)
        return self::PREFIX_CBC . base64_encode($iv . $encrypted);
    }

    /**
     * 解密数据（自动检测算法）
     */
    public function decrypt(string $ciphertext, ?string $keyId = null): string
    {
        if ($ciphertext === '') {
            return '';
        }

        // 检测算法前缀
        if (str_starts_with($ciphertext, self::PREFIX_CBC)) {
            return $this->decryptCBC(substr($ciphertext, strlen(self::PREFIX_CBC)), $keyId);
        }

        if (str_starts_with($ciphertext, self::PREFIX_GCM)) {
            return $this->decryptGCM(substr($ciphertext, strlen(self::PREFIX_GCM)), $keyId);
        }

        // 无前缀：尝试CBC（默认）
        return $this->decryptCBC($ciphertext, $keyId);
    }

    /**
     * AES-256-CBC解密
     */
    protected function decryptCBC(string $data, ?string $keyId): string
    {
        $key = $this->getKey($keyId);
        $raw = base64_decode($data, true);
        if ($raw === false || strlen($raw) < 16) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * AES-256-GCM解密（兼容V2.9.31）
     */
    protected function decryptGCM(string $data, ?string $keyId): string
    {
        $key = $this->getKey($keyId);
        $raw = base64_decode($data, true);
        if ($raw === false) {
            return '';
        }

        // GCM格式: tag(16) + iv(12) + encrypted
        if (strlen($raw) < 28) {
            return '';
        }

        $tag = substr($raw, 0, 16);
        $iv = substr($raw, 16, 12);
        $encrypted = substr($raw, 28);

        $decrypted = openssl_decrypt($encrypted, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * 获取加密密钥
     */
    protected function getKey(?string $keyId = null): string
    {
        $keyId = $keyId ?? 'default';

        if (isset(self::$keyCache[$keyId])) {
            return self::$keyCache[$keyId];
        }

        $config = Config::get('security.encryption', []);
        $cacheTtl = $config['key_cache_ttl'] ?? 3600;
        $cacheKey = 'enc_key_' . $keyId;

        $key = Cache::remember($cacheKey, function () use ($keyId, $config) {
            // 从数据库获取密钥
            $keyRecord = Db::name('encryption_key')
                ->where('key_id', $keyId)
                ->where('status', 1)
                ->find();

            if ($keyRecord) {
                // 用系统主密钥解密业务密钥
                $masterKey = $config['master_key'] ?? '';
                if (empty($masterKey)) {
                    throw new \RuntimeException('系统主密钥未配置: AI_CMS_ENC_KEY');
                }
                return $this->decryptWithMaster($keyRecord['encrypted_value'], $masterKey);
            }

            // 密钥不存在，自动生成并存储
            $newKey = openssl_random_pseudo_bytes(32);
            $this->storeKey($keyId, $newKey);
            return $newKey;
        }, $cacheTtl);

        self::$keyCache[$keyId] = $key;
        return $key;
    }

    /**
     * 用系统主密钥加密业务密钥
     */
    protected function encryptWithMaster(string $plaintext, string $masterKey): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', hash('sha256', $masterKey, true), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * 用系统主密钥解密业务密钥
     */
    protected function decryptWithMaster(string $ciphertext, string $masterKey): string
    {
        $raw = base64_decode($ciphertext, true);
        if ($raw === false || strlen($raw) < 16) {
            throw new \RuntimeException('密钥解密失败');
        }
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', hash('sha256', $masterKey, true), OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new \RuntimeException('密钥解密失败: ' . openssl_error_string());
        }
        return $decrypted;
    }

    /**
     * 存储密钥到数据库
     */
    protected function storeKey(string $keyId, string $key): void
    {
        $config = Config::get('security.encryption', []);
        $masterKey = $config['master_key'] ?? '';
        if (empty($masterKey)) {
            throw new \RuntimeException('系统主密钥未配置');
        }

        $encrypted = $this->encryptWithMaster($key, $masterKey);

        Db::name('encryption_key')->insert([
            'key_id'          => $keyId,
            'key_name'        => 'Auto-generated key: ' . $keyId,
            'encrypted_value' => $encrypted,
            'algorithm'       => 'AES-256-CBC',
            'version'         => 1,
            'status'          => 1,
            'created_by'      => (int) session('user_id'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 轮换密钥
     */
    public function rotateKey(string $keyId): bool
    {
        Db::startTrans();
        try {
            // 旧密钥标记为已轮换
            Db::name('encryption_key')
                ->where('key_id', $keyId)
                ->where('status', 1)
                ->update([
                    'status'     => 2,
                    'rotated_at' => date('Y-m-d H:i:s'),
                ]);

            // 生成新密钥
            $newKey = openssl_random_pseudo_bytes(32);
            $config = Config::get('security.encryption', []);
            $masterKey = $config['master_key'] ?? '';
            $encrypted = $this->encryptWithMaster($newKey, $masterKey);

            // 获取最大版本号
            $maxVersion = (int) Db::name('encryption_key')
                ->where('key_id', $keyId)
                ->max('version');

            Db::name('encryption_key')->insert([
                'key_id'          => $keyId,
                'key_name'        => 'Rotated key: ' . $keyId . ' v' . ($maxVersion + 1),
                'encrypted_value' => $encrypted,
                'algorithm'       => 'AES-256-CBC',
                'version'         => $maxVersion + 1,
                'status'          => 1,
                'created_by'      => (int) session('user_id'),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // 清除密钥缓存
            Cache::delete('enc_key_' . $keyId);
            unset(self::$keyCache[$keyId]);

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取密钥列表
     */
    public function getKeyList(): array
    {
        return Db::name('encryption_key')
            ->field('id, key_id, key_name, algorithm, version, status, created_at, rotated_at')
            ->order('key_id, version', 'desc')
            ->select()
            ->toArray();
    }
}
