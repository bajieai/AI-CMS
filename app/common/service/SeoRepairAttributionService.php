<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\ContentQualityScore;
use think\facade\Cache;

/**
 * SEO修复效果归因分析 — V2.9.33 OPS-1
 */
class SeoRepairAttributionService
{
    /**
     * 修复效果总览
     */
    public function getOverview(): array
    {
        $totalRepairs = ContentQualityScore::where('repair_count', '>', 0)->count();
        $successRepairs = ContentQualityScore::where('repair_status', 'auto')->count();
        $avgImprove = ContentQualityScore::where('repair_count', '>', 0)->avg('total_score') ?: 0;

        return [
            'total_repairs'     => $totalRepairs,
            'success_repairs'   => $successRepairs,
            'success_rate'      => $totalRepairs > 0 ? round($successRepairs / $totalRepairs * 100, 1) : 0,
            'avg_score_improve' => round($avgImprove, 1),
        ];
    }

    /**
     * 5维度归因分析
     */
    public function getAttribution(): array
    {
        $dimensions = ['completeness', 'readability', 'seo', 'image_match', 'tag_accuracy'];
        $result = [];

        foreach ($dimensions as $dim) {
            $field = $dim . '_score';
            $repaired = ContentQualityScore::where('repair_count', '>', 0)->avg($field) ?: 0;
            $unrepaired = ContentQualityScore::where('repair_count', 0)->avg($field) ?: 0;

            $result[$dim] = [
                'avg_repaired'   => round($repaired, 1),
                'avg_unrepaired' => round($unrepaired, 1),
                'improvement'    => round($repaired - $unrepaired, 1),
            ];
        }

        // 按提升幅度排序
        uasort($result, fn($a, $b) => $b['improvement'] <=> $a['improvement']);
        return $result;
    }

    /**
     * 效果趋势
     */
    public function getTrend(int $days = 30): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $start = strtotime($date);
            $end = $start + 86400;

            $repairs = ContentQualityScore::whereBetweenTime('last_repair_time', $start, $end)->count();
            $success = ContentQualityScore::whereBetweenTime('last_repair_time', $start, $end)->where('repair_status', 'auto')->count();

            $trend[] = [
                'date'      => $date,
                'repairs'   => $repairs,
                'success'   => $success,
                'rate'      => $repairs > 0 ? round($success / $repairs * 100, 1) : 0,
            ];
        }

        return $trend;
    }

    /**
     * 生成归因报告
     */
    public function generateReport(): array
    {
        return [
            'overview'    => $this->getOverview(),
            'attribution' => $this->getAttribution(),
            'trend'       => $this->getTrend(30),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
