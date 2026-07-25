<?php
declare(strict_types=1);

namespace app\common\service\mini;

use app\common\model\MiniStats;
use think\facade\Cache;
use think\facade\Db;

/**
 * 移动端数据统计服务
 * V2.9.37 MINI-FULL-5
 */
class MiniStatsService
{
    private const CACHE_TAG = 'mini_stats';

    /**
     * 记录行为事件
     */
    public function recordEvent(string $type, array $data): bool
    {
        $today = date('Y-m-d');
        $pageType = $data['page_type'] ?? '';
        $pagePath = $data['page_path'] ?? '';
        $platform = $data['platform'] ?? 'mini';
        $metricName = $data['metric_name'] ?? $type;
        $metricValue = $data['metric_value'] ?? 1;

        // 使用UPSERT逻辑(INSERT ON DUPLICATE KEY UPDATE)
        try {
            $existing = MiniStats::where('stats_date', $today)
                ->where('stats_type', $type)
                ->where('page_type', $pageType)
                ->where('page_path', $pagePath)
                ->where('platform', $platform)
                ->where('metric_name', $metricName)
                ->find();

            if ($existing) {
                $existing->metric_value += $metricValue;
                $existing->save();
            } else {
                MiniStats::create([
                    'stats_date'   => $today,
                    'stats_type'   => $type,
                    'page_type'    => $pageType,
                    'page_path'    => $pagePath,
                    'platform'     => $platform,
                    'metric_name'  => $metricName,
                    'metric_value' => $metricValue,
                    'metric_data'  => $data['extra'] ?? null,
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 概览数据(5分钟缓存)
     */
    public function getOverview(string $startDate, string $endDate): array
    {
        return Cache::remember(
            'mini_overview:' . $startDate . ':' . $endDate,
            function () use ($startDate, $endDate) {
                $base = MiniStats::whereBetween('stats_date', [$startDate, $endDate]);
                return [
                    'page_views'   => (clone $base)->where('stats_type', 'page_view')->sum('metric_value'),
                    'visitors'     => (clone $base)->where('stats_type', 'visitor')->sum('metric_value'),
                    'new_users'    => (clone $base)->where('stats_type', 'new_user')->sum('metric_value'),
                    'avg_duration' => (clone $base)->where('stats_type', 'duration')->avg('metric_value'),
                    'bounce_rate'  => (clone $base)->where('stats_type', 'bounce')->avg('metric_value'),
                ];
            },
            300
        );
    }

    /**
     * 访客统计
     */
    public function getVisitorStats(string $date): array
    {
        return Cache::remember(
            'mini_visitors:' . $date,
            function () use ($date) {
                $rows = MiniStats::where('stats_date', $date)
                    ->where('stats_type', 'visitor')
                    ->select()
                    ->toArray();
                return [
                    'total'   => array_sum(array_column($rows, 'metric_value')),
                    'by_platform' => $this->groupBy($rows, 'platform'),
                    'by_page' => $this->groupBy($rows, 'page_type'),
                ];
            },
            300
        );
    }

    /**
     * 页面访问排行
     */
    public function getPageRank(int $limit = 20): array
    {
        return Cache::remember(
            'mini_page_rank:' . $limit,
            function () use ($limit) {
                return MiniStats::where('stats_type', 'page_view')
                    ->where('stats_date', '>=', date('Y-m-d', strtotime('-7 days')))
                    ->field('page_type, page_path, SUM(metric_value) as total')
                    ->group('page_type, page_path')
                    ->order('total', 'desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();
            },
            300
        );
    }

    /**
     * 转化漏斗
     */
    public function getFunnel(string $type): array
    {
        $funnels = [
            'view_to_favorite' => ['page_view', 'favorite'],
            'view_to_comment'  => ['page_view', 'comment'],
            'view_to_share'    => ['page_view', 'share'],
            'search_to_view'   => ['search', 'page_view'],
        ];
        $steps = $funnels[$type] ?? [];
        if (empty($steps)) {
            return [];
        }

        $result = [];
        foreach ($steps as $step) {
            $result[$step] = MiniStats::where('stats_type', $step)
                ->where('stats_date', '>=', date('Y-m-d', strtotime('-30 days')))
                ->sum('metric_value');
        }
        // 计算转化率
        $values = array_values($result);
        $rates = [];
        for ($i = 1; $i < count($values); $i++) {
            $rates[$i] = $values[$i - 1] > 0 ? round($values[$i] / $values[$i - 1] * 100, 2) : 0;
        }
        return ['steps' => $result, 'conversion_rates' => $rates];
    }

    /**
     * 用户留存
     */
    public function getRetention(string $type): array
    {
        $days = ['next_day' => 1, 'day7' => 7, 'day30' => 30];
        $interval = $days[$type] ?? 1;
        $today = date('Y-m-d');
        $pastDate = date('Y-m-d', strtotime("-{$interval} days"));

        $baseUsers = MiniStats::where('stats_date', $pastDate)
            ->where('stats_type', 'new_user')
            ->sum('metric_value');
        $retained = MiniStats::where('stats_date', $today)
            ->where('stats_type', 'returning_user')
            ->where('metric_data->source_date', $pastDate)
            ->sum('metric_value');

        return [
            'base_users'   => $baseUsers,
            'retained'     => $retained,
            'retention_rate' => $baseUsers > 0 ? round($retained / $baseUsers * 100, 2) : 0,
        ];
    }

    /**
     * 导出报告
     */
    public function exportReport(string $type, string $date): string
    {
        $data = match ($type) {
            'overview' => $this->getOverview($date, $date),
            'visitors' => $this->getVisitorStats($date),
            'page_rank' => $this->getPageRank(100),
            default => [],
        };
        // CSV格式
        $csv = '';
        if (!empty($data)) {
            $headers = array_keys($data[0] ?? $data);
            $csv = implode(',', $headers) . "\n";
            foreach (($data[0] ?? null) ? $data : [$data] as $row) {
                $csv .= implode(',', array_map(fn($v) => is_scalar($v) ? $v : json_encode($v), $row)) . "\n";
            }
        }
        return $csv;
    }

    private function groupBy(array $rows, string $field): array
    {
        $result = [];
        foreach ($rows as $row) {
            $key = $row[$field] ?? 'unknown';
            $result[$key] = ($result[$key] ?? 0) + $row['metric_value'];
        }
        return $result;
    }
}
