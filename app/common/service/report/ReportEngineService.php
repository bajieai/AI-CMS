<?php
declare(strict_types=1);

namespace app\common\service\report;

use app\common\model\ReportDefinition;
use think\facade\Db;
use think\facade\Cache;

class ReportEngineService
{
    private const CACHE_TAG = 'report_engine';

    public function generate(int $reportId, array $params = []): array
    {
        $definition = ReportDefinition::find($reportId);
        if (!$definition) return [];
        $cacheKey = "report_{$reportId}_" . md5(json_encode($params));
        return Cache::remember($cacheKey, function() use ($definition, $params) {
            $data = $this->buildQuery($definition->data_source, $definition->metrics, $definition->dimensions, $params);
            return ['definition' => $definition->toArray(), 'data' => $data, 'chart' => $this->buildChartData($data, $definition->chart_type), 'summary' => $this->buildSummary($data, $definition->metrics)];
        }, 300);
    }

    public function saveReport(array $data, int $id = 0): array
    {
        $data['metrics'] = is_array($data['metrics'] ?? null) ? json_encode($data['metrics']) : $data['metrics'];
        $data['dimensions'] = is_array($data['dimensions'] ?? null) ? json_encode($data['dimensions']) : $data['dimensions'];
        if ($id > 0) ReportDefinition::where('id', $id)->update($data);
        else { $report = new ReportDefinition($data); $report->save(); $id = $report->id; }
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function getList(): array
    {
        try {
            return ReportDefinition::order('id', 'desc')->select()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getReport(int $id): ?array
    {
        try {
            $report = ReportDefinition::find($id);
            return $report ? $report->toArray() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function publishReport(int $id): array
    {
        $report = ReportDefinition::find($id);
        if (!$report) return ['success' => false, 'message' => '报表不存在'];
        $report->is_system = 1;
        $report->save();
        Cache::clear();
        return ['success' => true];
    }

    public function deleteReport(int $id): array
    {
        try {
            $report = ReportDefinition::find($id);
            if (!$report) return ['success' => false, 'message' => '报表不存在'];
            $report->delete();
            Cache::clear();
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildQuery(string $dataSource, string $metricsJson, string $dimensionsJson, array $params): array
    {
        $metrics = json_decode($metricsJson, true) ?: [];
        $tableMap = ['content' => 'content', 'member' => 'member', 'template_store' => 'template_store', 'push_channel' => 'push_channel', 'order' => 'order'];
        $table = $tableMap[$dataSource] ?? 'content';
        $query = Db::name($table);
        $dateRange = $params['date_range'] ?? 'last_30_days';
        if ($dateRange === 'last_7_days') $query->where('create_time', '>=', strtotime('-7 days'));
        elseif ($dateRange === 'last_30_days') $query->where('create_time', '>=', strtotime('-30 days'));
        elseif ($dateRange === 'all_time') {}
        return $query->order('create_time', 'desc')->limit(1000)->select()->toArray();
    }

    private function buildChartData(array $data, string $chartType): array
    {
        return ['type' => $chartType, 'data' => $data];
    }

    private function buildSummary(array $data, string $metricsJson): array
    {
        return ['count' => count($data)];
    }
}
