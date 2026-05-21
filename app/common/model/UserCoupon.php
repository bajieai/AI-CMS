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
 * 用户优惠券模型 - V2.9新增
 * 对应 i8j_user_coupon 表
 */
class UserCoupon extends Model
{
    protected $name = 'user_coupon';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'id'          => 'integer',
        'member_id'   => 'integer',
        'template_id' => 'integer',
        'status'      => 'integer',
        'used_at'     => 'integer',
        'used_order_id' => 'integer',
        'expire_at'   => 'integer',
    ];

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 关联优惠券模板
     */
    public function template()
    {
        return $this->belongsTo(CouponTemplate::class, 'template_id');
    }

    /**
     * 获取状态名称
     */
    public function getStatusNameAttr(): string
    {
        $map = [0 => '未使用', 1 => '已使用', 2 => '已过期', 3 => '已作废', 4 => '已退还'];
        return $map[$this->status] ?? '未知';
    }

    /**
     * 检查是否可用
     */
    public function isValid(): bool
    {
        if ($this->status !== 0) return false;
        if ($this->expire_at > 0 && $this->expire_at < time()) {
            // 自动标记过期
            $this->status = 2;
            $this->save();
            return false;
        }
        return true;
    }
}
