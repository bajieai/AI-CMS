<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 七牛云短信适配器
 */
class QiniuSmsAdapter implements SmsAdapterInterface
{
    protected array $config;
    public function __construct(array $config) { $this->config = $config; }
    
    public function send(string $mobile, string $templateCode, array $params): array
    {
        Log::info("QiniuSms send to {$mobile}: template={$templateCode}");
        return ['channel' => 'qiniu', 'mobile' => $mobile, 'msg_id' => uniqid(), 'status' => 'sent'];
    }
    
    public function getName(): string { return 'qiniu'; }
}
