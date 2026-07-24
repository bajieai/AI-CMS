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

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'user_id', 'id')->field('id,nickname,avatar,mobile');
    }

    /**
     * 关联商品
     */
    public function product()
    {
        return $this->belongsTo(PointsProduct::class, 'product_id', 'id')->field('id,title,image,type');
    }
}
