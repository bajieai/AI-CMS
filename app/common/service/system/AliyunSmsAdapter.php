<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 阿里云短信适配器
 */
class AliyunSmsAdapter implements SmsAdapterInterface
{
    protected array $config;
    public function __construct(array $config) { $this->config = $config; }
    
    public function send(string $mobile, string $templateCode, array $params): array
    {
        // 简化: 实际应调用阿里云SDK
        Log::info("AliyunSms send to {$mobile}: template={$templateCode}");
        return ['channel' => 'aliyun', 'mobile' => $mobile, 'msg_id' => uniqid(), 'status' => 'sent'];
    }
    
    public function getName(): string { return 'aliyun'; }
}
