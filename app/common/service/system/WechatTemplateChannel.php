<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 微信模板消息通道
 */
class WechatTemplateChannel
{
    public function send($to, string $templateCode, array $params): array
    {
        Log::info("Wechat template message to {$to}: {$templateCode}");
        return ['channel' => 'wechat', 'status' => 'sent'];
    }
}
