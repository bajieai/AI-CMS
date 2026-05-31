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
 * 模板订单模型 - V2.9.12新增
 * 注：实际支付走统一Order模型，此模型用于模板业务层面的订单记录扩展
 */
class TemplateOrder extends Model
{
    protected $name = 'template_order';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'store_id' => 'integer',
        'member_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'pay_time' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;
    const STATUS_PAID = 1;
    const STATUS_REFUNDED = 2;
    const STATUS_CLOSED = 3;

    /**
     * 关联商店模板
     */
    public function store()
    {
        return $this->belongsTo(TemplateStore::class, 'store_id');
    }

    /**
     * 生成订单编号
     */
    public static function generateOrderNo(): string
    {
        return 'TPL' . date('YmdHis') . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
