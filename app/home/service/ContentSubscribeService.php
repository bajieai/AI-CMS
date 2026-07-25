<?php
declare(strict_types=1);

namespace app\home\service;

use app\common\model\ContentSubscription;
use app\common\model\Content;
use think\facade\Cache;

/**
 * 内容订阅服务 - V2.9.29 Sprint I-7
 */
class ContentSubscribeService
{
    public function getSubscribedUsers(string $type, int $targetId): array
    {
        return Cache::remember('sub_users_' . $type . '_' . $targetId, function () use ($type, $targetId) {
            return ContentSubscription::where('subscribe_type', $type)
                ->where('subscribe_id', $targetId)
                ->where('notify_site', 1)
                ->select()->toArray();
        }, 300);
    }

    public function notifySubscribers(string $type, int $targetId, int $contentId): void
    {
        $users = $this->getSubscribedUsers($type, $targetId);
        foreach ($users as $user) {
            \app\common\service\PrivateMessageService::sendSystemMessage(
                $user['user_id'],
                '订阅内容更新',
                '您订阅的内容有新更新，点击查看：' . $contentId
            );
        }
    }
}
