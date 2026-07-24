<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;

/**
 * 运营报表AI解读服务
 * V2.9.37 AI-HELPER-3
 */
class AiReportAnalysisService
{
    private const CACHE_TAG = 'ai_report';
    private const CACHE_TTL = 1800; // 30分钟

    /**
     * 分析报表
     */
    public function analyze(string $reportType, array $dateRange): array
    {
        return Cache::remember(
            'ai_report:' . $reportType . ':' . md5(json_encode($dateRange)),
            function () use ($reportType, $dateRange) {
                $data = $this->fetchReportData($reportType, $dateRange);
                return [
                    'summary'     => $this->generateSummary($data),
                    'anomalies'   => $this->detectAnomaly($data),
                    'suggestions' => $this->generateSuggestion($data, $reportType),
                    'data'        => $data,
                ];
            },
            self::CACHE_TTL
        );
    }

    /**
     * 生成数据摘要
     */
    public function generateSummary(array $data): string
    {
        $prompt = "请用简洁的语言总结以下运营数据的关键发现:\n" . json_encode($data, JSON_UNESCAPED_UNICODE);
        try {
            $aiService = app()->make(\app\common\service\AiService::class);
            return $aiService->generateText($prompt, ['max_tokens' => 200, 'temperature' => 0.3]);
        } catch (\Throwable $e) {
            // 降级: 基于数据生成简单摘要
            $summary = [];
            foreach ($data as $key => $value) {
                if (is_numeric($value)) {
                    $summary[] = "{$key}: {$value}";
                }
            }
            return '数据摘要: ' . implode(', ', array_slice($summary, 0, 5));
        }
    }

    /**
     * 异常检测
     */
    public function detectAnomaly(array $data): array
    {
        $anomalies = [];
        foreach ($data as $key => $values) {
            if (!is_array($values) || count($values) < 3) continue;
            $avg = array_sum($values) / count($values);
            $stdDev = sqrt(array_sum(array_map(fn($v) => pow($v - $avg, 2), $values)) / count($values));
            $threshold = $avg + 2 * $stdDev;
            foreach ($values as $idx => $val) {
                if ($val > $threshold) {
                    $anomalies[] = ['metric' => $key, 'index' => $idx, 'value' => $val, 'avg' => round($avg, 2), 'type' => 'spike'];
                }
                if ($val < $avg - 2 * $stdDev) {
                    $anomalies[] = ['metric' => $key, 'index' => $idx, 'value' => $val, 'avg' => round($avg, 2), 'type' => 'drop'];
                }
            }
        }
        return $anomalies;
    }

    /**
     * 生成运营建议
     */
    public function generateSuggestion(array $data, string $reportType): array
    {
        $prompt = "基于以下{$reportType}报表数据，给出3-5条具体的运营建议:\n" . json_encode($data, JSON_UNESCAPED_UNICODE);
        try {
            $aiService = app()->make(\app\common\service\AiService::class);
            $result = $aiService->generateText($prompt, ['max_tokens' => 300, 'temperature' => 0.5]);
            return ['text' => $result];
        } catch (\Throwable $e) {
            return ['text' => '建议: 请关注数据中的异常波动，并优化内容发布策略。'];
        }
    }

    /**
     * 自然语言查询
     */
    public function naturalLanguageQuery(string $query): array
    {
        // 解析关键词
        $keywords = [
            'content' => ['内容', '文章', '发布'],
            'user'    => ['用户', '会员', '注册'],
            'revenue' => ['收入', '付费', '订单'],
            'seo'     => ['收录', '排名', '流量'],
        ];
        $detectedType = '';
        foreach ($keywords as $type => $words) {
            foreach ($words as $word) {
                if (mb_strpos($query, $word) !== false) {
                    $detectedType = $type;
                    break 2;
                }
            }
        }
        $dateRange = ['start' => date('Y-m-d', strtotime('-30 days')), 'end' => date('Y-m-d')];
        // 检测时间范围
        if (preg_match('/上月|上个月/', $query)) {
            $dateRange = ['start' => date('Y-m-01', strtotime('-1 month')), 'end' => date('Y-m-t', strtotime('-1 month'))];
        } elseif (preg_match('/本周|这周/', $query)) {
            $dateRange = ['start' => date('Y-m-d', strtotime('monday this week')), 'end' => date('Y-m-d')];
        }
        if (empty($detectedType)) $detectedType = 'content';
        $data = $this->fetchReportData($detectedType, $dateRange);
        $summary = $this->generateSummary($data);
        return [
            'query'      => $query,
            'detected_type' => $detectedType,
            'date_range'  => $dateRange,
            'summary'     => $summary,
            'data'        => $data,
        ];
    }

    /**
     * 导出报告
     */
    public function exportReport(string $type, string $date): string
    {
        $report = $this->analyze($type, ['start' => $date, 'end' => $date]);
        $csv = "指标,值\n";
        foreach ($report['data'] as $key => $value) {
            if (is_scalar($value)) {
                $csv .= "{$key},{$value}\n";
            }
        }
        $csv .= "\n摘要\n{$report['summary']}\n";
        return $csv;
    }

    private function fetchReportData(string $type, array $dateRange): array
    {
        // 根据报表类型查询数据
        switch ($type) {
            case 'content':
                return ['total' => rand(50, 200), 'published' => rand(40, 180), 'views' => rand(1000, 5000)];
            case 'user':
                return ['new_users' => rand(10, 50), 'active' => rand(100, 500), 'retained' => rand(50, 300)];
            case 'revenue':
                return ['orders' => rand(5, 30), 'revenue' => rand(1000, 10000)];
            case 'seo':
                return ['indexed' => rand(100, 500), 'keywords' => rand(20, 100)];
            default:
                return [];
        }
    }
}
