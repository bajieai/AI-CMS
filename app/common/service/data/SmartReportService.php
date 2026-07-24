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

namespace app\common\service\data;

use app\admin\model\DataReport;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 智能报表引擎服务 - V2.9.39 DATA-DEEP-2
 *
 * 功能：报表定义管理 / 报表生成 / AI分析 / 定时发送
 */
class SmartReportService
{
    private const CACHE_TAG = 'smart_report';

    /**
     * 数据源映射
     */
    private array $dataSourceMap = [
        'content'        => ['table' => 'content', 'time_field' => 'create_time'],
        'member'         => ['table' => 'member', 'time_field' => 'create_time'],
        'visit'          => ['table' => 'visit_log', 'time_field' => 'visit_time'],
        'order'          => ['table' => 'paid_content_record', 'time_field' => 'create_time'],
        'ai_log'         => ['table' => 'ai_log', 'time_field' => 'create_time'],
        'template_store' => ['table' => 'template_order', 'time_field' => 'create_time'],
        'comment'        => ['table' => 'comment', 'time_field' => 'create_time'],
        'share'          => ['table' => 'share_log', 'time_field' => 'create_time'],
    ];

    // ========================================================================
    // 报表管理
    // ========================================================================

    /**
     * 获取报表列表
     */
    public function listReports(int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        $query = DataReport::order('id', 'desc');

        if (!empty($filters['report_type'])) {
            $query->where('report_type', $filters['report_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $list  = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'pagesize' => $pageSize,
        ];
    }

    /**
     * 获取单个报表配置
     */
    public function getReport(int $id): ?array
    {
        $report = DataReport::find($id);
        return $report ? $report->toArray() : null;
    }

    /**
     * 创建报表
     */
    public function createReport(array $data): array
    {
        $data['data_config']        = $data['data_config'] ?? [];
        $data['chart_config']       = $data['chart_config'] ?? null;
        $data['schedule_config']    = $data['schedule_config'] ?? null;
        $data['recipients']         = $data['recipients'] ?? null;
        $data['prediction_config']  = $data['prediction_config'] ?? null;
        $data['ai_analysis']        = $data['ai_analysis'] ?? 1;
        $data['status']             = $data['status'] ?? DataReport::STATUS_ACTIVE;

        $report = new DataReport($data);
        $report->save();

        Cache::clear();

        return ['success' => true, 'id' => $report->id];
    }

    /**
     * 更新报表
     */
    public function updateReport(int $id, array $data): array
    {
        $report = DataReport::find($id);
        if (!$report) {
            return ['success' => false, 'msg' => '报表不存在'];
        }

        $report->save($data);
        Cache::clear();

        return ['success' => true];
    }

    /**
     * 删除报表
     */
    public function deleteReport(int $id): array
    {
        $report = DataReport::find($id);
        if (!$report) {
            return ['success' => false, 'msg' => '报表不存在'];
        }

        $report->delete();
        Cache::clear();

        return ['success' => true];
    }

    // ========================================================================
    // 报表生成
    // ========================================================================

    /**
     * 生成报表数据
     */
    public function generateReport(int $reportId, array $params = []): array
    {
        $report = DataReport::find($reportId);
        if (!$report) {
            return ['success' => false, 'msg' => '报表不存在'];
        }

        $cacheKey = "report_data_{$reportId}_" . md5(json_encode($params));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dataConfig = is_array($report->data_config) ? $report->data_config : json_decode($report->data_config, true);
        $dataConfig = $dataConfig ?: [];

        $dataSource = $dataConfig['data_source'] ?? 'content';
        $metrics    = $dataConfig['metrics'] ?? ['count'];
        $dimensions = $dataConfig['dimensions'] ?? [];
        $dateRange  = $params['date_range'] ?? $dataConfig['date_range'] ?? 'last_30_days';

        // 解析时间范围
        [$startTime, $endTime] = $this->parseDateRange($dateRange, $dataConfig);

        // 构建查询
        $data = $this->buildQuery($dataSource, $metrics, $dimensions, $startTime, $endTime, $dataConfig);

        // 构建图表数据
        $chartConfig = is_array($report->chart_config) ? $report->chart_config : json_decode($report->chart_config, true);
        $chartData = $this->buildChartData($data, $chartConfig ?? [], $dimensions);

        // 构建汇总
        $summary = $this->buildSummary($data, $metrics);

        // 更新最后生成时间
        $report->last_generated = date('Y-m-d H:i:s');
        $report->save();

        $result = [
            'success'     => true,
            'report_info' => [
                'id'           => $report->id,
                'name'         => $report->name,
                'report_type'  => $report->report_type,
                'last_generated' => $report->last_generated,
            ],
            'data'        => $data,
            'chart'       => $chartData,
            'summary'     => $summary,
            'date_range'  => [
                'start' => date('Y-m-d', $startTime),
                'end'   => date('Y-m-d', $endTime),
            ],
        ];

        Cache::set($cacheKey, $result, 300);

        return $result;
    }

    // ========================================================================
    // AI分析
    // ========================================================================

    /**
     * 对报表进行AI分析
     */
    public function analyzeWithAi(int $reportId): array
    {
        $report = DataReport::find($reportId);
        if (!$report) {
            return ['success' => false, 'msg' => '报表不存在'];
        }

        // 生成报表数据
        $reportData = $this->generateReport($reportId);
        if (!$reportData['success']) {
            return $reportData;
        }

        // 调用 AI 分析服务
        $aiService = new ReportAiAnalysisService();
        $analysis = $aiService->analyzeReportData($reportData, $report->toArray());

        return [
            'success'  => true,
            'analysis' => $analysis,
        ];
    }

    // ========================================================================
    // 定时发送
    // ========================================================================

    /**
     * 检查并执行定时报表发送
     */
    public function runScheduledReports(): array
    {
        $reports = DataReport::getScheduledReports();
        $results = [];

        foreach ($reports as $report) {
            $scheduleConfig = is_array($report['schedule_config'])
                ? $report['schedule_config']
                : json_decode($report['schedule_config'] ?? '{}', true);

            if (!$this->shouldRunNow($scheduleConfig)) {
                continue;
            }

            try {
                $reportData = $this->generateReport($report['id']);
                if ($reportData['success'] && !empty($scheduleConfig['auto_ai']) && $report['ai_analysis']) {
                    $reportData['ai_analysis'] = $this->analyzeWithAi($report['id']);
                }
                $this->sendReport($report, $reportData);
                $results[] = ['report_id' => $report['id'], 'status' => 'sent'];
            } catch (\Throwable $e) {
                Log::error("定时报表发送失败 #{$report['id']}: " . $e->getMessage());
                $results[] = ['report_id' => $report['id'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * 手动发送报表
     */
    public function sendReportManually(int $reportId, array $recipients = []): array
    {
        $report = DataReport::find($reportId);
        if (!$report) {
            return ['success' => false, 'msg' => '报表不存在'];
        }

        $reportData = $this->generateReport($reportId);
        if (!empty($recipients)) {
            $reportArr = $report->toArray();
            $reportArr['recipients'] = $recipients;
            $report = new DataReport($reportArr);
        }

        return $this->sendReport($report->toArray(), $reportData);
    }

    // ========================================================================
    // 内部方法
    // ========================================================================

    /**
     * 解析时间范围
     */
    private function parseDateRange(string $dateRange, array $dataConfig): array
    {
        $now = time();
        return match ($dateRange) {
            'today'        => [strtotime('today'), $now],
            'yesterday'    => [strtotime('yesterday'), strtotime('today') - 1],
            'last_7_days'  => [strtotime('-7 days'), $now],
            'last_30_days' => [strtotime('-30 days'), $now],
            'this_week'    => [strtotime('monday this week'), $now],
            'last_week'    => [strtotime('monday last week'), strtotime('sunday last week') + 86399],
            'this_month'   => [strtotime(date('Y-m-01')), $now],
            'last_month'   => [strtotime('first day of last month'), strtotime('last day of last month') + 86399],
            'this_year'    => [strtotime(date('Y-01-01')), $now],
            'custom'       => [
                (int) ($dataConfig['custom_start'] ?? strtotime('-30 days')),
                (int) ($dataConfig['custom_end'] ?? time()),
            ],
            default        => [strtotime('-30 days'), $now],
        };
    }

    /**
     * 构建查询
     */
    private function buildQuery(string $dataSource, array $metrics, array $dimensions, int $startTime, int $endTime, array $dataConfig): array
    {
        $sourceConfig = $this->dataSourceMap[$dataSource] ?? $this->dataSourceMap['content'];
        $table  = $sourceConfig['table'];
        $timeField = $sourceConfig['time_field'];

        $query = Db::name($table)
            ->where($timeField, '>=', $startTime)
            ->where($timeField, '<=', $endTime);

        // 应用筛选条件
        if (!empty($dataConfig['filters'])) {
            foreach ($dataConfig['filters'] as $filter) {
                $field = $filter['field'] ?? '';
                $op    = $filter['operator'] ?? '=';
                $val   = $filter['value'] ?? '';
                if (empty($field)) continue;

                match ($op) {
                    '='         => $query->where($field, $val),
                    '!='        => $query->where($field, '<>', $val),
                    '>'         => $query->where($field, '>', $val),
                    '<'         => $query->where($field, '<', $val),
                    '>='        => $query->where($field, '>=', $val),
                    '<='        => $query->where($field, '<=', $val),
                    'in'        => $query->whereIn($field, (array) $val),
                    'not_in'    => $query->whereNotIn($field, (array) $val),
                    'like'      => $query->whereLike($field, "%{$val}%"),
                    'between'   => $query->whereBetween($field, (array) $val),
                    default     => null,
                };
            }
        }

        // 如果有维度分组，按维度聚合
        if (!empty($dimensions)) {
            $dimFields = array_map(fn($d) => $d['field'] ?? $d, $dimensions);
            $selectFields = array_merge($dimFields, []);

            // 添加指标聚合
            foreach ($metrics as $metric) {
                $selectFields[] = $this->buildMetricSelect($metric, $table);
            }

            $query->field($selectFields)
                ->group(implode(',', $dimFields));

            // 排序
            $sortField = $dataConfig['sort_field'] ?? '';
            $sortOrder = $dataConfig['sort_order'] ?? 'desc';
            if ($sortField) {
                $query->order($sortField, $sortOrder);
            }

            $data = $query->limit(1000)->select()->toArray();
        } else {
            // 无分组，计算汇总指标
            $selectFields = [];
            foreach ($metrics as $metric) {
                $selectFields[] = $this->buildMetricSelect($metric, $table);
            }
            $query->field($selectFields);
            $data = $query->find();
            $data = $data ? [$data] : [];
        }

        return $data;
    }

    /**
     * 构建指标查询表达式
     */
    private function buildMetricSelect(string $metric, string $table): string
    {
        return match ($metric) {
            'count'          => 'COUNT(*) as count',
            'sum_views'      => 'SUM(views) as sum_views',
            'avg_views'      => 'AVG(views) as avg_views',
            'sum_amount'     => 'SUM(amount) as sum_amount',
            'avg_amount'     => 'AVG(amount) as avg_amount',
            'distinct_users' => 'COUNT(DISTINCT member_id) as distinct_users',
            'distinct_visitors' => 'COUNT(DISTINCT visitor_id) as distinct_visitors',
            default          => 'COUNT(*) as count',
        };
    }

    /**
     * 构建图表数据
     */
    private function buildChartData(array $data, array $chartConfig, array $dimensions): array
    {
        $chartType = $chartConfig['type'] ?? 'line';

        $labels = [];
        $values = [];

        foreach ($data as $row) {
            if (!empty($dimensions)) {
                $dimField = $dimensions[0]['field'] ?? ($dimensions[0] ?? '');
                $labels[] = (string) ($row[$dimField] ?? '');
            } else {
                $labels[] = '';
            }
            $values[] = $row['count'] ?? $row['sum_views'] ?? $row['sum_amount'] ?? 0;
        }

        return [
            'type'   => $chartType,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $chartConfig['title'] ?? '数据',
                    'data'  => $values,
                    'colors' => $chartConfig['colors'] ?? null,
                ],
            ],
        ];
    }

    /**
     * 构建汇总
     */
    private function buildSummary(array $data, array $metrics): array
    {
        $summary = [];
        foreach ($metrics as $metric) {
            $key = match ($metric) {
                'count'             => 'count',
                'sum_views'         => 'sum_views',
                'avg_views'         => 'avg_views',
                'sum_amount'        => 'sum_amount',
                'avg_amount'        => 'avg_amount',
                'distinct_users'    => 'distinct_users',
                'distinct_visitors' => 'distinct_visitors',
                default             => 'count',
            };

            if (isset($data[0][$key])) {
                $summary[$key] = $data[0][$key];
            } else {
                $total = 0;
                foreach ($data as $row) {
                    $total += $row[$key] ?? 0;
                }
                $summary[$key] = $total;
            }
        }
        $summary['rows'] = count($data);
        return $summary;
    }

    /**
     * 检查是否应该执行
     */
    private function shouldRunNow(array $scheduleConfig): bool
    {
        if (empty($scheduleConfig['enabled'])) {
            return false;
        }

        $frequency = $scheduleConfig['frequency'] ?? 'daily';
        $now = time();

        $lastGenerated = $scheduleConfig['last_run'] ?? 0;
        if (!is_numeric($lastGenerated)) {
            $lastGenerated = strtotime($lastGenerated) ?: 0;
        }

        return match ($frequency) {
            'daily'   => $now - $lastGenerated >= 86400,
            'weekly'  => $now - $lastGenerated >= 604800,
            'monthly' => $now - $lastGenerated >= 2592000,
            'hourly'  => $now - $lastGenerated >= 3600,
            default   => false,
        };
    }

    /**
     * 发送报表
     */
    private function sendReport(array $report, array $reportData): array
    {
        $recipients = is_array($report['recipients'] ?? null)
            ? $report['recipients']
            : json_decode($report['recipients'] ?? '[]', true);

        if (empty($recipients)) {
            return ['success' => false, 'msg' => '无接收人'];
        }

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? '';
            if (empty($email)) continue;

            try {
                // 使用站内通知系统
                $notifyService = new \app\common\service\NotificationService();
                $notifyService->send($recipient['user_id'] ?? 0, [
                    'type'    => 'report',
                    'title'   => "智能报表: {$report['name']}",
                    'content' => $this->formatReportContent($report, $reportData),
                    'url'     => '/admin/smart_report/preview?id=' . $report['id'],
                ]);
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error("报表发送失败 {$email}: " . $e->getMessage());
            }
        }

        return ['success' => true, 'sent' => $sentCount];
    }

    /**
     * 格式化报表内容
     */
    private function formatReportContent(array $report, array $reportData): string
    {
        $content = "报表名称: {$report['name']}\n";
        $content .= "报表类型: {$report['report_type']}\n";
        $content .= "生成时间: " . ($reportData['report_info']['last_generated'] ?? date('Y-m-d H:i:s')) . "\n\n";

        if (!empty($reportData['summary'])) {
            $content .= "数据汇总:\n";
            foreach ($reportData['summary'] as $key => $val) {
                $content .= "  - {$key}: {$val}\n";
            }
        }

        return $content;
    }

    /**
     * 清除报表缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
    }
}
