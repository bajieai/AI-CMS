<?php

declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Db;
use think\facade\Cache;

/**
 * PLUG-SHOP-4: 插件评价评分服务 — V2.9.36
 */
class PluginRatingService
{
    private const CACHE_TAG = 'plugin_store';

    /**
     * 提交评分
     */
    public function submitRating(int $pluginId, int $memberId, int $rating, string $title = '', string $content = ''): array
    {
        if ($rating < 1 || $rating > 5) {
            return ['code' => 1, 'msg' => '评分必须在1-5之间'];
        }

        $plugin = Db::name('plugin')->find($pluginId);
        if (!$plugin) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        // 检查是否已评价（唯一约束 plugin_id + member_id）
        $existing = Db::name('plugin_rating')
            ->where('plugin_id', $pluginId)
            ->where('member_id', $memberId)
            ->find();

        if ($existing) {
            // 更新评价
            Db::name('plugin_rating')->where('id', $existing['id'])->update([
                'rating'         => $rating,
                'rating_title'   => $title,
                'rating_content' => $content,
                'update_time'    => date('Y-m-d H:i:s'),
            ]);
        } else {
            // 新增评价
            Db::name('plugin_rating')->insert([
                'plugin_id'      => $pluginId,
                'member_id'      => $memberId,
                'member_name'    => (string) $memberId,
                'rating'         => $rating,
                'rating_title'   => $title,
                'rating_content' => $content,
                'rating_images'  => '[]',
                'rating_tags'    => '',
                'like_count'     => 0,
                'is_verified'    => 0,
                'status'         => 1,
                'create_time'    => date('Y-m-d H:i:s'),
                'update_time'    => date('Y-m-d H:i:s'),
            ]);
        }

        // 更新插件表评分
        $this->updatePluginRating($pluginId);

        return ['code' => 0, 'msg' => '评价提交成功'];
    }

    /**
     * 评分列表
     */
    public function getRatingList(int $pluginId, int $page = 1, string $sort = 'latest'): array
    {
        $query = Db::name('plugin_rating')
            ->where('plugin_id', $pluginId)
            ->where('status', 1);

        $sortMap = [
            'latest'  => ['id', 'desc'],
            'oldest'  => ['id', 'asc'],
            'highest' => ['rating', 'desc'],
            'lowest'  => ['rating', 'asc'],
            'likes'   => ['like_count', 'desc'],
        ];
        [$sortField, $sortOrder] = $sortMap[$sort] ?? $sortMap['latest'];
        $query->order($sortField, $sortOrder);

        $total = $query->count();
        $list = $query->page($page, 10)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 开发者回复
     */
    public function replyRating(int $ratingId, string $reply): array
    {
        $rating = Db::name('plugin_rating')->find($ratingId);
        if (!$rating) {
            return ['code' => 1, 'msg' => '评价不存在'];
        }

        Db::name('plugin_rating')->where('id', $ratingId)->update([
            'developer_reply' => $reply,
            'reply_time'      => date('Y-m-d H:i:s'),
            'update_time'     => date('Y-m-d H:i:s'),
        ]);

        return ['code' => 0, 'msg' => '回复成功'];
    }

    /**
     * 点赞
     */
    public function likeRating(int $ratingId): array
    {
        $rating = Db::name('plugin_rating')->find($ratingId);
        if (!$rating) {
            return ['code' => 1, 'msg' => '评价不存在'];
        }

        Db::name('plugin_rating')->where('id', $ratingId)->inc('like_count')->update();

        return ['code' => 0, 'msg' => '点赞成功', 'data' => ['like_count' => $rating['like_count'] + 1]];
    }

    /**
     * 更新插件表评分汇总
     */
    public function updatePluginRating(int $pluginId): void
    {
        $row = Db::name('plugin_rating')
            ->where('plugin_id', $pluginId)
            ->where('status', 1)
            ->field('AVG(rating) as avg_rating, COUNT(*) as total')
            ->find();

        Db::name('plugin')->where('id', $pluginId)->update([
            'rating'       => $row && $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : 0,
            'rating_count' => $row ? (int) $row['total'] : 0,
        ]);

        // 清除缓存
        Cache::clear();
    }

    /**
     * 评分统计
     */
    public function getRatingStats(int $pluginId): array
    {
        $row = Db::name('plugin_rating')
            ->where('plugin_id', $pluginId)
            ->where('status', 1)
            ->field('AVG(rating) as avg_rating, COUNT(*) as total')
            ->find();

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = Db::name('plugin_rating')
                ->where('plugin_id', $pluginId)
                ->where('status', 1)
                ->where('rating', $i)
                ->count();
        }

        return [
            'avg'          => $row && $row['avg_rating'] ? round((float) $row['avg_rating'], 1) : 0,
            'total'        => $row ? (int) $row['total'] : 0,
            'distribution' => $distribution,
        ];
    }
}
