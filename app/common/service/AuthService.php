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

namespace app\common\service;

use app\common\model\Member;
use think\facade\Session;

/**
 * 登录注册增强服务 - V2.9.18 U-2
 * 
 * 邮箱注册 + 图形验证码 + 注册频率限制 + 密码找回
 */
class AuthService
{
    /** 密码最小长度 */
    const PASSWORD_MIN_LEN = 8;
    /** 24h 同IP最大注册数 */
    const RATE_LIMIT_PER_IP = 3;
    /** 用户名黑名单 */
    const USERNAME_BLACKLIST = ['admin', 'root', 'system', 'test', 'administrator'];

    /**
     * 邮箱注册
     */
    public function registerByEmail(array $data): array
    {
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $code     = $data['code'] ?? '';
        $ip       = request()->ip();

        // 邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['code' => 1, 'msg' => '邮箱格式不正确'];
        }

        // 邮箱唯一
        if (Member::where('email', $email)->find()) {
            return ['code' => 1, 'msg' => '该邮箱已被注册'];
        }

        // 密码强度
        $pwdCheck = $this->checkPasswordStrength($password);
        if (!$pwdCheck['valid']) {
            return ['code' => 1, 'msg' => $pwdCheck['msg']];
        }

        // 邮箱验证码校验
        if (!$this->verifyEmailCode($email, $code)) {
            return ['code' => 1, 'msg' => '验证码错误或已过期'];
        }

        // 频率限制
        if (!$this->checkRateLimit($ip)) {
            return ['code' => 1, 'msg' => '注册太频繁，请24小时后重试'];
        }

        // 创建用户
        $member = Member::create([
            'username'       => $email,
            'email'         => $email,
            'password'      => $password,
            'nickname'      => explode('@', $email)[0],
            'status'        => 1,
            'email_verified'=> 1,
            'register_ip'   => $ip,
            'register_source' => 'email',
            'create_time'   => time(),
        ]);

        return ['code' => 0, 'msg' => '注册成功', 'data' => ['member_id' => $member->id]];
    }

    /**
     * 发送邮箱验证码
     */
    public function sendEmailVerifyCode(string $email): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['code' => 1, 'msg' => '邮箱格式不正确'];
        }

        if (Member::where('email', $email)->find()) {
            return ['code' => 1, 'msg' => '该邮箱已被注册'];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Session::set('verify_code_' . $email, ['code' => $code, 'expire' => time() + 300]);

        // 发送验证码邮件
        $mailService = new MailService();
        $subject = '【AI-CMS】邮箱验证码';
        $body = "<p>您的验证码是：<strong style='font-size:24px;letter-spacing:4px'>{$code}</strong></p><p>有效期5分钟，请勿泄露。</p>";
        $mailService->send($email, $subject, $body);

        return ['code' => 0, 'msg' => '验证码已发送'];
    }

    /**
     * 密码找回：发送重置链接
     */
    public function sendPasswordResetEmail(string $email): array
    {
        $member = Member::where('email', $email)->find();
        if (!$member) {
            return ['code' => 1, 'msg' => '该邮箱未注册'];
        }

        $token = bin2hex(random_bytes(32));
        Session::set('pwd_reset_' . $token, ['member_id' => $member->id, 'expire' => time() + 1800]);

        $siteUrl = config('app.app_host', request()->domain());
        $resetUrl = rtrim($siteUrl, '/') . '/member/password/reset?token=' . $token;

        $mailService = new MailService();
        $subject = '【AI-CMS】密码找回';
        $body = "<p>点击下方链接重置密码（30分钟内有效）：</p><p><a href='{$resetUrl}'>{$resetUrl}</a></p>";
        $mailService->send($email, $subject, $body);

        return ['code' => 0, 'msg' => '重置链接已发送到您的邮箱'];
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $data = Session::get('pwd_reset_' . $token);
        if (!$data || $data['expire'] < time()) {
            return ['code' => 1, 'msg' => '重置链接已过期，请重新申请'];
        }

        $pwdCheck = $this->checkPasswordStrength($newPassword);
        if (!$pwdCheck['valid']) {
            return ['code' => 1, 'msg' => $pwdCheck['msg']];
        }

        $member = Member::find($data['member_id']);
        if (!$member) {
            return ['code' => 1, 'msg' => '用户不存在'];
        }

        $member->password = $newPassword;
        $member->save();

        Session::delete('pwd_reset_' . $token);

        return ['code' => 0, 'msg' => '密码重置成功，请使用新密码登录'];
    }

    public function checkPasswordStrength(string $password): array
    {
        if (mb_strlen($password) < self::PASSWORD_MIN_LEN) {
            return ['valid' => false, 'msg' => '密码至少8位'];
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            return ['valid' => false, 'msg' => '密码需包含字母'];
        }
        if (!preg_match('/\d/', $password)) {
            return ['valid' => false, 'msg' => '密码需包含数字'];
        }
        return ['valid' => true, 'msg' => ''];
    }

    public function checkUsernameBlacklist(string $username): bool
    {
        foreach (self::USERNAME_BLACKLIST as $word) {
            if (stripos($username, $word) !== false) return false;
        }
        return true;
    }

    protected function checkRateLimit(string $ip): bool
    {
        $today = strtotime('today');
        $count = Member::where('register_ip', $ip)
            ->where('create_time', '>=', $today)
            ->count();
        return $count < self::RATE_LIMIT_PER_IP;
    }

    protected function verifyEmailCode(string $email, string $code): bool
    {
        $data = Session::get('verify_code_' . $email);
        if (!$data) return false;
        if ($data['expire'] < time()) {
            Session::delete('verify_code_' . $email);
            return false;
        }
        $valid = $data['code'] === $code;
        if ($valid) Session::delete('verify_code_' . $email);
        return $valid;
    }
}
