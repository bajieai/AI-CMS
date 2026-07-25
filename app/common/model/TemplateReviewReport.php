<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板评价举报模型 — V2.9.28 M-2
 */
class TemplateReviewReport extends Model
{
    protected $name = 'template_review_report';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const STATUS_PENDING = 0;  // 待处理
    const STATUS_APPROVED = 1; // 已通过(隐藏评价)
    const STATUS_REJECTED = 2; // 已驳回

    /**
     * 关联评价
     */
    public function review()
    {
        return $this->belongsTo(TemplateReview::class, 'review_id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_APPROVED => '已处理',
            self::STATUS_REJECTED => '已驳回',
        ];
        return $map[$data['status']] ?? '未知';
    }
}
