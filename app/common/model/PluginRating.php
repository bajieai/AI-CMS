<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 插件评分评价模型 — V2.9.36 Sprint PLUG-SHOP
 * 表: i8j_plugin_rating
 * 注意：V2.9.4 曾创建过同名模型（基于 plugin_code），V2.9.36 全面重写为 plugin_id 模式。
 */
class PluginRating extends Model
{
    protected $name = 'plugin_rating';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'plugin_id'  => 'integer',
        'member_id'  => 'integer',
        'rating'     => 'integer',
        'like_count' => 'integer',
        'is_verified'=> 'integer',
        'status'     => 'integer',
    ];

    /**
     * rating_images JSON 字段自动序列化
     */
    public function getRatingImagesAttr($value): array
    {
        if (empty($value)) return [];
        if (is_array($value)) return $value;
        return json_decode($value, true) ?? [];
    }

    public function setRatingImagesAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    /**
     * 获取插件平均评分（带缓存5分钟）
     */
    public static function getAverageRating(int $pluginId): array
    {
        $cacheKey = 'plugin_rating_avg_' . $pluginId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = self::where('plugin_id', $pluginId)
            ->where('status', 1)
            ->field('AVG(rating) as avg_rating, COUNT(*) as total_count')
            ->find();

        $data = [
            'avg_rating'  => $result && $result->avg_rating ? round((float) $result->avg_rating, 1) : 0,
            'total_count' => $result ? (int) $result->total_count : 0,
        ];

        Cache::set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * 清除插件评分缓存
     */
    public static function clearRatingCache(int $pluginId): void
    {
        Cache::delete('plugin_rating_avg_' . $pluginId);
        Cache::clear();
    }
}
