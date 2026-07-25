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
use app\common\service\push\PushDispatchService;

/**
 * 推送分发监听器 - V2.9.18 D-1
 * 
 * 监听 ContentPublished 事件，自动触发内容推送
 */
class PushDispatchListener
{
    public function handle(ContentPublished $event): void
    {
        try {
            $service = new PushDispatchService();
            if ($event->isManual) {
                $service->dispatchManual($event->contentId);
            } else {
                $service->dispatch($event->contentId);
            }
        } catch (\Throwable $e) {
            // 推送失败不影响内容发布流程，仅记录日志
            trace('PushDispatchListener error: ' . $e->getMessage(), 'error');
        }
    }
}
