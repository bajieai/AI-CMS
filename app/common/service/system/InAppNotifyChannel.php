<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;

/**
 * 站内信通知通道
 */
class InAppNotifyChannel
{
    public function send($to, string $templateCode, array $params): array
    {
        Db::name('private_message')->insert([
            'to_uid' => is_int($to) ? $to : 0,
            'title' => $params['title'] ?? '通知',
            'content' => $params['content'] ?? '',
            'type' => 'system',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['channel' => 'in_app', 'status' => 'sent'];
    }
}
