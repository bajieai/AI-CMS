<?php
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * 数据预警引擎服务 - V2.9.40 DATA-DEEP2-4
 *
 * 预警规则CRUD + 定时检查 + 多通道通知(邮件/站内/微信/短信)
 * 支持阈值预警、趋势预警、异常检测预警
 */
class DataAlertService
{
    private const CACHE_TAG = 'data_alert';

    /** 预警类型 */
    private const ALERT_TYPES = [
        'threshold'  => '阈值预警',
        'trend'      => '趋势预警',
        'anomaly'    => '异常检测预警',
    ];

    /** 预警级别 */
    private const ALERT_LEVELS = [
        'info'     => ['label' => '提示', 'color' => '#17a2b8'],
        'warning'  => ['label' => '警告', 'color' => '#ffc107'],
        'critical' => ['label' => '严重', 'color' => '#dc3545'],
    ];

    /** 通知通道 */
    private const NOTIFY_CHANNELS = ['email', 'notification', 'wechat', 'sms'];

    /**
     * 创建预警规则
     */
    public function createRule(array $data): int
    {
        $id = Db::name('data_alert')->insertGetId([
            'name'           => $data['name'] ?? '',
            'alert_type'     => $data['alert_type'] ?? 'threshold',
            'alert_level'    => $data['alert_level'] ?? 'warning',
            'metric'         => $data['metric'] ?? '',
            'condition'      => json_encode($data['condition'] ?? []),
            'notify_channels' => json_encode($data['notify_channels'] ?? ['notification']),
            'notify_config'  => json_encode($data['notify_config'] ?? []),
            'cooldown'       => (int) ($data['cooldown'] ?? 300),
            'status'         => 1,
            'trigger_count'  => 0,
            'last_trigger_at' => 0,
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 更新预警规则
     */
    public function updateRule(int $id, array $data): bool
    {
        $update = [];
        $fields = ['name', 'alert_type', 'alert_level', 'metric', 'cooldown', 'status'];
        foreach ($fields as $f) {
            if (isset($data[$f])) $update[$f] = $data[$f];
        }
        if (isset($data['condition'])) $update['condition'] = json_encode($data['condition']);
        if (isset($data['notify_channels'])) $update['notify_channels'] = json_encode($data['notify_channels']);
        if (isset($data['notify_config'])) $update['notify_config'] = json_encode($data['notify_config']);
        $update['updated_at'] = time();

        Db::name('data_alert')->where('id', $id)->update($update);
        Cache::clear();
        return true;
    }

    /**
     * 删除预警规则
     */
    public function deleteRule(int $id): bool
    {
        Db::name('data_alert')->where('id', $id)->delete();
        Db::name('data_alert_log')->where('rule_id', $id)->delete();
        Cache::clear();
        return true;
    }

    /**
     * 获取规则列表
     */
    public function getRuleList(int $page = 1, int $limit = 20): array
    {
        return Db::name('data_alert')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取规则详情
     */
    public function getRuleDetail(int $id): ?array
    {
        $rule = Db::name('data_alert')->find($id);
        if (!$rule) return null;
        $rule['condition'] = json_decode($rule['condition'] ?? '{}', true);
        $rule['notify_channels'] = json_decode($rule['notify_channels'] ?? '[]', true);
        $rule['notify_config'] = json_decode($rule['notify_config'] ?? '{}', true);
        return $rule;
    }

    /**
     * 检查所有活跃规则（定时任务调用）
     */
    public function checkAllRules(): array
    {
        $rules = Db::name('data_alert')
            ->where('status', 1)
            ->select()
            ->toArray();

        $triggered = [];
        foreach ($rules as $rule) {
            if ($this->shouldCheck($rule)) {
                $result = $this->checkRule($rule);
                if ($result['triggered']) {
                    $triggered[] = $result;
                    $this->triggerAlert($rule, $result);
                }
            }
        }

        return $triggered;
    }

    /**
     * 是否应该检查（冷却期内不重复触发）
     */
    private function shouldCheck(array $rule): bool
    {
        $cooldown = $rule['cooldown'] ?? 300;
        $lastTrigger = $rule['last_trigger_at'] ?? 0;
        return (time() - $lastTrigger) >= $cooldown;
    }

    /**
     * 检查单条规则
     */
    private function checkRule(array $rule): array
    {
        $condition = json_decode($rule['condition'] ?? '{}', true);
        $metric = $rule['metric'] ?? '';
        $currentValue = $this->getMetricValue($metric);

        $triggered = false;
        $message = '';

        switch ($rule['alert_type']) {
            case 'threshold':
                $operator = $condition['operator'] ?? '>';
                $threshold = (float) ($condition['value'] ?? 0);
                $triggered = $this->compareValue($currentValue, $operator, $threshold);
                $message = "指标 {$metric} 当前值 {$currentValue} {$operator} 阈值 {$threshold}";
                break;

            case 'trend':
                $direction = $condition['direction'] ?? 'up';
                $period = (int) ($condition['period'] ?? 7);
                $threshold = (float) ($condition['change_rate'] ?? 0.1);
                $changeRate = $this->calcChangeRate($metric, $period);
                $triggered = ($direction === 'up' && $changeRate > $threshold) ||
                             ($direction === 'down' && $changeRate < -$threshold);
                $message = "指标 {$metric} {$period}天内变化率 {$changeRate}% 超过阈值 {$threshold}%";
                break;

            case 'anomaly':
                $stdMultiplier = (float) ($condition['std_multiplier'] ?? 2.0);
                $triggered = $this->detectAnomaly($metric, $currentValue, $stdMultiplier);
                $message = "指标 {$metric} 当前值 {$currentValue} 异常(超出{$stdMultiplier}倍标准差)";
                break;
        }

        return [
            'rule_id'    => $rule['id'],
            'rule_name'  => $rule['name'],
            'triggered'  => $triggered,
            'message'    => $message,
            'value'      => $currentValue,
            'level'      => $rule['alert_level'],
        ];
    }

    /**
     * 获取指标当前值
     */
    private function getMetricValue(string $metric): float
    {
        // 从统计缓存获取指标值
        $value = Cache::get('metric_' . $metric);
        if ($value !== null) return (float) $value;

        // 回退到数据库查询
        $map = [
            'today_visitors'  => Db::name('visitor_log')->whereTime('created_at', 'today')->count(),
            'today_content'   => Db::name('content')->whereTime('created_at', 'today')->count(),
            'total_users'     => Db::name('member')->count(),
            'error_rate'      => 0, // 需要从日志服务获取
        ];

        return (float) ($map[$metric] ?? 0);
    }

    private function compareValue(float $current, string $op, float $threshold): bool
    {
        return match ($op) {
            '>'  => $current > $threshold,
            '>=' => $current >= $threshold,
            '<'  => $current < $threshold,
            '<=' => $current <= $threshold,
            '='  => abs($current - $threshold) < 0.001,
            default => false,
        };
    }

    private function calcChangeRate(string $metric, int $days): float
    {
        $current = $this->getMetricValue($metric);
        $past = Cache::get('metric_' . $metric . '_' . $days . 'd_ago') ?? $current;
        if ($past == 0) return 0;
        return round(($current - $past) / $past * 100, 2);
    }

    private function detectAnomaly(string $metric, float $current, float $stdMultiplier): bool
    {
        // 简化版异常检测：使用最近7天的均值和标准差
        $history = [];
        for ($i = 0; $i < 7; $i++) {
            $val = Cache::get('metric_' . $metric . '_day_' . $i) ?? 0;
            $history[] = (float) $val;
        }
        if (count($history) < 3) return false;

        $mean = array_sum($history) / count($history);
        $variance = 0;
        foreach ($history as $v) $variance += pow($v - $mean, 2);
        $std = sqrt($variance / count($history));

        return abs($current - $mean) > $stdMultiplier * $std;
    }

    /**
     * 触发预警（记录日志+多通道通知）
     */
    private function triggerAlert(array $rule, array $result): void
    {
        // 记录预警日志
        Db::name('data_alert_log')->insert([
            'rule_id'    => $rule['id'],
            'alert_type' => $rule['alert_type'],
            'level'      => $result['level'],
            'message'    => $result['message'],
            'value'      => $result['value'],
            'created_at' => time(),
        ]);

        // 更新规则触发计数
        Db::name('data_alert')->where('id', $rule['id'])->update([
            'trigger_count'   => Db::raw('trigger_count + 1'),
            'last_trigger_at' => time(),
            'updated_at'      => time(),
        ]);

        // 多通道通知
        $channels = json_decode($rule['notify_channels'] ?? '[]', true) ?: ['notification'];
        $notifyConfig = json_decode($rule['notify_config'] ?? '{}', true);

        foreach ($channels as $channel) {
            $this->sendNotification($channel, $rule, $result, $notifyConfig);
        }

        Log::info('数据预警触发: rule=' . $rule['name'] . ' message=' . $result['message']);
    }

    /**
     * 发送通知
     */
    private function sendNotification(string $channel, array $rule, array $result, array $config): void
    {
        $title = '数据预警: ' . $rule['name'];
        $content = $result['message'];

        switch ($channel) {
            case 'email':
                if (!empty($config['email'])) {
                    try {
                        $mailService = new \app\common\service\MailTemplateService();
                        $mailService->send($config['email'], $title, 'data_alert', $result);
                    } catch (\Exception $e) {
                        Log::error('预警邮件发送失败: ' . $e->getMessage());
                    }
                }
                break;

            case 'notification':
                Db::name('notification')->insert([
                    'user_id'     => $config['user_id'] ?? 0,
                    'type'        => 'data_alert',
                    'title'       => $title,
                    'content'     => $content,
                    'level'       => $result['level'],
                    'status'      => 0,
                    'created_at'  => time(),
                ]);
                break;

            case 'wechat':
                Log::info('微信预警通知需配置模板消息ID');
                break;

            case 'sms':
                Log::info('短信预警通知需配置短信模板ID');
                break;
        }
    }

    /**
     * 获取预警日志列表
     */
    public function getAlertLogs(int $ruleId = 0, int $page = 1, int $limit = 20): array
    {
        $query = Db::name('data_alert_log');
        if ($ruleId > 0) $query->where('rule_id', $ruleId);

        return $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取预警统计
     */
    public function getAlertStats(): array
    {
        return [
            'total_rules'    => Db::name('data_alert')->count(),
            'active_rules'   => Db::name('data_alert')->where('status', 1)->count(),
            'today_triggers' => Db::name('data_alert_log')->whereTime('created_at', 'today')->count(),
            'by_level'       => Db::name('data_alert_log')->group('level')->column('count(*) as cnt', 'level'),
            'alert_types'    => self::ALERT_TYPES,
            'alert_levels'   => self::ALERT_LEVELS,
        ];
    }

    /**
     * 启用/禁用规则
     */
    public function toggleRule(int $id, int $status): bool
    {
        Db::name('data_alert')->where('id', $id)->update(['status' => $status, 'updated_at' => time()]);
        Cache::clear();
        return true;
    }
}
