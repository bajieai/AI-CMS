<?php
declare(strict_types=1);

namespace app\common\service\plugin;

use app\common\model\Plugin;
use app\common\model\PluginVersion;
use think\facade\Cache;

/**
 * 插件数据统计服务
 * V2.9.37 PLUG-ECO-3
 */
class PluginStatsService
{
    private const CACHE_TAG = 'plugin_stats';

    public function getInstallStats(int $pluginId): array
    {
        return Cache::remember('plugin_install:' . $pluginId, function () use ($pluginId) {
            $plugin = Plugin::find($pluginId);
            return [
                'total_installs'   => $plugin['install_count'] ?? 0,
                'active_installs'  => ($plugin['install_count'] ?? 0) - ($plugin['uninstall_count'] ?? 0),
                'uninstall_count'  => $plugin['uninstall_count'] ?? 0,
            ];
        }, 300);
    }

    public function getUsageStats(int $pluginId): array
    {
        return ['usage_frequency' => 0, 'avg_duration' => 0, 'peak_hours' => []];
    }

    public function getPerformanceStats(int $pluginId): array
    {
        return ['avg_response_time' => 0, 'error_rate' => 0, 'memory_usage' => 0];
    }

    public function getErrorStats(int $pluginId): array
    {
        return ['total_errors' => 0, 'by_type' => [], 'recent' => []];
    }

    public function getDashboard(int $pluginId): array
    {
        return [
            'install'    => $this->getInstallStats($pluginId),
            'usage'      => $this->getUsageStats($pluginId),
            'performance' => $this->getPerformanceStats($pluginId),
            'errors'     => $this->getErrorStats($pluginId),
        ];
    }

    public function exportStats(int $pluginId, string $type): string
    {
        $data = match ($type) {
            'install'    => $this->getInstallStats($pluginId),
            'usage'      => $this->getUsageStats($pluginId),
            'performance' => $this->getPerformanceStats($pluginId),
            'errors'     => $this->getErrorStats($pluginId),
            default      => $this->getDashboard($pluginId),
        };
        $csv = "指标,值\n";
        foreach ($data as $key => $value) {
            if (is_scalar($value)) $csv .= "{$key},{$value}\n";
        }
        return $csv;
    }
}
