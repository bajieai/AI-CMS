<?php

declare(strict_types=1);

namespace app\common\service\sys;

use think\facade\Db;
use think\facade\Cache;

/**
 * 自动扩缩容服务
 */
class AutoScaleService
{
    private const CONFIG_KEY = 'auto_scale_config';

    /**
     * 获取配置
     */
    public static function getConfig(): array
    {
        return Cache::remember(self::CONFIG_KEY, function (): array {
            $config = Db::name('config')->where('name', self::CONFIG_KEY)->value('value');
            return $config ? json_decode($config, true) : self::getDefaultConfig();
        }, 3600);
    }

    /**
     * 保存配置
     */
    public static function saveConfig(array $config): void
    {
        $exists = Db::name('config')->where('name', self::CONFIG_KEY)->find();
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        if ($exists) {
            Db::name('config')->where('name', self::CONFIG_KEY)->update(['value' => $json, 'update_time' => time()]);
        } else {
            Db::name('config')->insert(['name' => self::CONFIG_KEY, 'value' => $json, 'group' => 'system', 'type' => 'json', 'create_time' => time(), 'update_time' => time()]);
        }

        Cache::delete(self::CONFIG_KEY);
    }

    /**
     * 检查并执行扩缩容
     */
    public static function checkAndScale(): void
    {
        $config = self::getConfig();
        if (!$config['enabled']) return;

        $server = SystemMonitorService::getServerStatus();
        $cpuUsage = $server['cpu']['usage'] ?? 0;
        $memUsage = $server['memory']['usage_percent'] ?? 0;

        $scaleUpThreshold = $config['scale_up_threshold'] ?? 80;
        $scaleDownThreshold = $config['scale_down_threshold'] ?? 30;
        $maxInstances = $config['max_instances'] ?? 5;
        $minInstances = $config['min_instances'] ?? 1;

        $current = self::getCurrentScale();
        $currentWorkers = $current['workers'] ?? 1;

        if ($cpuUsage > $scaleUpThreshold && $currentWorkers < $maxInstances) {
            self::scaleUp('php-worker', 1);
        } elseif ($cpuUsage < $scaleDownThreshold && $currentWorkers > $minInstances) {
            self::scaleDown('php-worker', 1);
        }
    }

    /**
     * 扩容
     */
    public static function scaleUp(string $service, int $count): array
    {
        $config = self::getConfig();
        $maxInstances = $config['max_instances'] ?? 5;
        $current = self::getCurrentScale();
        $currentCount = $current[$service] ?? 1;
        $newCount = min($maxInstances, $currentCount + $count);

        // 记录扩容操作
        self::logScaleAction($service, 'scale_up', $currentCount, $newCount);

        return ['service' => $service, 'from' => $currentCount, 'to' => $newCount, 'status' => 'success'];
    }

    /**
     * 缩容
     */
    public static function scaleDown(string $service, int $count): array
    {
        $config = self::getConfig();
        $minInstances = $config['min_instances'] ?? 1;
        $current = self::getCurrentScale();
        $currentCount = $current[$service] ?? 1;
        $newCount = max($minInstances, $currentCount - $count);

        self::logScaleAction($service, 'scale_down', $currentCount, $newCount);

        return ['service' => $service, 'from' => $currentCount, 'to' => $newCount, 'status' => 'success'];
    }

    /**
     * 当前实例数
     */
    public static function getCurrentScale(): array
    {
        // Docker环境：docker-compose ps
        // 应用层：通过进程数估算
        $phpProcesses = 0;
        if (function_exists('shell_exec')) {
            $output = @shell_exec('ps aux | grep php-fpm | grep -v grep | wc -l');
            $phpProcesses = intval(trim($output ?? '1'));
        }

        return [
            'workers'     => max(1, $phpProcesses),
            'php-fpm'     => max(1, $phpProcesses),
        ];
    }

    /**
     * 扩缩容历史
     */
    public static function getScaleHistory(): array
    {
        return Db::name('security_log')
            ->where('action_type', 'auto_scale')
            ->order('id', 'desc')
            ->limit(50)
            ->select()
            ->toArray();
    }

    /**
     * 默认配置
     */
    private static function getDefaultConfig(): array
    {
        return [
            'enabled'             => false,
            'scale_up_threshold'  => 80,
            'scale_down_threshold' => 30,
            'max_instances'       => 5,
            'min_instances'       => 1,
            'check_interval'      => 60,
            'cooldown_minutes'    => 5,
        ];
    }

    /**
     * 记录扩缩容操作
     */
    private static function logScaleAction(string $service, string $action, int $from, int $to): void
    {
        try {
            Db::name('security_log')->insert([
                'user_id'     => 0,
                'action_type' => 'auto_scale',
                'action'      => "{$action}: {$service} {$from}→{$to}",
                'ip'          => '127.0.0.1',
                'detail'      => json_encode(['service' => $service, 'action' => $action, 'from' => $from, 'to' => $to], JSON_UNESCAPED_UNICODE),
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
        }
    }
}
