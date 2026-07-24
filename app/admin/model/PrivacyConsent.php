<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: 隐私同意记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\model;

use think\Model;

/**
 * 隐私同意记录模型 - V2.9.39 COMPLIANCE-1
 * 表名: i8j_privacy_consent
 */
class PrivacyConsent extends Model
{
    protected $name = 'privacy_consent';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id'      => 'integer',
        'policy_id'    => 'integer',
        'consent_given' => 'integer',
        'create_time'  => 'integer',
        'update_time'  => 'integer',
    ];

    // 同意状态
    const STATUS_GRANTED  = 1;
    const STATUS_REVOKED  = 0;

    // 同意来源
    const SOURCE_COOKIE_BANNER = 'cookie_banner';
    const SOURCE_REGISTRATION  = 'registration';
    const SOURCE_EXPLICIT      = 'explicit';
    const SOURCE_IMPLICIT      = 'implicit';

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(\app\common\model\Member::class, 'user_id', 'id');
    }

    /**
     * 查询作用域 — 按用户
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询作用域 — 已同意
     */
    public function scopeGranted($query)
    {
        return $query->where('consent_given', self::STATUS_GRANTED);
    }
}
