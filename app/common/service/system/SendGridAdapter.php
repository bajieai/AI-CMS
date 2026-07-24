<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * SendGrid邮件适配器
 */
class SendGridAdapter implements MailAdapterInterface
{
    protected array $config;
    public function __construct(array $config) { $this->config = $config; }
    public function send(string $to, string $subject, string $body): array
    {
        Log::info("SendGrid send to {$to}: {$subject}");
        return ['channel' => 'sendgrid', 'to' => $to, 'status' => 'sent'];
    }
    public function getName(): string { return 'sendgrid'; }
}
