<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\SsePushService;
use think\Response;

/**
 * V2.9.20 C-1: SSE 推送端点
 * 
 * 3通道：audit/comment/system
 * 心跳包：每30秒发送一次
 * PHP-FPM 最长5分钟连接限制，前端需自动重连
 */
class SseController extends BaseController
{
    /**
     * SSE 推送流
     */
    public function stream(string $channel = 'system'): Response
    {
        $validChannels = [
            SsePushService::CHANNEL_AUDIT,
            SsePushService::CHANNEL_COMMENT,
            SsePushService::CHANNEL_SYSTEM,
        ];
        if (!in_array($channel, $validChannels, true)) {
            $channel = SsePushService::CHANNEL_SYSTEM;
        }

        // 关闭输出缓冲
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');

        for ($i = 0; $i < ob_get_level(); $i++) {
            ob_end_flush();
        }
        ob_implicit_flush(true);

        $response = new Response();
        $response->header([
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);

        $lastTime = time();
        $heartbeatInterval = 30; // 30秒心跳
        $maxRuntime = 290;       // PHP-FPM 5分钟限制，留10秒缓冲
        $startTime = time();

        $callback = function () use ($channel, &$lastTime, $heartbeatInterval, $maxRuntime, $startTime) {
            // 发送初始连接事件
            echo "event: connected\n";
            echo "data: " . json_encode(['channel' => $channel, 'time' => time()]) . "\n\n";
            flush();

            while (true) {
                // 检查最大运行时间
                if (time() - $startTime > $maxRuntime) {
                    echo "event: close\n";
                    echo "data: " . json_encode(['reason' => 'timeout', 'time' => time()]) . "\n\n";
                    flush();
                    break;
                }

                // 检查客户端断开
                if (connection_aborted()) {
                    break;
                }

                // 拉取新消息
                $messages = SsePushService::pull($channel, $lastTime);
                foreach ($messages as $msg) {
                    echo "event: {$msg['channel']}\n";
                    echo "data: " . json_encode($msg['payload']) . "\n\n";
                    $lastTime = max($lastTime, $msg['time']);
                }

                if (!empty($messages)) {
                    flush();
                }

                // 心跳包：每30秒发送一次
                if (time() - $lastTime >= $heartbeatInterval) {
                    echo "event: heartbeat\n";
                    echo "data: " . json_encode(['type' => 'heartbeat', 'time' => time()]) . "\n\n";
                    flush();
                    $lastTime = time();
                }

                // 短暂休眠，降低 CPU 占用
                usleep(500000); // 0.5秒
            }
        };

        return $response->contentType('text/event-stream')->content($callback() ?: '');
    }
}
