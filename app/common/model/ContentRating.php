<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 内容评价评分模型 - V2.9新增
 * 对应 i8j_content_rating 表
 */
class ContentRating extends Model
{
    protected $name = 'content_rating';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id'          => 'integer',
        'content_id'   => 'integer',
        'member_id'    => 'integer',
        'rating'       => 'integer',
        'has_media'    => 'integer',
        'reply_count'  => 'integer',
        'like_count'   => 'integer',
        'status'       => 'integer',
    ];

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusNameAttr(): string
    {
        $map = [0 => '待审', 1 => '通过', 2 => '拒绝'];
        return $map[$this->status] ?? '未知';
    }

    /**
     * 获取评分星星HTML
     */
    public function getStarsAttr(): string
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $html .= '<i class="bi bi-star-fill text-warning"></i>';
            } else {
                $html .= '<i class="bi bi-star text-muted"></i>';
            }
        }
        return $html;
    }

    /**
     * 检查会员是否已评价该内容
     */
    public static function hasRated(int $memberId, int $contentId): bool
    {
        return self::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->where('status', 1)
            ->find() !== null;
    }

    /**
     * 获取内容平均评分
     */
    public static function getAverageRating(int $contentId): float
    {
        return (float) self::where('content_id', $contentId)
            ->where('status', 1)
            ->avg('rating') ?: 0.0;
    }

    /**
     * 获取内容评价数
     */
    public static function getRatingCount(int $contentId): int
    {
        return self::where('content_id', $contentId)
            ->where('status', 1)
            ->count();
    }
}
