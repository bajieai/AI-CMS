<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 插件订单模型 — V2.9.36 Sprint PLUG-SHOP
 * 表: i8j_plugin_order
 */
class PluginOrder extends Model
{
    protected $name = 'plugin_order';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /** 订单状态常量 */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_CLOSED    = 'closed';

    protected $type = [
        'plugin_id' => 'integer',
        'member_id' => 'integer',
        'price'     => 'float',
    ];

    /**
     * order_data JSON 字段自动序列化
     */
    public function getOrderDataAttr($value): array
    {
        if (empty($value)) return [];
        if (is_array($value)) return $value;
        return json_decode($value, true) ?? [];
    }

    public function setOrderDataAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    /**
     * 关联插件
     */
    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'plugin_id', 'id');
    }
}
