<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\SsePushService;
use app\common\service\sse\SseEngine;
use app\common\service\sse\SseRateLimiter;
use app\common\model\SseMessageQueue;
use think\Response;
use think\facade\Log;

/**
 * V2.9.27 T-1: SSE 推送端点（升级版）
 * 
 * 特性：
 * - DB持久化队列（替代V2.9.20 Cache队列）
 * - Last-Event-Id 断线重连补推
 * - 连接限流（IP+用户限制）
 * - 4通道：audit/comment/system/notification
 * - 心跳包：每30秒
 * - PHP-FPM 最长5分钟连接限制，前端自动重连
 */
class SseController extends BaseController
{
    /**
     * SSE 推送流（升级版 — DB持久化队列）
     */
    public function stream(string $channel = 'system'): Response
    {
        $validChannels = [
            SseMessageQueue::CHANNEL_AUDIT,
            SseMessageQueue::CHANNEL_COMMENT,
            SseMessageQueue::CHANNEL_SYSTEM,
            SseMessageQueue::CHANNEL_NOTIFICATION,
        ];
        if (!in_array($channel, $validChannels, true)) {
            $channel = SseMessageQueue::CHANNEL_SYSTEM;
        }

        // T-6: 安全限流检查
        $clientIp = $this->request->ip();
        if (!SseRateLimiter::checkIpLimit($clientIp)) {
            return Response::create('Too many connections from this IP', 'html', 429);
        }

        // 获取Last-Event-Id（断线重连补推）
        $lastEventId = (int) $this->request->header('Last-Event-Id', 0);
        if ($lastEventId <= 0) {
            $lastEventId = (int) $this->request->get('last_event_id', 0);
        }

        // 获取用户ID（如果已登录）
        $userId = 0;
        try {
            $user = \app\common\service\MemberService::getCurrentUser();
            if ($user) $userId = (int)$user->id;
        } catch (\Throwable) {}

        // 注册客户端
        $clientId = $this->request->get('client_id', uniqid('sse_', true));
        SseEngine::registerClient($clientId, $userId, $clientIp, $this->request->header('User-Agent', ''), $channel);

        // 关闭输出缓冲
        if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        for ($i = 0; $i < ob_get_level(); $i++) ob_end_flush();
        ob_implicit_flush(true);

        $response = new Response();
        $response->header([
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);

        $heartbeatInterval = 30;
        $maxRuntime = 290;
        $startTime = time();
        $lastHeartbeat = time();

        $callback = function () use ($channel, $userId, $clientId, $lastEventId, $heartbeatInterval, $maxRuntime, $startTime, &$lastHeartbeat) {
            // 发送初始连接事件
            echo "event: connected\n";
            echo "data: " . json_encode(['channel' => $channel, 'time' => time(), 'client_id' => $clientId]) . "\n\n";
            flush();

            // T-5: 断线重连 — 补推离线消息
            if ($lastEventId > 0) {
                $offlineMessages = SseEngine::pull($channel, $userId, $lastEventId, 50);
                foreach ($offlineMessages as $msg) {
                    echo "id: {$msg['id']}\n";
                    echo "event: {$msg['event_type']}\n";
                    echo "data: " . json_encode($msg['payload']) . "\n\n";
                }
                if (!empty($offlineMessages)) flush();
            }

            $currentLastId = $lastEventId;

            while (true) {
                if (time() - $startTime > $maxRuntime) {
                    echo "event: close\n";
                    echo "data: " . json_encode(['reason' => 'timeout', 'time' => time(), 'last_event_id' => $currentLastId]) . "\n\n";
                    flush();
                    break;
                }
                if (connection_aborted()) break;

                // T-1: 从DB队列拉取消息
                $messages = SseEngine::pull($channel, $userId, $currentLastId, 20);
                $deliveredIds = [];
                foreach ($messages as $msg) {
                    echo "id: {$msg['id']}\n";
                    echo "event: {$msg['event_type']}\n";
                    echo "data: " . json_encode($msg['payload']) . "\n\n";
                    $currentLastId = max($currentLastId, (int)$msg['id']);
                    $deliveredIds[] = (int)$msg['id'];
                }
                if (!empty($messages)) {
                    flush();
                    SseEngine::markDelivered($deliveredIds);
                }

                // 心跳包
                if (time() - $lastHeartbeat >= $heartbeatInterval) {
                    echo "event: heartbeat\n";
                    echo "data: " . json_encode(['type' => 'heartbeat', 'time' => time()]) . "\n\n";
                    flush();
                    $lastHeartbeat = time();
                    SseEngine::heartbeat($clientId, $currentLastId);
                }

                usleep(500000); // 0.5秒
            }

            // 注销客户端
            SseEngine::unregisterClient($clientId);
        };

        return $response->contentType('text/event-stream')->content($callback() ?: '');
    }

    /**
     * V2.9.20: 长轮询降级端点（保留兼容）
     */
    public function poll()
    {
        $channel = $this->request->get('channel', 'system');
        $lastTime = (int) $this->request->get('last_time', 0);

        $messages = SsePushService::pull($channel, $lastTime);
        return json(['code' => 0, 'data' => ['messages' => $messages, 'last_time' => time()]]);
    }
}
