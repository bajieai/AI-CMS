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

use app\common\service\storage\AliyunOssDriver;
use app\common\service\storage\LocalStorageDriver;
use app\common\service\storage\StorageDriverInterface;
use app\common\service\storage\TencentCosDriver;
use think\facade\Config;

/**
 * 存储服务门面 - V2.6
 * 统一封装对象存储操作，根据配置自动切换驱动
 */
class StorageService
{
    /**
     * 已实例化的驱动缓存
     */
    protected static array $drivers = [];

    /**
     * 获取存储驱动实例
     */
    public static function driver(?string $name = null): StorageDriverInterface
    {
        $name = $name ?: self::getDefaultDriver();

        if (isset(self::$drivers[$name])) {
            return self::$drivers[$name];
        }

        $config = self::getDriverConfig($name);

        $instance = match ($name) {
            'oss' => new AliyunOssDriver($config),
            'cos' => new TencentCosDriver($config),
            default => new LocalStorageDriver(),
        };

        self::$drivers[$name] = $instance;
        return $instance;
    }

    /**
     * 获取默认驱动名称
     */
    public static function getDefaultDriver(): string
    {
        return Config::get('storage.default', 'local');
    }

    /**
     * 获取指定驱动的配置
     */
    public static function getDriverConfig(string $name): array
    {
        return Config::get("storage.drivers.{$name}", []);
    }

    /**
     * 获取所有可用驱动列表
     */
    public static function getAvailableDrivers(): array
    {
        return [
            'local' => ['name' => 'local', 'display_name' => '本地存储'],
            'oss' => ['name' => 'oss', 'display_name' => '阿里云OSS'],
            'cos' => ['name' => 'cos', 'display_name' => '腾讯云COS'],
        ];
    }

    /**
     * 上传文件
     */
    public static function upload(string $localPath, string $savePath, array $options = []): array
    {
        return self::driver()->upload($localPath, $savePath, $options);
    }

    /**
     * 删除文件
     */
    public static function delete(string $path): bool
    {
        return self::driver()->delete($path);
    }

    /**
     * 获取文件URL
     */
    public static function getUrl(string $path): string
    {
        return self::driver()->getUrl($path);
    }

    /**
     * V2.7: 获取CDN加速URL（若启用CDN则替换域名）
     */
    public static function getCdnUrl(string $path): string
    {
        $url = self::getUrl($path);
        $cdnEnabled = \think\facade\Config::get('cdn.enabled', false);
        $cdnDomain = trim(\think\facade\Config::get('cdn.domain', ''));

        if (!$cdnEnabled || empty($cdnDomain)) {
            return $url;
        }

        // 替换URL中的域名部分为CDN域名
        $parsed = parse_url($url);
        if (!empty($parsed['host'])) {
            $scheme = $parsed['scheme'] ?? 'https';
            $cdnDomain = rtrim($cdnDomain, '/');
            $newUrl = $scheme . '://' . $cdnDomain;
            if (!empty($parsed['path'])) {
                $newUrl .= $parsed['path'];
            }
            if (!empty($parsed['query'])) {
                $newUrl .= '?' . $parsed['query'];
            }
            return $newUrl;
        }

        return $url;
    }

    /**
     * 检查文件是否存在
     */
    public static function exists(string $path): bool
    {
        return self::driver()->exists($path);
    }

    /**
     * 获取当前驱动的配置字段定义
     */
    public static function getCurrentConfigFields(): array
    {
        return self::driver()->getConfigFields();
    }
}
