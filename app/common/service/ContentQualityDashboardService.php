<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\ContentQualityScore;
use think\facade\Cache;

/**
 * 内容质量看板 — V2.9.33 AI5-5
 * 看板数据缓存5分钟（确保实时性）
 */
class ContentQualityDashboardService
{
    private const CACHE_TAG = 'content_quality';
    private const CACHE_TTL = 300; // 5分钟

    /**
     * 质量概览
     */
    public function getOverview(): array
    {
        return Cache::remember('dashboard_overview', function () {
            $total = Content::count();
            $scored = ContentQualityScore::count();
            $avgScore = ContentQualityScore::avg('total_score') ?: 0;

            $levelDist = [
                'excellent' => Content::where('quality_level', 'excellent')->count(),
                'good'      => Content::where('quality_level', 'good')->count(),
                'fair'      => Content::where('quality_level', 'fair')->count(),
                'poor'      => Content::where('quality_level', 'poor')->count(),
                'unscored'  => Content::where('quality_level', 'unscored')->orWhereNull('quality_level')->count(),
            ];

            $todayStart = strtotime(date('Y-m-d'));
            $todayNew = Content::where('create_time', '>=', $todayStart)->count();
            $todayScored = ContentQualityScore::where('create_time', '>=', $todayStart)->count();

            return [
                'total_content'   => $total,
                'scored_content'  => $scored,
                'avg_score'       => round($avgScore, 1),
                'level_distribution' => $levelDist,
                'today_new'       => $todayNew,
                'today_scored'    => $todayScored,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 质量趋势（最近N天）
     */
    public function getTrend(int $days = 30): array
    {
        return Cache::remember('dashboard_trend_' . $days, function () use ($days) {
            $trend = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $start = strtotime($date);
                $end = $start + 86400;

                $avg = ContentQualityScore::whereBetweenTime('create_time', $start, $end)->avg('total_score');
                $count = ContentQualityScore::whereBetweenTime('create_time', $start, $end)->count();

                $trend[] = ['date' => $date, 'avg_score' => round($avg ?: 0, 1), 'count' => $count];
            }
            return $trend;
        }, self::CACHE_TTL);
    }

    /**
     * 各维度评分分布
     */
    public function getDimensionDistribution(): array
    {
        return Cache::remember('dashboard_dimensions', function () {
            return [
                'completeness' => $this->getDimensionStats('completeness_score'),
                'readability'  => $this->getDimensionStats('readability_score'),
                'seo'          => $this->getDimensionStats('seo_score'),
                'image_match'  => $this->getDimensionStats('image_match_score'),
                'tag_accuracy' => $this->getDimensionStats('tag_accuracy_score'),
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 低分内容TOP10
     */
    public function getLowScoreTop10(): array
    {
        return Cache::remember('dashboard_low_top10', function () {
            return ContentQualityScore::with(['content'])
                ->order('total_score', 'asc')
                ->limit(10)
                ->select()
                ->toArray();
        }, self::CACHE_TTL);
    }

    /**
     * 高频问题类型统计
     */
    public function getHighFrequencyIssues(): array
    {
        return Cache::remember('dashboard_issues', function () {
            $issues = ['completeness' => 0, 'readability' => 0, 'seo' => 0, 'image_match' => 0, 'tag_accuracy' => 0];

            $records = ContentQualityScore::where('total_score', '<', 60)->select();
            foreach ($records as $record) {
                $suggestions = json_decode($record->suggestions ?: '[]', true);
                foreach ($suggestions as $s) {
                    $dim = $s['dimension'] ?? '';
                    if (isset($issues[$dim])) $issues[$dim]++;
                }
            }

            arsort($issues);
            return $issues;
        }, self::CACHE_TTL);
    }

    /**
     * 导出数据
     */
    public function exportData(string $format = 'excel'): array
    {
        $data = ContentQualityScore::with(['content'])
            ->order('total_score', 'asc')
            ->limit(1000)
            ->select()
            ->toArray();

        return ['format' => $format, 'count' => count($data), 'data' => $data];
    }

    private function getDimensionStats(string $field): array
    {
        return [
            'avg'   => round(ContentQualityScore::avg($field) ?: 0, 1),
            'high'  => ContentQualityScore::where($field, '>=', 80)->count(),
            'mid'   => ContentQualityScore::whereBetween($field, [60, 79])->count(),
            'low'   => ContentQualityScore::where($field, '<', 60)->count(),
        ];
    }
}
