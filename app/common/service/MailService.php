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

/**
 * 邮件发送服务 - V2.9.18 D-3
 * 
 * 封装 SMTP / PHPMail 邮件发送，从 setting 表读取 SMTP 配置
 */
class MailService
{
    /**
     * 发送邮件
     *
     * @param string $to      收件人邮箱
     * @param string $subject 主题
     * @param string $body    正文（HTML）
     * @return bool
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $config = $this->getConfig();

        // 尝试 SMTP
        if (!empty($config['host'])) {
            return $this->sendBySmtp($to, $subject, $body, $config);
        }

        // 降级使用 PHP mail()
        return $this->sendByPhpMail($to, $subject, $body);
    }

    /**
     * SMTP 发送
     */
    protected function sendBySmtp(string $to, string $subject, string $body, array $config): bool
    {
        try {
            $transport = (new \Swift_SmtpTransport(
                $config['host'],
                (int) ($config['port'] ?? 465),
                $config['encryption'] ?? 'ssl'
            ))
                ->setUsername($config['username'] ?? '')
                ->setPassword($config['password'] ?? '')
                ->setTimeout(30);

            $mailer = new \Swift_Mailer($transport);

            $message = (new \Swift_Message($subject))
                ->setFrom([$config['from_addr'] ?? 'noreply@example.com' => $config['from_name'] ?? 'AI-CMS'])
                ->setTo($to)
                ->setBody($body, 'text/html');

            return (bool) $mailer->send($message);
        } catch (\Throwable $e) {
            trace('MailService SMTP error: ' . $e->getMessage(), 'error');
            // 降级到 PHP mail()
            return $this->sendByPhpMail($to, $subject, $body);
        }
    }

    /**
     * PHP mail() 降级
     */
    protected function sendByPhpMail(string $to, string $subject, string $body): bool
    {
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: " . ($this->getConfig()['from_name'] ?? 'AI-CMS') . " <" . ($this->getConfig()['from_addr'] ?? 'noreply@example.com') . ">\r\n";

            return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
        } catch (\Throwable $e) {
            trace('MailService PHP mail() error: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 获取 SMTP 配置（优先 setting 表，其次 config/ai.php）
     */
    protected function getConfig(): array
    {
        $defaults = config('ai.mail', []);

        try {
            return [
                'host'       => \app\common\model\Config::where('name', 'smtp_host')->value('value') ?: ($defaults['host'] ?? ''),
                'port'       => \app\common\model\Config::where('name', 'smtp_port')->value('value') ?: ($defaults['port'] ?? '465'),
                'username'   => \app\common\model\Config::where('name', 'smtp_username')->value('value') ?: ($defaults['username'] ?? ''),
                'password'   => \app\common\model\Config::where('name', 'smtp_password')->value('value') ?: ($defaults['password'] ?? ''),
                'encryption' => \app\common\model\Config::where('name', 'smtp_encryption')->value('value') ?: ($defaults['encryption'] ?? 'ssl'),
                'from_addr'  => \app\common\model\Config::where('name', 'smtp_from_addr')->value('value') ?: ($defaults['from_addr'] ?? 'noreply@i8j.cn'),
                'from_name'  => \app\common\model\Config::where('name', 'site_name')->value('value') ?: ($defaults['from_name'] ?? 'AI-CMS'),
            ];
        } catch (\Throwable $e) {
            return $defaults;
        }
    }
}
