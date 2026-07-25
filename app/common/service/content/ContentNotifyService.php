<?php
declare(strict_types=1);

namespace app\common\service\content;

use app\common\model\ContentSubscription;
use app\common\model\Content;
use think\facade\Db;
use think\facade\Cache;

/**
 * 内容推送通知服务 - V2.9.29 Sprint I-7
 */
class ContentNotifyService
{
    /**
     * 新内容发布时通知订阅者
     */
    public function onContentPublished(int $contentId): void
    {
        $content = Content::find($contentId);
        if (!$content) return;

        // 按栏目订阅
        $subs = ContentSubscription::where('subscribe_type', 'category')
            ->where('subscribe_id', $content->cate_id)
            ->where('notify_site', 1)
            ->select();

        foreach ($subs as $sub) {
            Db::name('private_message')->insert([
                'from_user_id' => 0,
                'to_user_id' => $sub->user_id,
                'title' => '订阅内容更新',
                'content' => '您订阅的分类有新内容发布：' . $content->title,
                'type' => 'system',
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 发送摘要推送
     */
    public function sendDigest(int $userId, string $frequency = 'daily'): array
    {
        $contents = Content::where('status', 2)
            ->where('create_time', '>=', date('Y-m-d H:i:s', strtotime('-1 day')))
            ->order('views', 'desc')
            ->limit(10)
            ->select();

        return ['user_id' => $userId, 'frequency' => $frequency, 'items' => $contents->toArray()];
    }
}
