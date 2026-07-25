<?php
declare(strict_types=1);

namespace app\common\service\h5;

use think\facade\Db;
use think\facade\Cache;

/**
 * H5移动端配置服务
 */
class H5ConfigService
{
    protected static string $table = 'h5_config';
    protected static string $cacheTag = 'h5_config';

    /**
     * 获取配置
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = 'h5_config_' . $key;
        $value = Cache::get($cacheKey);
        if ($value !== null) {
            return $value;
        }
        $row = Db::name(self::$table)->where('config_key', $key)->where('is_active', 1)->find();
        if ($row && $row['config_value']) {
            $val = is_string($row['config_value']) ? json_decode($row['config_value'], true) : $row['config_value'];
            Cache::set($cacheKey, $val, 3600);
            return $val;
        }
        return $default;
    }

    /**
     * 设置配置
     */
    public static function set(string $key, $value, string $type = 'general'): bool
    {
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $exists = Db::name(self::$table)->where('config_key', $key)->find();
        if ($exists) {
            Db::name(self::$table)->where('config_key', $key)->update([
                'config_value' => $jsonValue,
                'config_type' => $type,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        } else {
            Db::name(self::$table)->insert([
                'config_key' => $key,
                'config_value' => $jsonValue,
                'config_type' => $type,
                'is_active' => 1,
            ]);
        }
        Cache::clear();
        return true;
    }

    /**
     * 获取所有配置
     */
    public static function getAll(): array
    {
        $rows = Db::name(self::$table)->where('is_active', 1)->select()->toArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row['config_value'] ? json_decode($row['config_value'], true) : null;
        }
        return $result;
    }

    /**
     * 获取配置列表（后台管理）
     */
    public static function getList(int $page = 1, int $limit = 20, array $filter = []): array
    {
        $query = Db::name(self::$table);
        if (!empty($filter['config_type'])) {
            $query->where('config_type', $filter['config_type']);
        }
        if (!empty($filter['keyword'])) {
            $query->whereLike('config_key', '%' . $filter['keyword'] . '%');
        }
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 获取主题配置
     */
    public static function getTheme(): array
    {
        return self::get('theme', [
            'primary_color' => '#1989fa',
            'dark_mode' => 'auto',
            'font_size' => 'medium',
        ]);
    }

    /**
     * 获取功能开关
     */
    public static function getFeatures(): array
    {
        return self::get('feature', [
            'enable_pwa' => true,
            'enable_push' => true,
            'enable_offline' => true,
            'enable_payment' => true,
        ]);
    }

    /**
     * 获取PWA配置
     */
    public static function getPwa(): array
    {
        return self::get('pwa', [
            'name' => 'AI-CMS',
            'short_name' => 'CMS',
            'display' => 'standalone',
            'theme_color' => '#1989fa',
            'background_color' => '#ffffff',
        ]);
    }

    /**
     * 获取性能配置
     */
    public static function getPerformance(): array
    {
        return self::get('performance', [
            'ssr_enabled' => true,
            'lazy_load' => true,
            'cache_ttl' => 300,
            'cdn_domain' => '',
        ]);
    }
}
