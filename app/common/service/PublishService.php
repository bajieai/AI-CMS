<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content as ContentModel;
use app\common\service\NotificationService;

/**
 * 定时发布服务
 */
class PublishService
{
    /**
     * 执行定时发布
     */
    public function schedule(): array
    {
        $now = time();
        $contents = ContentModel::where('status', 1)
            ->where('publish_time', '>', 0)
            ->where('publish_time', '<=', $now)
            ->select();

        $count = 0;
        foreach ($contents as $content) {
            $content->status = 2;
            $content->save();
            $count++;

            // 发送发布通知
            $notifService = new NotificationService();
            $notifService->send('admin', $content->user_id, 'publish', '内容已定时发布', "《{$content->title}》已按计划发布", url('/content/' . $content->id));
        }

        return ['success' => true, 'msg' => "本次发布 {$count} 条内容", 'count' => $count];
    }

    /**
     * 获取待发布队列
     */
    public function getQueue(int $limit = 10): array
    {
        return ContentModel::where('status', 1)
            ->where('publish_time', '>', time())
            ->order('publish_time', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}