<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 主题评分收藏模型 - V3.1 Sprint 16
 *
 * 功能：1-5星评分 + 收藏 + 评价内容
 * 唯一索引：uk_user_theme (user_id, theme_id) 防重复
 */
class ThemeRate extends Model
{
    protected $name = 'theme_rate';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id'     => 'integer',
        'theme_id'    => 'integer',
        'rating'      => 'integer',
        'is_favorite' => 'integer',
    ];

    /**
     * 提交或更新评分
     */
    public static function rate(int $userId, int $themeId, int $rating, string $comment = ''): array
    {
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('评分必须在1-5星之间');
        }

        $exists = self::where('user_id', $userId)->where('theme_id', $themeId)->find();

        if ($exists) {
            $exists->rating = $rating;
            if ($comment !== '') {
                $exists->comment = $comment;
            }
            $exists->save();
        } else {
            self::create([
                'user_id'  => $userId,
                'theme_id' => $themeId,
                'rating'   => $rating,
                'comment'  => $comment,
            ]);
        }

        // 同步平均评分到theme_info
        self::syncAvgRating($themeId);

        return ['success' => true, 'rating' => $rating];
    }

    /**
     * 切换收藏状态
     */
    public static function toggleFavorite(int $userId, int $themeId): array
    {
        $record = self::where('user_id', $userId)->where('theme_id', $themeId)->find();

        if ($record) {
            $newState = $record->is_favorite ? 0 : 1;
            $record->is_favorite = $newState;
            $record->save();
            return ['success' => true, 'is_favorite' => $newState];
        }

        // 没有评分记录时先创建（rating=0表示未评分仅收藏）
        self::create([
            'user_id'     => $userId,
            'theme_id'    => $themeId,
            'rating'      => 0,
            'is_favorite' => 1,
        ]);

        return ['success' => true, 'is_favorite' => 1];
    }

    /**
     * 获取用户对主题的评分和收藏状态
     */
    public static function getUserRate(int $userId, int $themeId): ?array
    {
        $record = self::where('user_id', $userId)->where('theme_id', $themeId)->find();
        if (!$record) {
            return null;
        }
        return [
            'rating'      => (int) $record->rating,
            'is_favorite' => (int) $record->is_favorite,
            'comment'     => $record->comment,
        ];
    }

    /**
     * 获取主题的评分统计
     */
    public static function getThemeStats(int $themeId): array
    {
        $total = self::where('theme_id', $themeId)->where('rating', '>', 0)->count();
        $avg = $total > 0
            ? (float) self::where('theme_id', $themeId)->where('rating', '>', 0)->avg('rating')
            : 0;
        $favorites = self::where('theme_id', $themeId)->where('is_favorite', 1)->count();

        // 各星级分布
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = self::where('theme_id', $themeId)->where('rating', $i)->count();
        }

        return [
            'total'         => $total,
            'avg'           => round($avg, 1),
            'favorites'     => $favorites,
            'distribution'  => $distribution,
        ];
    }

    /**
     * 同步平均评分到theme_info表
     */
    protected static function syncAvgRating(int $themeId): void
    {
        $stats = self::getThemeStats($themeId);
        ThemeInfo::where('id', $themeId)->update([
            'avg_rating' => $stats['avg'],
        ]);
    }
}
