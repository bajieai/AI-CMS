<?php
declare(strict_types=1);

namespace app\common\service\mail;

use app\common\model\EmailTemplate;
use think\facade\Cache;

/**
 * V2.9.27 V-1: 邮件模板管理服务
 * 统一邮件模板管理，支持6种内置模板
 */
class MailTemplateService
{
    // 内置模板类型
    const TYPES = [
        'welcome' => '欢迎邮件',
        'verify' => '验证码邮件',
        'reset' => '密码重置',
        'notify' => '系统通知',
        'order' => '订单确认',
        'audit' => '审核结果',
    ];

    /**
     * 获取邮件模板列表
     */
    public static function getList(): array
    {
        return Cache::remember('mail_template_list', function () {
            return EmailTemplate::order('id', 'desc')->select()->toArray();
        }, 3600);
    }

    /**
     * 获取指定模板
     */
    public static function getTemplate(string $code): ?array
    {
        return Cache::remember('mail_template_' . $code, function () use ($code) {
            $tpl = EmailTemplate::where('code', $code)->find();
            return $tpl ? $tpl->toArray() : null;
        }, 3600);
    }

    /**
     * 渲染模板（变量替换）
     */
    public static function render(string $code, array $vars = []): array
    {
        $tpl = self::getTemplate($code);
        if (!$tpl) {
            return ['success' => false, 'msg' => '模板不存在'];
        }

        $subject = $tpl['subject'] ?? '';
        $body = $tpl['content'] ?? '';

        foreach ($vars as $key => $val) {
            $subject = str_replace('{{' . $key . '}}', (string)$val, $subject);
            $body = str_replace('{{' . $key . '}}', (string)$val, $body);
        }

        return ['success' => true, 'subject' => $subject, 'body' => $body];
    }

    /**
     * 初始化内置模板（首次安装时调用）
     */
    public static function initTemplates(): void
    {
        $templates = [
            ['code' => 'welcome', 'subject' => '欢迎加入{{site_name}}', 'content' => '尊敬的{{nickname}}，欢迎加入{{site_name}}！'],
            ['code' => 'verify', 'subject' => '您的验证码是{{code}}', 'content' => '验证码：{{code}}，有效期{{expire}}分钟。'],
            ['code' => 'reset', 'subject' => '密码重置链接', 'content' => '点击链接重置密码：{{link}}'],
            ['code' => 'notify', 'subject' => '系统通知：{{title}}', 'content' => '{{content}}'],
            ['code' => 'order', 'subject' => '订单确认：{{order_no}}', 'content' => '您的订单{{order_no}}已确认，金额：{{amount}}元。'],
            ['code' => 'audit', 'subject' => '审核结果通知', 'content' => '您的内容"{{title}}"审核{{result}}。'],
        ];

        foreach ($templates as $t) {
            EmailTemplate::where('code', $t['code'])->findOrEmpty()->save($t);
        }

        Cache::delete('mail_template_list');
    }
}