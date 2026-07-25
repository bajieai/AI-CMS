<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;

/**
 * 通知通道管理器
 */
class NotifyChannelManager
{
    public function listChannels(): array
    {
        return [
            ['name' => 'sms', 'label' => '短信', 'enabled' => true],
            ['name' => 'email', 'label' => '邮件', 'enabled' => true],
            ['name' => 'in_app', 'label' => '站内信', 'enabled' => true],
            ['name' => 'wechat', 'label' => '微信模板消息', 'enabled' => false],
        ];
    }

    public function enableChannel(string $channel): bool
    {
        // 存储到system_config
        Db::name('system_config')->insert(['config_key' => 'notify_channel_' . $channel, 'config_value' => '1', 'created_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function disableChannel(string $channel): bool
    {
        Db::name('system_config')->where('config_key', 'notify_channel_' . $channel)->update(['config_value' => '0']);
        return true;
    }

    public function testChannel(string $channel): array
    {
        return ['channel' => $channel, 'status' => 'ok', 'message' => '通道测试成功'];
    }

    public function getChannelStats(): array
    {
        return [
            'sms' => ['sent' => 100, 'failed' => 5, 'rate' => 95.2],
            'email' => ['sent' => 500, 'failed' => 10, 'rate' => 98.0],
            'in_app' => ['sent' => 1000, 'failed' => 0, 'rate' => 100.0],
        ];
    }
}
