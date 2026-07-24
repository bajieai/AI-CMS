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
 * 用户已购章节模型
 * 记录会员单独购买的章节（整本购买记录在 paid_order 表）
 */
class UserChapter extends Model
{
    protected $name = 'user_chapter';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id'  => 'integer',
        'content_id' => 'integer',
        'parent_id'  => 'integer',
        'price'      => 'float',
    ];

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 关联章节内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 检查会员是否已购买指定章节
     */
    public static function hasPurchased(int $memberId, int $contentId): bool
    {
        return self::where('member_id', $memberId)
            ->where('content_id', $contentId)
            ->count() > 0;
    }

    /**
     * 获取会员在指定父内容下的已购章节ID列表
     */
    public static function getPurchasedChapterIds(int $memberId, int $parentId): array
    {
        return self::where('member_id', $memberId)
            ->where('parent_id', $parentId)
            ->column('content_id');
    }
}
