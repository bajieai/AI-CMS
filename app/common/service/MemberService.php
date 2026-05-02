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

        $member = new MemberModel;
        $member->save([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'nickname' => $data['nickname'] ?? $data['username'],
            'status'   => 1,
        ]);

        // V2.4: 赋予默认等级
        $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->find();
        if ($defaultLevel) {
            $member->level_id = $defaultLevel->id;
            $member->save();
        }

        // V2.4: 注册奖励积分
        $registerPoints = (int) ConfigService::get('points_register', 50);
        if ($registerPoints > 0) {
            try {
                PointsService::add($member->id, $registerPoints, 'register', 0, '注册奖励');
            } catch (\Throwable) {
                // 积分添加失败不影响注册流程
            }
        }

        return ['success' => true, 'msg' => '注册成功', 'data' => ['id' => $member->id]];
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

        if ($member->status != 1) {
            return ['success' => false, 'msg' => '账号已被禁用'];
        }

        // 生成Token并写入Cookie+Cache
        $token = bin2hex(random_bytes(32));
        $hash = sha1($token);
        $cacheKey = 'i8j_member_token_' . $hash;
        $memberData = [
            'id'       => $member->id,
            'username' => $member->username,
            'nickname' => $member->nickname,
            'avatar'   => $member->avatar,
        ];

        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey, $memberData, 7200);
        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey . '_id', $member->id, 7200);
        Cookie::set('member_token', $token, ['expire' => 7200, 'httponly' => true]);

        // 更新登录信息
        $member->last_login_time = time();
        $member->last_login_ip = request()->ip();
        $member->save();

        return ['success' => true, 'msg' => '登录成功', 'data' => $memberData];
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
}