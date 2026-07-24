<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 腾讯云短信适配器
 */
class TencentSmsAdapter implements SmsAdapterInterface
{
    protected array $config;
    public function __construct(array $config) { $this->config = $config; }
    
    public function send(string $mobile, string $templateCode, array $params): array
    {
        Log::info("TencentSms send to {$mobile}: template={$templateCode}");
        return ['channel' => 'tencent', 'mobile' => $mobile, 'msg_id' => uniqid(), 'status' => 'sent'];
    }
    
    public function getName(): string { return 'tencent'; }
}
