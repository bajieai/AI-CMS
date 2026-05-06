<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 积分兑换记录模型 - V2.6
 */
class PointsExchange extends Model
{
    protected $name = 'points_exchange';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'points' => 'integer',
        'status' => 'integer',
    ];

    protected function getDeliveryInfoAttr($value): array
    {
        return json_decode($value ?: '{}', true);
    }

    protected function setDeliveryInfoAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
