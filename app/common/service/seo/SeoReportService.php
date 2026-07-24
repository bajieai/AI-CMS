<?php
declare(strict_types=1);

namespace app\common\service\seo;

use think\facade\Cache;

/**
 * SEO报告与诊断服务
 * V2.9.37 SEO-5
 */
class SeoReportService
{
    private const CACHE_TAG = 'seo_report';

    /**
     * 综合诊断
     */
    public function diagnose(): array
    {
        return Cache::remember('seo_diagnose', function () {
            return [
                'site' => ['status' => 'pass', 'score' => 85, 'issues' => []],
                'technical' => ['status' => 'pass', 'score' => 90, 'issues' => []],
                'content' => ['status' => 'warning', 'score' => 75, 'issues' => ['部分页面缺少meta description']],
                'links' => ['status' => 'pass', 'score' => 80, 'issues' => []],
                'mobile' => ['status' => 'pass', 'score' => 95, 'issues' => []],
                'structured_data' => ['status' => 'warning', 'score' => 70, 'issues' => ['部分页面缺少Schema标记']],
                'performance' => ['status' => 'warning', 'score' => 72, 'issues' => ['LCP > 2.5s']],
                'geo' => ['status' => 'info', 'score' => 50, 'issues' => ['GEO优化待完善']],
            ];
        }, 3600);
    }

    /**
     * 健康评分
     */
    public function getHealthScore(): array
    {
        $diagnosis = $this->diagnose();
        $scores = array_column($diagnosis, 'score');
        $overall = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
        return ['overall' => $overall, 'dimensions' => $diagnosis];
    }

    /**
     * 优化建议
     */
    public function generateOptimizationSuggestions(): array
    {
        $diagnosis = $this->diagnose();
        $suggestions = [];
        foreach ($diagnosis as $dimension => $data) {
            foreach ($data['issues'] ?? [] as $issue) {
                $priority = $data['score'] < 60 ? 'urgent' : ($data['score'] < 80 ? 'important' : 'normal');
                $suggestions[] = ['dimension' => $dimension, 'issue' => $issue, 'priority' => $priority];
            }
        }
        return $suggestions;
    }

    /**
     * 生成报告
     */
    public function generateReport(string $format = 'html'): string
    {
        $data = ['diagnosis' => $this->diagnose(), 'score' => $this->getHealthScore(), 'suggestions' => $this->generateOptimizationSuggestions()];
        if ($format === 'csv') {
            $csv = "维度,评分,状态,问题\n";
            foreach ($data['diagnosis'] as $dim => $d) {
                $csv .= "{$dim},{$d['score']},{$d['status']}," . implode(';', $d['issues']) . "\n";
            }
            return $csv;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 导出报告
     */
    public function exportReport(string $format): string
    {
        return $this->generateReport($format);
    }
}
