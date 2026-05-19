<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 许可证模型 - V2.9.4新增
 */
class License extends Model
{
    protected $name = 'licenses';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'valid_from' => 'integer',
        'valid_until' => 'integer',
        'last_verified' => 'integer',
    ];

    /**
     * 生成许可证编码
     */
    public static function generateLicenseCode(): string
    {
        return 'LIC-' . strtoupper(bin2hex(random_bytes(8))) . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * 创建许可证
     */
    public static function createLicense(array $data): self
    {
        $data['license_code'] = $data['license_code'] ?? self::generateLicenseCode();
        $data['status'] = $data['status'] ?? 'active';
        $data['valid_from'] = $data['valid_from'] ?? time();

        return self::create($data);
    }

    /**
     * 本地验证许可证
     */
    public static function verifyLocal(string $productType, string $productCode, string $domain = ''): array
    {
        $cacheKey = "license_{$productType}_{$productCode}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $license = self::where('product_type', $productType)
            ->where('product_code', $productCode)
            ->where('status', 'active')
            ->find();

        if (!$license) {
            $result = ['valid' => false, 'reason' => '许可证不存在'];
            Cache::set($cacheKey, $result, 86400); // 缓存24h
            return $result;
        }

        // 检查有效期
        if ($license->valid_until > 0 && $license->valid_until < time()) {
            $result = ['valid' => false, 'reason' => '许可证已过期'];
            Cache::set($cacheKey, $result, 3600);
            return $result;
        }

        // 检查域名绑定
        if (!empty($license->bind_domain) && !empty($domain)) {
            if ($license->bind_domain !== $domain) {
                $result = ['valid' => false, 'reason' => '域名不匹配'];
                Cache::set($cacheKey, $result, 3600);
                return $result;
            }
        }

        // 更新验证时间
        $license->last_verified = time();
        $license->save();

        $result = [
            'valid' => true,
            'license_code' => $license->license_code,
            'license_type' => $license->license_type,
            'valid_until' => $license->valid_until,
            'product_type' => $license->product_type,
            'product_code' => $license->product_code,
        ];
        Cache::set($cacheKey, $result, 86400); // 缓存24h
        return $result;
    }

    /**
     * 吊销许可证
     */
    public function revoke(): bool
    {
        $this->status = 'revoked';
        $result = $this->save();
        Cache::delete("license_{$this->product_type}_{$this->product_code}");
        return $result;
    }

    /**
     * 激活许可证
     */
    public function activate(): bool
    {
        $this->status = 'active';
        $result = $this->save();
        Cache::delete("license_{$this->product_type}_{$this->product_code}");
        return $result;
    }
}
