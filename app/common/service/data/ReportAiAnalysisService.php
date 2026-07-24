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

use app\common\service\ai\AiProviderFactory;
use app\common\service\ai\AiProviderInterface;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI报表分析服务 - V2.9.39 DATA-DEEP-2
 *
 * 功能：数据解读 / 异常检测 / 归因分析 / 建议生成
 * 调用 DeepSeek（或当前默认AI Provider）分析数据，不使用 rand() 模拟。
 */
class ReportAiAnalysisService
{
    private const CACHE_TAG = 'report_ai_analysis';
    private const CACHE_TTL = 1800; // 30分钟

    protected ?AiProviderInterface $provider = null;

    public function __construct(?AiProviderInterface $provider = null)
    {
        $this->provider = $provider;
    }

    protected function getProvider(): AiProviderInterface
    {
        if ($this->provider !== null) {
            return $this->provider;
        }
        $this->provider = AiProviderFactory::getDefault();
        return $this->provider;
    }

    /**
     * 分析报表数据（主入口）
     */
    public function analyzeReportData(array $reportData, array $reportInfo = []): array
    {
        $cacheKey = 'ai_analysis_' . md5(json_encode([
            $reportInfo['id'] ?? 0,
            $reportData['date_range'] ?? [],
            $reportData['summary'] ?? [],
        ]));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $analysis = [
            'interpretation'  => $this->generateInterpretation($reportData, $reportInfo),
            'anomalies'       => $this->detectAnomalies($reportData),
            'attribution'     => $this->analyzeAttribution($reportData),
            'recommendations' => $this->generateRecommendations($reportData, $reportInfo),
        ];

        Cache::set($cacheKey, $analysis, self::CACHE_TTL);
        return $analysis;
    }

    /**
     * 数据解读 — AI 将数据转化为自然语言描述
     */
    public function generateInterpretation(array $reportData, array $reportInfo): array
    {
        $summary = $reportData['summary'] ?? [];
        $chartData = $reportData['chart'] ?? [];
        $reportName = $reportInfo['name'] ?? '未知报表';
        $reportType = $reportInfo['report_type'] ?? 'custom';

        $typeMap = [
            'daily' => '日报', 'weekly' => '周报', 'monthly' => '月报',
            'quarterly' => '季报', 'yearly' => '年报', 'compare' => '对比分析',
            'trend' => '趋势分析', 'anomaly' => '异常检测', 'target' => '目标达成',
        ];
        $typeText = $typeMap[$reportType] ?? '自定义报表';

        $dataSummary = $this->prepareDataSummary($summary, $chartData);

        $prompt = "你是一位专业的数据分析专家。请对以下报表数据进行解读分析。\n\n"
            . "报表名称：{$reportName}\n报表类型：{$typeText}\n"
            . "时间范围：{$reportData['date_range']['start']} 至 {$reportData['date_range']['end']}\n\n"
            . "数据摘要：\n{$dataSummary}\n\n"
            . "请用中文进行数据解读，包含：1.整体概况 2.关键发现 3.趋势分析。简洁专业，不要多余格式标记。";

        try {
            $aiResult = $this->getProvider()->write($prompt, [
                'system_prompt' => '你是一位资深数据分析师，擅长从数据中发现洞察并给出专业解读。请基于实际数据进行分析，不要编造数据。',
                'max_tokens'    => 1500,
                'temperature'   => 0.3,
            ]);
            return [
                'overview'     => $aiResult,
                'data_summary' => $summary,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            Log::error('AI报表解读失败: ' . $e->getMessage());
            return [
                'overview'     => 'AI分析暂时不可用，请稍后重试。',
                'error'        => $e->getMessage(),
                'data_summary' => $summary,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * 异常检测 — 3-sigma + IQR 统计方法 + AI原因分析
     */
    public function detectAnomalies(array $reportData): array
    {
        $chartData = $reportData['chart'] ?? [];
        $values    = $chartData['datasets'][0]['data'] ?? [];
        $labels    = $chartData['labels'] ?? [];

        if (count($values) < 3) {
            return ['anomalies' => [], 'method' => 'insufficient_data', 'detected_at' => date('Y-m-d H:i:s')];
        }

        $statAnomalies = $this->statisticalAnomalyDetection($values, $labels);

        if (!empty($statAnomalies)) {
            $aiInsights = $this->aiAnomalyAnalysis($statAnomalies);
            foreach ($statAnomalies as &$anomaly) {
                $anomaly['ai_analysis'] = $aiInsights[$anomaly['label']] ?? '';
            }
        }

        return [
            'anomalies'   => $statAnomalies,
            'total'       => count($statAnomalies),
            'method'      => '3sigma_iqr',
            'detected_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 统计异常检测（3-sigma + IQR）
     */
    private function statisticalAnomalyDetection(array $values, array $labels): array
    {
        $n = count($values);
        $mean = array_sum($values) / $n;
        $variance = 0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $stdDev = sqrt($variance / $n);

        $sorted = $values;
        sort($sorted);
        $q1 = $sorted[(int) floor($n * 0.25)] ?? 0;
        $q3 = $sorted[(int) floor($n * 0.75)] ?? 0;
        $iqr = $q3 - $q1;
        $lowerBound = $q1 - 1.5 * $iqr;
        $upperBound = $q3 + 1.5 * $iqr;

        $anomalies = [];

        for ($i = 0; $i < $n; $i++) {
            $value = (float) $values[$i];
            $label = $labels[$i] ?? "数据点{$i}";
            $zScore = $stdDev > 0 ? abs($value - $mean) / $stdDev : 0;
            $isOutlier = $value < $lowerBound || $value > $upperBound;

            if ($zScore > 2 || $isOutlier) {
                $anomalies[] = [
                    'label'         => $label,
                    'value'         => $value,
                    'mean'          => round($mean, 2),
                    'std_dev'       => round($stdDev, 2),
                    'z_score'       => round($zScore, 2),
                    'deviation'     => $value > $mean ? 'above' : 'below',
                    'deviation_pct' => $mean != 0 ? round(abs($value - $mean) / abs($mean) * 100, 1) : 0,
                    'severity'      => $zScore > 3 ? 'high' : ($zScore > 2.5 ? 'medium' : 'low'),
                ];
            }
        }
        return $anomalies;
    }

    /**
     * AI 异常原因分析
     */
    private function aiAnomalyAnalysis(array $anomalies): array
    {
        $anomalyDesc = [];
        foreach ($anomalies as $a) {
            $anomalyDesc[] = "{$a['label']}: 值={$a['value']}, 均值={$a['mean']}, 偏离={$a['deviation']}, 偏差={$a['deviation_pct']}%";
        }
        $anomalyStr = implode("\n", $anomalyDesc);

        $prompt = "以下数据中检测到异常波动，请分析可能的原因：\n\n异常数据：\n{$anomalyStr}\n\n"
            . "请简要分析每个异常数据点可能的原因（节假日效应、运营活动、数据采集异常、外部事件等）。\n"
            . "按\"数据标签: 原因分析\"格式输出，每个异常一行。";

        try {
            $aiResult = $this->getProvider()->write($prompt, [
                'system_prompt' => '你是数据异常分析专家，请基于数据特征给出合理的原因推断。',
                'max_tokens'    => 800,
                'temperature'   => 0.4,
            ]);

            $insights = [];
            $lines = explode("\n", trim($aiResult));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                if (preg_match('/^(.+?):\s*(.+)$/', $line, $m)) {
                    $insights[trim($m[1])] = trim($m[2]);
                }
            }
            return $insights;
        } catch (\Throwable $e) {
            Log::error('AI异常分析失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 归因分析 — AI 分析数据变化的归因
     */
    public function analyzeAttribution(array $reportData): array
    {
        $chartData = $reportData['chart'] ?? [];
        $values = $chartData['datasets'][0]['data'] ?? [];
        $labels = $chartData['labels'] ?? [];

        if (count($values) < 2) {
            return ['attributions' => [], 'note' => '数据不足，无法进行归因分析'];
        }

        $changes = [];
        for ($i = 1; $i < count($values); $i++) {
            $prev = (float) $values[$i - 1];
            $curr = (float) $values[$i];
            $changePct = $prev != 0 ? round(($curr - $prev) / abs($prev) * 100, 1) : 0;
            $changes[] = [
                'from'       => $labels[$i - 1] ?? '',
                'to'         => $labels[$i] ?? '',
                'change_pct' => $changePct,
                'direction'  => $curr > $prev ? 'up' : ($curr < $prev ? 'down' : 'flat'),
            ];
        }

        $maxChange = null;
        foreach ($changes as $change) {
            if ($maxChange === null || abs($change['change_pct']) > abs($maxChange['change_pct'])) {
                $maxChange = $change;
            }
        }

        $changeDesc = '';
        foreach ($changes as $c) {
            $changeDesc .= "{$c['from']}->{$c['to']}: {$c['change_pct']}% ({$c['direction']})\n";
        }
        $maxStr = $maxChange ? "{$maxChange['from']}->{$maxChange['to']}: {$maxChange['change_pct']}%" : '无';

        $prompt = "请对以下数据变化进行归因分析：\n\n数据变化序列：\n{$changeDesc}\n最大变化：{$maxStr}\n\n"
            . "请分析可能导致这些变化的原因，包含：1.内部因素 2.外部因素。简洁输出。";

        $aiAnalysis = '';
        try {
            $aiAnalysis = $this->getProvider()->write($prompt, [
                'system_prompt' => '你是数据归因分析专家，请基于数据变化趋势给出合理的归因分析。',
                'max_tokens'    => 800,
                'temperature'   => 0.4,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI归因分析失败: ' . $e->getMessage());
            $aiAnalysis = '归因分析暂时不可用。';
        }

        return [
            'changes'     => $changes,
            'max_change'  => $maxChange,
            'ai_analysis' => $aiAnalysis,
            'analyzed_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * AI 生成优化建议
     */
    public function generateRecommendations(array $reportData, array $reportInfo): array
    {
        $summary = $reportData['summary'] ?? [];
        $chartData = $reportData['chart'] ?? [];
        $values = $chartData['datasets'][0]['data'] ?? [];
        $reportType = $reportInfo['report_type'] ?? 'custom';

        $dataSummary = $this->prepareDataSummary($summary, $chartData);

        $trendStr = '';
        if (count($values) >= 2) {
            $first = (float) $values[0];
            $last  = (float) $values[count($values) - 1];
            $trendPct = $first != 0 ? round(($last - $first) / abs($first) * 100, 1) : 0;
            $trendStr = "整体趋势: {$trendPct}%";
        }

        $prompt = "基于以下报表数据，请生成优化建议：\n\n数据摘要：\n{$dataSummary}\n{$trendStr}\n报表类型: {$reportType}\n\n"
            . "请给出3-5条具体的优化建议，每条建议包含：\n1. 建议标题\n2. 具体操作\n3. 预期效果\n\n用简洁的中文输出。";

        try {
            $aiResult = $this->getProvider()->write($prompt, [
                'system_prompt' => '你是数据驱动运营顾问，请基于数据给出可执行的优化建议。',
                'max_tokens'    => 1000,
                'temperature'   => 0.5,
            ]);

            $recommendations = $this->parseRecommendations($aiResult);
            return [
                'recommendations' => $recommendations,
                'raw'             => $aiResult,
                'generated_at'    => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            Log::error('AI建议生成失败: ' . $e->getMessage());
            return [
                'recommendations' => [],
                'error'           => $e->getMessage(),
                'generated_at'    => date('Y-m-d H:i:s'),
            ];
        }
    }

    // ========================================================================
    // 工具方法
    // ========================================================================

    /**
     * 准备数据摘要文本
     */
    private function prepareDataSummary(array $summary, array $chartData): string
    {
        $parts = [];
        foreach ($summary as $key => $val) {
            $parts[] = "  - {$key}: {$val}";
        }

        $labels = $chartData['labels'] ?? [];
        $values = $chartData['datasets'][0]['data'] ?? [];
        if (!empty($labels) && !empty($values)) {
            $parts[] = "\n数据序列:";
            $maxItems = min(count($labels), 20);
            for ($i = 0; $i < $maxItems; $i++) {
                $parts[] = "  {$labels[$i]}: {$values[$i]}";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * 解析 AI 建议输出
     */
    private function parseRecommendations(string $aiResult): array
    {
        $recommendations = [];
        $blocks = preg_split('/\n(?=\d+[\.、\)）])/', trim($aiResult));

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            $recommendations[] = [
                'content' => $block,
            ];
        }

        return $recommendations;
    }

    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
    }
}
