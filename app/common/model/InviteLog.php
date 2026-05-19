<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邀请关系模型 - V2.8新增
 * 对应 i8j_invite_relation 表
 */
class InviteLog extends Model
{
    protected $name = 'invite_relation';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'inviter_id' => 'integer',
        'invitee_id' => 'integer',
        'reward_points' => 'integer',
        'reward_stage' => 'integer',
        'create_time' => 'integer',
    ];

    /**
     * 关联邀请人
     */
    public function inviter()
    {
        return $this->belongsTo(Member::class, 'inviter_id');
    }

    /**
     * 关联被邀请人
     */
    public function invitee()
    {
        return $this->belongsTo(Member::class, 'invitee_id');
    }

    /**
     * 生成邀请码
     */
    public static function generateCode(int $memberId): string
    {
        $code = substr(md5(uniqid((string)$memberId, true)), 0, 8);
        // 确保唯一
        while (self::where('invite_code', $code)->find()) {
            $code = substr(md5(uniqid((string)$memberId . time(), true)), 0, 8);
        }
        return $code;
    }

    /**
     * 检查是否已邀请
     */
    public static function hasInvited(int $inviterId, int $inviteeId): bool
    {
        return self::where('inviter_id', $inviterId)->where('invitee_id', $inviteeId)->find() !== null;
    }

    /**
     * 通过邀请码获取邀请关系
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('invite_code', $code)->find();
    }
}
