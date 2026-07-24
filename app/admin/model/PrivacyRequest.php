<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: 隐私请求模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\model;

use think\Model;

/**
 * 隐私请求模型 - V2.9.39 COMPLIANCE-1
 * 表名: i8j_privacy_request
 * GDPR 数据主体权利请求：访问/更正/删除/限制处理/数据可携带/反对处理
 */
class PrivacyRequest extends Model
{
    protected $name = 'privacy_request';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id'     => 'integer',
        'status'      => 'integer',
        'handler_id'  => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    // 请求类型 (GDPR 数据主体权利)
    const TYPE_ACCESS         = 'access';          // 访问权
    const TYPE_RECTIFICATION  = 'rectification';   // 更正权
    const TYPE_ERASURE        = 'erasure';         // 删除权(被遗忘权)
    const TYPE_RESTRICTION    = 'restriction';     // 限制处理权
    const TYPE_PORTABILITY    = 'portability';     // 数据可携带权
    const TYPE_OBJECTION      = 'objection';       // 反对处理权

    // 状态
    const STATUS_PENDING     = 0;  // 待处理
    const STATUS_PROCESSING  = 1;  // 处理中
    const STATUS_COMPLETED   = 2;  // 已完成
    const STATUS_REJECTED    = 3;  // 已拒绝
    const STATUS_CANCELLED   = 4;  // 已取消

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(\app\common\model\Member::class, 'user_id', 'id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING    => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED  => '已完成',
            self::STATUS_REJECTED   => '已拒绝',
            self::STATUS_CANCELLED  => '已取消',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            self::TYPE_ACCESS        => '数据访问',
            self::TYPE_RECTIFICATION => '数据更正',
            self::TYPE_ERASURE       => '数据删除',
            self::TYPE_RESTRICTION   => '限制处理',
            self::TYPE_PORTABILITY   => '数据可携带',
            self::TYPE_OBJECTION     => '反对处理',
        ];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * 查询作用域 — 按状态
     */
    public function scopeByStatus($query, int $status)
    {
        return $query->where('status', $status);
    }
}
