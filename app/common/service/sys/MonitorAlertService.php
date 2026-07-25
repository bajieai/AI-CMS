<?php
declare(strict_types=1);

namespace app\common\service\sys;

use app\admin\model\MonitorAlert;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Mail;

/**
 * 监控告警服务
 * V2.9.40 SYS-ROBUST2-1
 */
class MonitorAlertService
{
    private const CACHE_TAG = 'monitor_alert';

    public function createAlert(array $data): int
    {
        $alert = MonitorAlert::create([
            'alert_name'        => $data['alert_name'] ?? '',
            'monitor_type'      => $data['monitor_type'] ?? 'server',
            'monitor_metric'    => $data['monitor_metric'] ?? 'cpu',
            'alert_rule'        => is_array($data['alert_rule'] ?? null) ? json_encode($data['alert_rule'], JSON_UNESCAPED_UNICODE) : ($data['alert_rule'] ?? '{}'),
            'alert_level'       => $data['alert_level'] ?? 'warning',
            'alert_channels'    => is_array($data['alert_channels'] ?? null) ? json_encode($data['alert_channels'], JSON_UNESCAPED_UNICODE) : ($data['alert_channels'] ?? '[]'),
            'alert_recipients'  => is_array($data['alert_recipients'] ?? null) ? json_encode($data['alert_recipients'], JSON_UNESCAPED_UNICODE) : ($data['alert_recipients'] ?? '[]'),
            'escalation_config' => is_array($data['escalation_config'] ?? null) ? json_encode($data['escalation_config'], JSON_UNESCAPED_UNICODE) : ($data['escalation_config'] ?? '{}'),
            'cooldown_minutes'  => $data['cooldown_minutes'] ?? 30,
            'is_active'         => $data['is_active'] ?? 1,
            'trigger_count'     => 0,
        ]);
        Cache::clear();
        return (int)$alert->id;
    }

    public function updateAlert(int $id, array $data): bool
    {
        $alert = MonitorAlert::find($id);
        if (!$alert) {
            return false;
        }
        $fields = ['alert_name','monitor_type','monitor_metric','alert_rule','alert_level',
            'alert_channels','alert_recipients','escalation_config','cooldown_minutes','is_active'];
        $update = [];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update[$f] = is_array($data[$f]) ? json_encode($data[$f], JSON_UNESCAPED_UNICODE) : $data[$f];
            }
        }
        if ($update) {
            $alert->save($update);
            Cache::clear();
        }
        return true;
    }

    public function deleteAlert(int $id): bool
    {
        $result = MonitorAlert::destroy($id);
        if ($result) {
            Cache::clear();
        }
        return (bool)$result;
    }

    public function getAlert(int $id): array
    {
        $alert = MonitorAlert::find($id);
        if (!$alert) {
            return [];
        }
        $data = $alert->toArray();
        foreach (['alert_rule','alert_channels','alert_recipients','escalation_config'] as $f) {
            $data[$f] = $this->decodeJson($data[$f] ?? '');
        }
        return $data;
    }

    public function getAlertList(int $page, int $pageSize): array
    {
        $cacheKey = 'alert_list_' . $page . '_' . $pageSize;
        return Cache::remember($cacheKey, function () use ($page, $pageSize) {
            $total = MonitorAlert::count();
            $list = MonitorAlert::order('id', 'desc')->page($page, $pageSize)->select()->toArray();
            return ['total' => $total, 'list' => $list, 'page' => $page, 'page_size' => $pageSize];
        }, 60);
    }

    public function checkAlerts(): void
    {
        $alerts = MonitorAlert::where('is_active', 1)->select();
        $monitor = new SystemMonitorService();
        $metrics = $this->collectAllMetrics($monitor);

        foreach ($alerts as $alert) {
            try {
                $rule = $this->decodeJson($alert->alert_rule ?? '{}');
                $metricValue = $metrics[$alert->monitor_metric] ?? null;
                if ($metricValue === null) {
                    continue;
                }
                if ($this->evaluateRule($rule, [$alert->monitor_metric => $metricValue])) {
                    if ($this->isInCooldown($alert)) {
                        continue;
                    }
                    $this->triggerAlert($alert, (float)$metricValue, $metrics);
                }
            } catch (\Throwable $e) {
                Log::error("MonitorAlert checkAlerts failed for alert {$alert->id}: " . $e->getMessage());
            }
        }
    }

    public function evaluateRule(array $rule, array $metrics): bool
    {
        $operator = $rule['operator'] ?? '>';
        $threshold = (float)($rule['threshold'] ?? 0);
        $duration = (int)($rule['duration'] ?? 0);
        $metricKey = $rule['metric'] ?? array_key_first($metrics) ?? '';
        if (empty($metricKey) || !isset($metrics[$metricKey])) {
            return false;
        }
        $value = (float)$metrics[$metricKey];
        $matched = match ($operator) {
            '>'  => $value > $threshold,
            '>=' => $value >= $threshold,
            '<'  => $value < $threshold,
            '<=' => $value <= $threshold,
            '==' => abs($value - $threshold) < 0.01,
            '!=' => abs($value - $threshold) >= 0.01,
            default => false,
        };
        if ($matched && $duration > 0) {
            $cacheKey = 'alert_dur_' . $metricKey;
            $firstTriggered = Cache::get($cacheKey);
            if ($firstTriggered === null) {
                Cache::set($cacheKey, time(), $duration + 300);
                return false;
            }
            if ((time() - (int)$firstTriggered) < $duration) {
                return false;
            }
            Cache::delete($cacheKey);
        }
        if (!$matched) {
            Cache::delete('alert_dur_' . $metricKey);
        }
        return $matched;
    }

    public function sendNotification(array $alert, array $data): void
    {
        $channels = $this->decodeJson($alert['alert_channels'] ?? '[]');
        $recipients = $this->decodeJson($alert['alert_recipients'] ?? '[]');
        $level = $alert['alert_level'] ?? 'warning';
        $name = $alert['alert_name'] ?? '未知告警';
        $subject = "[{$level}] {$name} - 系统告警通知";
        $body = "告警名称: {$name}\n告警级别: {$level}\n监控类型: {$alert['monitor_type']}\n监控指标: {$alert['monitor_metric']}\n当前值: " . ($data['current_value'] ?? 'N/A') . "\n触发时间: " . date('Y-m-d H:i:s') . "\n\n请及时处理！";

        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        foreach ($recipients as $email) {
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                Mail::to($email)->subject($subject)->html(nl2br($body));
                            }
                        }
                        break;
                    case 'webhook':
                        $this->sendWebhook($alert, $subject, $body, $data);
                        break;
                    case 'dingtalk':
                        $this->sendDingtalk($alert, $subject, $body);
                        break;
                    case 'feishu':
                        $this->sendFeishu($alert, $subject, $body);
                        break;
                    case 'sms':
                        Log::info("SMS alert sent for: {$name}");
                        break;
                }
            } catch (\Throwable $e) {
                Log::error("sendNotification failed (channel={$channel}): " . $e->getMessage());
            }
        }
    }

    private function collectAllMetrics(SystemMonitorService $monitor): array
    {
        $server = $monitor->getServerStatus();
        $app = $monitor->getApplicationStatus();
        $db = $monitor->getDatabaseStatus();
        $queue = $monitor->getQueueStatus();
        return [
            'cpu'             => $server['cpu']['percent'] ?? 0,
            'memory'          => $server['memory']['percent'] ?? 0,
            'disk'            => $server['disk']['percent'] ?? 0,
            'load_1'          => $server['cpu']['load_1'] ?? 0,
            'connections'     => $db['connections'] ?? 0,
            'slow_queries'    => $db['slow_queries'] ?? 0,
            'queue_pending'   => $queue['total_pending'] ?? 0,
            'opcache_hit_rate' => $app['opcache']['hit_rate'] ?? 0,
        ];
    }

    private function isInCooldown($alert): bool
    {
        $last = $alert->last_triggered;
        if (!$last) {
            return false;
        }
        $cooldownSec = ($alert->cooldown_minutes ?? 30) * 60;
        return (time() - strtotime((string)$last)) < $cooldownSec;
    }

    private function triggerAlert($alert, float $metricValue, array $metrics): void
    {
        $alert->trigger_count = ($alert->trigger_count ?? 0) + 1;
        $alert->last_triggered = date('Y-m-d H:i:s');
        $alert->save();
        $alertData = $alert->toArray();
        $alertData['current_value'] = $metricValue;
        $this->sendNotification($alertData, ['current_value' => $metricValue]);
        Log::warning("Monitor alert triggered: {$alert->alert_name} (metric={$alert->monitor_metric}, value={$metricValue})");
        Cache::clear();
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function sendWebhook(array $alert, string $subject, string $body, array $data): void
    {
        $webhookUrl = $alert['webhook_url'] ?? '';
        if (empty($webhookUrl)) {
            return;
        }
        $payload = json_encode([
            'alert' => $alert['alert_name'] ?? '',
            'level' => $alert['alert_level'] ?? 'warning',
            'subject' => $subject,
            'body' => $body,
            'data' => $data,
            'time' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE);
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendDingtalk(array $alert, string $subject, string $body): void
    {
        $webhookUrl = $alert['dingtalk_webhook'] ?? '';
        if (empty($webhookUrl)) {
            return;
        }
        $payload = json_encode([
            'msgtype' => 'text',
            'text' => ['content' => $subject . "\n\n" . $body],
        ], JSON_UNESCAPED_UNICODE);
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendFeishu(array $alert, string $subject, string $body): void
    {
        $webhookUrl = $alert['feishu_webhook'] ?? '';
        if (empty($webhookUrl)) {
            return;
        }
        $payload = json_encode([
            'msg_type' => 'text',
            'content' => ['text' => $subject . "\n\n" . $body],
        ], JSON_UNESCAPED_UNICODE);
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
