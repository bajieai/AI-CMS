<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 插件分成结算模型 — V2.9.36 Sprint PLUG-SHOP
 * 表: i8j_plugin_payout
 */
class PluginPayout extends Model
{
    protected $name = 'plugin_payout';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /** 结算状态常量 */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_SETTLED   = 'settled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $type = [
        'developer_id'     => 'integer',
        'order_id'         => 'integer',
        'plugin_id'        => 'integer',
        'order_amount'     => 'float',
        'platform_ratio'   => 'float',
        'developer_ratio'  => 'float',
        'platform_amount'  => 'float',
        'developer_amount' => 'float',
    ];
}
