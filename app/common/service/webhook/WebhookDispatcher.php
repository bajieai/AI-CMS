<?php
declare(strict_types=1);
namespace app\common\service\webhook;

use app\common\model\WebhookEndpoint;
use app\common\model\WebhookLog;
use app\common\hook\HookEvents;
use think\facade\Log;

/**
 * Webhook事件分发服务 (V2.9.29 D-4)
 * 
 * curl超时3s连接+5s执行，连续失败10次禁用
 */
class WebhookDispatcher
{
    /**
     * 分发事件到所有匹配的Webhook端点
     */
    public function dispatch(string $eventName, array $payload = []): void
    {
        $endpoints = WebhookEndpoint::where('is_active', 1)->select();
        $signer = new WebhookSigner();

        foreach ($endpoints as $endpoint) {
            $events = json_decode($endpoint->events, true) ?: [];
            if (!in_array('*', $events) && !in_array($eventName, $events)) {
                continue;
            }

            $this->send($endpoint, $eventName, $payload, $signer);
        }
    }

    /**
     * 发送到单个端点
     */
    private function send($endpoint, string $eventName, array $payload, WebhookSigner $signer): void
    {
        $data = json_encode([
            'event' => $eventName,
            'data' => $payload,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);

        $signature = $signer->sign($data, $endpoint->secret);
        $startTime = microtime(true);

        $ch = curl_init($endpoint->url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-Event: ' . $eventName,
            ],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);

        // 记录日志
        WebhookLog::create([
            'endpoint_id' => $endpoint->id,
            'event_name' => $eventName,
            'payload' => $data,
            'response_code' => $httpCode,
            'response_body' => is_string($response) ? mb_substr($response, 0, 2000) : '',
            'status' => $success ? 2 : 3,
            'attempt' => 1,
            'duration_ms' => $durationMs,
            'error_message' => $error,
            'create_time' => time(),
        ]);

        // 更新端点状态
        $endpoint->last_sent_at = time();
        $endpoint->last_status = $success ? 1 : 0;

        if ($success) {
            $endpoint->fail_count = 0;
        } else {
            $endpoint->fail_count = ($endpoint->fail_count ?? 0) + 1;
            // 连续失败10次自动禁用
            if ($endpoint->fail_count >= 10) {
                $endpoint->is_active = 0;
                Log::warning("Webhook端点 {$endpoint->id} ({$endpoint->name}) 因连续失败10次被自动禁用");
            }
        }
        $endpoint->save();
    }
}
