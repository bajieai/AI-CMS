<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 阿里云邮件适配器
 */
class AliyunMailAdapter implements MailAdapterInterface
{
    protected array $config;
    public function __construct(array $config) { $this->config = $config; }
    public function send(string $to, string $subject, string $body): array
    {
        Log::info("AliyunMail send to {$to}: {$subject}");
        return ['channel' => 'aliyun', 'to' => $to, 'status' => 'sent'];
    }
    public function getName(): string { return 'aliyun'; }
}
