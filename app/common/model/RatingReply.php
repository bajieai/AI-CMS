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
 * 评价回复模型 - V2.9.1 M15b
 * 对应表: i8j_rating_reply
 */
class RatingReply extends Model
{
    // 表名（不含前缀）
    protected $name = 'rating_reply';

    // 自动时间戳（使用int时间戳）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'rating_id' => 'integer',
        'user_id'   => 'integer',
        'member_id' => 'integer',
        'create_time' => 'integer',
    ];

    /**
     * 关联评价
     */
    public function rating()
    {
        return $this->belongsTo(ContentRating::class, 'rating_id');
    }

    /**
     * 关联管理员
     */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 获取回复者名称
     */
    public function getReplyerNameAttr($value, $data): string
    {
        if (!empty($data['user_id'])) {
            $user = User::find($data['user_id']);
            return $user ? ($user->nickname ?: $user->username) : '管理员';
        }
        if (!empty($data['member_id'])) {
            $member = Member::find($data['member_id']);
            return $member ? ($member->nickname ?: $member->username) : '会员';
        }
        return '匿名';
    }

    /**
     * 获取某评价的所有回复
     */
    public static function getByRatingId(int $ratingId): array
    {
        return self::where('rating_id', $ratingId)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();
    }
}
