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

namespace app\common\service;

use app\common\model\License;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.4 许可证管理服务
 */
class LicenseService
{
    /**
     * 验证许可证（本地+远程双验证）
     */
    public static function verify(string $productType, string $productCode, string $domain = ''): array
    {
        // 1. 本地验证
        $localResult = License::verifyLocal($productType, $productCode, $domain);

        if ($localResult['valid']) {
            return ['success' => true, 'valid' => true, 'source' => 'local', 'data' => $localResult];
        }

        // 2. 远程验证（如果启用）
        $remoteEnabled = Config::get('license_verify_enabled', 0);
        if ($remoteEnabled) {
            $remoteResult = self::verifyRemote($productType, $productCode, $domain);
            if ($remoteResult['valid']) {
                // 远程验证通过，更新本地缓存
                return ['success' => true, 'valid' => true, 'source' => 'remote', 'data' => $remoteResult];
            }
        }

        // 3. 离线降级：检查24h缓存
        $cacheKey = "license_offline_{$productType}_{$productCode}";
        $offlineCache = Cache::get($cacheKey);
        if ($offlineCache && $offlineCache['valid']) {
            Log::info("[License] 离线降级使用缓存: {$productType}/{$productCode}");
            return ['success' => true, 'valid' => true, 'source' => 'offline_cache', 'data' => $offlineCache];
        }

        return ['success' => false, 'valid' => false, 'reason' => $localResult['reason'] ?? '许可证无效'];
    }

    /**
     * 远程验证许可证
     */
    protected static function verifyRemote(string $productType, string $productCode, string $domain = ''): array
    {
        $apiUrl = Config::get('license_api_url', '');
        if (empty($apiUrl)) {
            return ['valid' => false, 'reason' => '未配置许可证验证API'];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post($apiUrl . '/verify', [
                'json' => [
                    'product_type' => $productType,
                    'product_code' => $productCode,
                    'domain' => $domain,
                    'cms_version' => config('app.version', '2.9.4'),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            if (!is_array($body) || empty($body['valid'])) {
                return ['valid' => false, 'reason' => $body['reason'] ?? '远程验证失败'];
            }

            // 缓存验证结果（24h离线降级用）
            $cacheKey = "license_offline_{$productType}_{$productCode}";
            Cache::set($cacheKey, $body, 86400);

            return $body;
        } catch (\Throwable $e) {
            Log::warning("[License] 远程验证失败: " . $e->getMessage());
            return ['valid' => false, 'reason' => '远程验证不可用'];
        }
    }

    /**
     * 发放许可证
     */
    public static function issue(string $productType, string $productCode, int $userId, string $licenseType = 'standard', string $domain = '', int $validUntil = 0): License
    {
        return License::createLicense([
            'product_type' => $productType,
            'product_code' => $productCode,
            'user_id' => $userId,
            'license_type' => $licenseType,
            'bind_domain' => $domain,
            'valid_from' => time(),
            'valid_until' => $validUntil,
        ]);
    }

    /**
     * 获取许可证列表
     */
    public static function getList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = License::order('id', 'desc');

        if (!empty($filters['product_type'])) {
            $query->where('product_type', $filters['product_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['product_code'])) {
            $query->where('product_code', $filters['product_code']);
        }

        return $query->page($page, $limit)->select()->toArray();
    }
}
