<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 积分商品模型 - V2.6
 */
class PointsProduct extends Model
{
    protected $name = 'points_product';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'points' => 'integer',
        'stock' => 'integer',
        'sort' => 'integer',
        'is_enabled' => 'integer',
    ];

    protected function getConfigAttr($value): array
    {
        return json_decode($value ?: '{}', true);
    }

    protected function setConfigAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
