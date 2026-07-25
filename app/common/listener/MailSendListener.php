<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\listener;

use app\common\event\ContentPublished;
use app\common\service\SubscribeService;

/**
 * 邮件发送监听器 - V2.9.18 D-3
 * 
 * 监听 ContentPublished 事件，自动通知订阅者
 */
class MailSendListener
{
    public function handle(ContentPublished $event): void
    {
        // 仅自动发布时发送邮件通知（手动推送不发送）
        if ($event->isManual) return;

        try {
            $service = new SubscribeService();
            $count = $service->notifySubscribers($event->contentId);
            if ($count > 0) {
                trace("MailSendListener: sent {$count} notification emails", 'info');
            }
        } catch (\Throwable $e) {
            trace('MailSendListener error: ' . $e->getMessage(), 'error');
        }
    }
}
