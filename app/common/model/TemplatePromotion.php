<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint T3: 模板推广活动模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板推广活动模型 - V2.9.31 T3-1
 */
class TemplatePromotion extends Model
{
    protected $name = 'template_promotion';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'template_id' => 'integer',
        'promo_type' => 'integer',
        'discount_rate' => 'float',
        'discount_amount' => 'float',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'min_amount' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'status' => 'integer',
    ];

    // 推广类型常量
    const TYPE_DISCOUNT = 0;    // 限时折扣
    const TYPE_NEWUSER = 1;     // 新用户专享
    const TYPE_FULLREDUCE = 2;  // 满减
    const TYPE_BUNDLE = 3;      // 组合优惠

    /**
     * 获取推广类型文本
     */
    public function getPromoTypeTextAttr($value, $data): string
    {
        $map = [
            self::TYPE_DISCOUNT => '限时折扣',
            self::TYPE_NEWUSER => '新用户专享',
            self::TYPE_FULLREDUCE => '满减',
            self::TYPE_BUNDLE => '组合优惠',
        ];
        return $map[$data['promo_type']] ?? '未知';
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }

    /**
     * 查询作用域 — 有效活动（时间范围内+启用）
     */
    public function scopeActive($query)
    {
        $now = time();
        return $query->where('status', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now);
    }

    /**
     * 查询作用域 — 指定模板
     */
    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }
}
