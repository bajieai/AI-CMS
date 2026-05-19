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
 * 插件评分评价模型 - V2.9.4新增
 */
class PluginRating extends Model
{
    protected $name = 'plugin_rating';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'rating' => 'integer',
    ];

    /**
     * 获取插件的平均评分（带缓存5分钟）
     */
    public static function getAverageRating(string $pluginCode): array
    {
        $cacheKey = 'plugin_rating_' . $pluginCode;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = self::where('plugin_code', $pluginCode)
            ->field('AVG(rating) as avg_rating, COUNT(*) as total_count')
            ->find();

        $data = [
            'avg_rating' => $result && $result->avg_rating ? round((float) $result->avg_rating, 1) : 0,
            'total_count' => $result ? (int) $result->total_count : 0,
        ];

        Cache::set($cacheKey, $data, 300); // 5分钟缓存
        return $data;
    }

    /**
     * 清除插件评分缓存
     */
    public static function clearRatingCache(string $pluginCode): void
    {
        Cache::delete('plugin_rating_' . $pluginCode);
    }

    /**
     * 提交或更新评分
     */
    public static function submitRating(string $pluginCode, int $userId, int $rating, string $content = ''): self
    {
        // 验证评分范围
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('评分必须在1-5之间');
        }

        // 查找已有评分
        $existing = self::where('plugin_code', $pluginCode)
            ->where('user_id', $userId)
            ->find();

        if ($existing) {
            $existing->rating = $rating;
            $existing->content = $content;
            $existing->save();
            self::clearRatingCache($pluginCode);
            return $existing;
        }

        $new = self::create([
            'plugin_code' => $pluginCode,
            'user_id' => $userId,
            'rating' => $rating,
            'content' => $content,
        ]);

        self::clearRatingCache($pluginCode);
        return $new;
    }

    /**
     * 获取插件的评分列表
     */
    public static function getRatings(string $pluginCode, int $page = 1, int $limit = 10): array
    {
        return self::where('plugin_code', $pluginCode)
            ->where('content', '<>', '')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
}
