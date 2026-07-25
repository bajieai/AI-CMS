<?php
declare(strict_types=1);
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\WebhookLog;
use app\common\model\WebhookEndpoint;
use app\common\service\webhook\WebhookSigner;

/**
 * Webhook重试命令 (V2.9.29 D-4)
 * 每5分钟执行，重试3次(5s/30s/300s间隔)
 */
class WebhookRetryCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('webhook:retry')
            ->setDescription('Webhook失败推送重试（每5分钟执行）');
    }

    protected function execute(Input $input, Output $output): void
    {
        $output->writeln('<info>=== Webhook重试开始 ===</info>');

        // 查找待重试的失败日志
        $failedLogs = WebhookLog::where('status', 3)
            ->where('attempt', '<', 3)
            ->where('create_time', '>', time() - 3600)
            ->order('id', 'asc')
            ->limit(100)
            ->select();

        $output->writeln("待重试记录: {$failedLogs->count()}");

        $signer = new WebhookSigner();
        $retryIntervals = [5, 30, 300];

        foreach ($failedLogs as $log) {
            $elapsed = time() - $log->create_time;
            $neededInterval = $retryIntervals[min($log->attempt - 1, 2)] ?? 300;

            if ($elapsed < $neededInterval) continue;

            $endpoint = WebhookEndpoint::find($log->endpoint_id);
            if (!$endpoint || !$endpoint->is_active) continue;

            $this->retrySend($log, $endpoint, $signer);
        }

        $output->writeln('<info>=== Webhook重试完成 ===</info>');
    }

    private function retrySend($log, $endpoint, $signer): void
    {
        $data = $log->payload;
        $signature = $signer->sign($data, $endpoint->secret);
        $startTime = microtime(true);

        $ch = curl_init($endpoint->url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-Event: ' . $log->event_name,
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

        $log->response_code = $httpCode;
        $log->response_body = mb_substr((string)$response, 0, 2000);
        $log->status = $success ? 2 : 3;
        $log->attempt = $log->attempt + 1;
        $log->duration_ms = $durationMs;
        $log->error_message = $error;
        $log->save();
    }
}
