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
use app\common\service\EmailService;
use think\facade\Cache;
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
        Cache::set('pwd_reset_' . $token, ['member_id' => $member->id, 'expire' => time() + 1800], 1800);

        $siteUrl = config('app.app_host') ?: request()->domain();
        $resetUrl = rtrim($siteUrl, '/') . '/member/password/reset?token=' . $token;
        $siteName = \app\common\service\ConfigService::get('site_name', 'AI-CMS');

        // 优先使用 EmailService（支持 smtp_from_name/smtp_from_email 配置），失败降级到 MailService
        $subject = '【' . $siteName . '】密码找回';
        $body = $this->buildPasswordResetEmail($resetUrl, $siteName);
        $result = EmailService::send($email, $subject, $body);

        if (!$result) {
            // EmailService 失败，降级使用 MailService（支持 PHP mail() 降级）
            $mailService = new MailService();
            $result = $mailService->send($email, $subject, $body);
        }

        if (!$result) {
            // 发送失败，清除 token
            Cache::delete('pwd_reset_' . $token);
            return ['code' => 1, 'msg' => '邮件发送失败，请联系管理员'];
        }

        return ['code' => 0, 'msg' => '重置链接已发送到您的邮箱'];
    }

    /**
     * 构建密码重置邮件 HTML 正文
     */
    protected function buildPasswordResetEmail(string $resetUrl, string $siteName): string
    {
        $expireMinutes = 30;
        $siteUrl = config('app.app_host') ?: request()->domain();

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>密码找回</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1a73e8;padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:600;margin:0;">{$siteName}</h1>
                            <p style="color:rgba(255,255,255,0.85);font-size:14px;margin:8px 0 0;">密码找回</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <p style="font-size:15px;color:#333333;line-height:1.6;margin:0 0 20px;">
                                您好，您正在为账号申请重置密码。
                            </p>
                            <p style="font-size:15px;color:#333333;line-height:1.6;margin:0 0 24px;">
                                请点击下方按钮重置密码（{$expireMinutes} 分钟内有效）：
                            </p>
                            <!-- Button -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="background-color:#1a73e8;border-radius:6px;">
                                        <a href="{$resetUrl}" target="_blank" style="display:inline-block;padding:14px 48px;color:#ffffff;font-size:16px;font-weight:500;text-decoration:none;letter-spacing:0.5px;">
                                            重置密码
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <!-- Fallback link -->
                            <p style="font-size:13px;color:#888888;line-height:1.6;margin:0 0 16px;">
                                如果按钮无法点击，请复制以下链接到浏览器打开：
                            </p>
                            <p style="background-color:#f8f9fa;border:1px solid #e8eaed;border-radius:4px;padding:12px 16px;font-size:13px;color:#555555;word-break:break-all;margin:0 0 24px;line-height:1.5;">
                                {$resetUrl}
                            </p>
                            <!-- Warning -->
                            <table cellpadding="0" cellspacing="0" style="background-color:#fff8e1;border:1px solid #ffcc02;border-radius:4px;width:100%;">
                                <tr>
                                    <td style="padding:12px 16px;">
                                        <p style="font-size:12px;color:#8d6e00;margin:0;line-height:1.5;">
                                            如非您本人操作，请忽略此邮件。为保障账号安全，请勿将链接转发给他人。
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px 40px;text-align:center;border-top:1px solid #e8eaed;">
                            <p style="font-size:12px;color:#999999;margin:0;line-height:1.5;">
                                此邮件由系统自动发送，请勿回复。
                            </p>
                            <p style="font-size:12px;color:#999999;margin:4px 0 0;line-height:1.5;">
                                {$siteUrl}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $data = Cache::get('pwd_reset_' . $token);
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
        Cache::delete('pwd_reset_' . $token);

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
