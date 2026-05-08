<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member as MemberModel;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * 会员服务
 */
class MemberService
{
    /**
     * 会员注册
     */
    public function register(array $data): array
    {
        if (MemberModel::where('username', $data['username'])->find()) {
            return ['success' => false, 'msg' => '用户名已存在'];
        }
        if (MemberModel::where('email', $data['email'])->find()) {
            return ['success' => false, 'msg' => '邮箱已被注册'];
        }

        $needAudit = (int) ConfigService::get('member_register_audit', 0);

        $member = new MemberModel;
        $member->save([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'nickname' => $data['nickname'] ?? $data['username'],
            'status'   => $needAudit ? 2 : 1,
        ]);

        // V2.4: 赋予默认等级
        $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->find();
        if ($defaultLevel) {
            $member->level_id = $defaultLevel->id;
            $member->save();
        }

        // V2.4: 注册奖励积分
        $registerPoints = (int) ConfigService::get('points_register', 50);
        if ($registerPoints > 0 && !$needAudit) {
            try {
                PointsService::add($member->id, $registerPoints, 'register', 0, '注册奖励');
            } catch (\Throwable) {
                // 积分添加失败不影响注册流程
            }
        }

        // V2.8: 邀请返积分处理
        if (!empty($data['invite_code']) && !$needAudit) {
            try {
                $this->processInviteReward($member->id, $data['invite_code'], request()->ip() ?? '0.0.0.0');
                // V2.9: 统一入口触发注册奖励（由InviteRewardService处理）
                InviteRewardService::onMemberEvent($member->id, 'register');
            } catch (\Throwable) {
                // 邀请处理失败不影响注册流程
            }
        }

        $msg = $needAudit ? '注册成功，请等待管理员审核' : '注册成功';
        return ['success' => true, 'msg' => $msg, 'data' => ['id' => $member->id]];
    }

    /**
     * 会员登录
     */
    public function login(string $username, string $password): array
    {
        $member = MemberModel::where('username', $username)
            ->whereOr('email', $username)
            ->find();

        if (!$member || !password_verify($password, $member->password)) {
            return ['success' => false, 'msg' => '用户名或密码错误'];
        }

        if ($member->status == 0) {
            return ['success' => false, 'msg' => '账号已被禁用'];
        }
        if ($member->status == 2) {
            return ['success' => false, 'msg' => '账号待审核，请等待管理员审核通过'];
        }

        // 生成Token并写入Cookie+Cache
        $token = bin2hex(random_bytes(32));
        $hash = sha1($token);
        $cacheKey = 'i8j_member_token_' . $hash;
        $memberData = [
            'id'       => $member->id,
            'username' => $member->username,
            'nickname' => $member->nickname,
            'email'    => $member->email,
            'avatar'   => $member->avatar,
        ];

        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey, $memberData, 7200);
        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey . '_id', $member->id, 7200);
        Cookie::set('member_token', $token, ['expire' => 7200, 'httponly' => true]);

        // V2.7: 登录时VIP过期实时检查
        $vipExpiredNotice = '';
        if ($member->vip_expire_time > 0 && $member->vip_expire_time < time() && $member->level_id > 0) {
            $currentLevel = \app\common\model\MemberLevel::find($member->level_id);
            if ($currentLevel && $currentLevel->is_vip) {
                $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->value('id') ?: 0;
                $member->level_id = $defaultLevel;
                $member->save();
                $vipExpiredNotice = '您的VIP已过期，会员等级已重置为默认等级，请及时续费。';
                \think\facade\Log::info("VIP过期实时检查: 会员{$member->id}等级已重置");
            }
        }

        // 更新登录信息
        $member->last_login_time = time();
        $member->last_login_ip = request()->ip();
        $member->save();

        $msg = $vipExpiredNotice ?: '登录成功';
        return ['success' => true, 'msg' => $msg, 'data' => $memberData];
    }

    /**
     * 会员退出
     */
    public function logout(int $memberId): void
    {
        $token = Cookie::get('member_token');
        if ($token) {
            $hash = sha1($token);
            Cache::delete('i8j_member_token_' . $hash);
            Cache::delete('i8j_member_token_' . $hash . '_id');
        }
        Cookie::delete('member_token');
    }

    /**
     * 更新资料
     */
    public function updateProfile(int $memberId, array $data): array
    {
        $member = MemberModel::find($memberId);
        if (!$member) {
            return ['success' => false, 'msg' => '会员不存在'];
        }

        $allowFields = ['nickname', 'avatar'];
        $update = array_intersect_key($data, array_flip($allowFields));
        $member->save($update);

        return ['success' => true, 'msg' => '资料更新成功'];
    }

    /**
     * 修改密码
     */
    public function changePassword(int $memberId, string $oldPassword, string $newPassword): array
    {
        $member = MemberModel::find($memberId);
        if (!$member || !password_verify($oldPassword, $member->password)) {
            return ['success' => false, 'msg' => '原密码错误'];
        }

        $member->password = $newPassword;
        $member->save();

        return ['success' => true, 'msg' => '密码修改成功'];
    }

    /**
     * 后台管理员保存会员（新增/编辑）
     */
    public function adminSave(array $data, ?int $id = null): array
    {
        $isNew = !$id;

        if (empty($data['username']) || empty($data['email'])) {
            return ['success' => false, 'msg' => '用户名和邮箱不能为空'];
        }

        $checkQuery = MemberModel::where('username', $data['username']);
        if (!$isNew) {
            $checkQuery->where('id', '<>', $id);
        }
        if ($checkQuery->find()) {
            return ['success' => false, 'msg' => '用户名已存在'];
        }

        $checkQueryEmail = MemberModel::where('email', $data['email']);
        if (!$isNew) {
            $checkQueryEmail->where('id', '<>', $id);
        }
        if ($checkQueryEmail->find()) {
            return ['success' => false, 'msg' => '邮箱已被注册'];
        }

        if ($isNew) {
            if (empty($data['password'])) {
                return ['success' => false, 'msg' => '密码不能为空'];
            }
            $member = new MemberModel;
            $member->save([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'nickname' => $data['nickname'] ?? $data['username'],
                'avatar'   => $data['avatar'] ?? '',
                'status'   => isset($data['status']) ? (int) $data['status'] : 1,
                'level_id' => isset($data['level_id']) ? (int) $data['level_id'] : 0,
            ]);

            // 赋予默认等级
            if (empty($data['level_id'])) {
                $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->find();
                if ($defaultLevel) {
                    $member->level_id = $defaultLevel->id;
                    $member->save();
                }
            }
        } else {
            $member = MemberModel::find($id);
            if (!$member) {
                return ['success' => false, 'msg' => '会员不存在'];
            }

            $update = [
                'username' => $data['username'],
                'email'    => $data['email'],
                'nickname' => $data['nickname'] ?? $data['username'],
                'avatar'   => $data['avatar'] ?? '',
                'status'   => isset($data['status']) ? (int) $data['status'] : $member->status,
                'level_id' => isset($data['level_id']) ? (int) $data['level_id'] : $member->level_id,
            ];
            if (!empty($data['password'])) {
                $update['password'] = $data['password'];
            }
            $member->save($update);

            // 如果审核通过，清除可能存在的token缓存并发放注册积分
            if ($member->status == 1 && isset($data['status']) && (int) $data['status'] === 1) {
                Cache::tag(CacheService::TAG_MEMBER)->clear();
                $registerPoints = (int) ConfigService::get('points_register', 50);
                if ($registerPoints > 0) {
                    try {
                        PointsService::add($member->id, $registerPoints, 'register', 0, '注册奖励');
                    } catch (\Throwable) {
                    }
                }
            }
        }

        return ['success' => true, 'msg' => $isNew ? '添加成功' : '更新成功', 'data' => ['id' => $member->id]];
    }

    /**
     * V2.8: 处理邀请奖励
     */
    protected function processInviteReward(int $inviteeId, string $inviteCode, string $ip): void
    {
        $inviteRelation = \app\common\model\InviteLog::getByCode($inviteCode);
        if (!$inviteRelation) {
            return;
        }
        
        $inviterId = $inviteRelation->inviter_id;
        
        // 防止自邀
        if ($inviterId === $inviteeId) {
            return;
        }
        
        // 检查是否已邀请过
        if (\app\common\model\InviteLog::where('inviter_id', $inviterId)->where('invitee_id', $inviteeId)->find()) {
            return;
        }
        
        // 防刷：同IP限3次
        $ipCount = \app\common\model\InviteLog::where('invitee_ip', $ip)->count();
        if ($ipCount >= 3) {
            return;
        }
        
        // 创建邀请关系（reward_stage=-1 表示未发放任何奖励，由InviteRewardService统一处理）
        $relation = new \app\common\model\InviteLog();
        $relation->save([
            'inviter_id' => $inviterId,
            'invitee_id' => $inviteeId,
            'invite_code' => \app\common\model\InviteLog::generateCode($inviteeId),
            'invitee_ip' => $ip,
            'reward_points' => 0,
            'reward_stage' => -1,
            'create_time' => time(),
        ]);
        
        // 发放被邀请人注册奖励积分（邀请人奖励由InviteRewardService::onMemberEvent统一处理）
        $inviteePoints = (int) ConfigService::get('points_invitee_register', 20);
        if ($inviteePoints > 0) {
            try {
                PointsService::add($inviteeId, $inviteePoints, 'invited', $relation->id, '被邀请注册奖励');
            } catch (\Throwable) {
            }
        }
    }
}