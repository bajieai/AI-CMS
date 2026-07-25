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
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 趋势预测引擎 - V2.9.39 DATA-DEEP-3
 *
 * 功能：线性回归 / 移动平均 / 季节性预测 / AI预测+置信区间
 * - 线性回归用最小二乘法 PHP 原生实现
 * - AI 预测调用 AiProviderFactory::getDefault()
 */
class TrendPredictionService
{
    private const CACHE_TAG = 'trend_prediction';
    private const CACHE_TTL = 600; // 10分钟

    protected ?AiProviderInterface $provider = null;

    // 预测方法常量
    public const METHOD_LINEAR_REGRESSION = 'linear_regression';
    public const METHOD_MOVING_AVERAGE     = 'moving_average';
    public const METHOD_SEASONAL          = 'seasonal';
    public const METHOD_AI                = 'ai';

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

    // ========================================================================
    // 主预测入口
    // ========================================================================

    /**
     * 执行趋势预测
     *
     * @param array $historicalData 历史数据 [['date'=>'2026-01-01', 'value'=>100], ...]
     * @param int $forecastDays 预测天数
     * @param string $method 预测方法
     * @return array 预测结果
     */
    public function predict(array $historicalData, int $forecastDays = 7, string $method = self::METHOD_LINEAR_REGRESSION): array
    {
        if (count($historicalData) < 3) {
            return [
                'success'     => false,
                'msg'         => '历史数据不足，至少需要3个数据点',
                'required'     => 3,
                'provided'     => count($historicalData),
            ];
        }

        $cacheKey = 'trend_predict_' . md5(json_encode([$historicalData, $forecastDays, $method]));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = match ($method) {
            self::METHOD_LINEAR_REGRESSION => $this->linearRegressionForecast($historicalData, $forecastDays),
            self::METHOD_MOVING_AVERAGE     => $this->movingAverageForecast($historicalData, $forecastDays),
            self::METHOD_SEASONAL          => $this->seasonalForecast($historicalData, $forecastDays),
            self::METHOD_AI                => $this->aiForecast($historicalData, $forecastDays),
            default                        => $this->linearRegressionForecast($historicalData, $forecastDays),
        };

        $result['method'] = $method;
        $result['historical_count'] = count($historicalData);
        $result['forecast_days'] = $forecastDays;
        $result['generated_at'] = date('Y-m-d H:i:s');

        Cache::set($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    // ========================================================================
    // 线性回归（最小二乘法）
    // ========================================================================

    /**
     * 线性回归预测 — 最小二乘法 PHP 原生实现
     */
    public function linearRegressionForecast(array $historicalData, int $forecastDays): array
    {
        $n = count($historicalData);
        $x = range(0, $n - 1); // x = 0, 1, 2, ..., n-1
        $y = array_map(fn($d) => (float) $d['value'], $historicalData);

        // 最小二乘法计算
        $sumX  = array_sum($x);
        $sumY  = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if (abs($denominator) < 1e-10) {
            return [
                'success'     => false,
                'msg'         => '数据无方差，无法拟合线性回归',
                'predictions' => [],
            ];
        }

        $slope     = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // 计算 R² (决定系数)
        $meanY = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;
        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $ssTotal += ($y[$i] - $meanY) ** 2;
            $ssResidual += ($y[$i] - $predicted) ** 2;
        }
        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        // 计算残差标准差（用于置信区间）
        $residualStdDev = $n > 2 ? sqrt($ssResidual / ($n - 2)) : 0;

        // 预测
        $predictions = [];
        $lastDate = $historicalData[$n - 1]['date'] ?? date('Y-m-d');
        for ($i = 1; $i <= $forecastDays; $i++) {
            $predictedX = $n - 1 + $i;
            $predictedValue = $slope * $predictedX + $intercept;

            // 95% 置信区间 ≈ predicted ± 1.96 * stdDev * sqrt(1 + 1/n + (x-xmean)^2/SSx)
            $xMean = $sumX / $n;
            $ssx = $sumX2 - $n * $xMean * $xMean;
            $leverage = $ssx > 0 ? ($predictedX - $xMean) ** 2 / $ssx : 0;
            $margin = 1.96 * $residualStdDev * sqrt(1 + 1 / $n + $leverage);

            $predictions[] = [
                'date'        => $this->addDaysToDate($lastDate, $i),
                'value'       => round($predictedValue, 2),
                'lower_bound' => round($predictedValue - $margin, 2),
                'upper_bound' => round($predictedValue + $margin, 2),
                'confidence'  => 95,
            ];
        }

        return [
            'success'      => true,
            'predictions'  => $predictions,
            'model'        => [
                'slope'        => round($slope, 4),
                'intercept'    => round($intercept, 4),
                'r_squared'    => round($rSquared, 4),
                'std_error'   => round($residualStdDev, 4),
                'trend'       => $slope > 0 ? 'upward' : ($slope < 0 ? 'downward' : 'flat'),
                'trend_pct'   => $intercept != 0 ? round($slope / abs($intercept) * 100, 2) : 0,
            ],
        ];
    }

    // ========================================================================
    // 移动平均预测
    // ========================================================================

    /**
     * 移动平均预测
     *
     * @param array $historicalData 历史数据
     * @param int $forecastDays 预测天数
     * @param int $windowSize 窗口大小（默认7天）
     */
    public function movingAverageForecast(array $historicalData, int $forecastDays, int $windowSize = 7): array
    {
        $values = array_map(fn($d) => (float) $d['value'], $historicalData);
        $n = count($values);

        if ($windowSize > $n) {
            $windowSize = max(2, $n);
        }

        // 计算移动平均
        $ma = [];
        for ($i = $windowSize - 1; $i < $n; $i++) {
            $sum = 0;
            for ($j = $i - $windowSize + 1; $j <= $i; $j++) {
                $sum += $values[$j];
            }
            $ma[] = $sum / $windowSize;
        }

        // 计算标准差
        $maValues = array_slice($values, max(0, $n - $windowSize));
        $mean = array_sum($maValues) / count($maValues);
        $variance = 0;
        foreach ($maValues as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $stdDev = sqrt($variance / count($maValues));

        // 使用最后一个移动平均值作为预测基准
        $lastMA = !empty($ma) ? end($ma) : $mean;

        $predictions = [];
        $lastDate = $historicalData[$n - 1]['date'] ?? date('Y-m-d');
        for ($i = 1; $i <= $forecastDays; $i++) {
            $margin = 1.96 * $stdDev;
            $predictions[] = [
                'date'        => $this->addDaysToDate($lastDate, $i),
                'value'       => round($lastMA, 2),
                'lower_bound' => round($lastMA - $margin, 2),
                'upper_bound' => round($lastMA + $margin, 2),
                'confidence'  => 95,
            ];
        }

        return [
            'success'     => true,
            'predictions' => $predictions,
            'model'       => [
                'window_size'  => $windowSize,
                'last_ma'       => round($lastMA, 2),
                'std_error'    => round($stdDev, 4),
                'ma_values'    => array_map(fn($v) => round($v, 2), $ma),
            ],
        ];
    }

    // ========================================================================
    // 季节性预测
    // ========================================================================

    /**
     * 季节性预测 — 基于历史同周期数据的加权预测
     *
     * @param array $historicalData 历史数据
     * @param int $forecastDays 预测天数
     * @param int $seasonLength 季节周期长度（默认7=周）
     */
    public function seasonalForecast(array $historicalData, int $forecastDays, int $seasonLength = 7): array
    {
        $values = array_map(fn($d) => (float) $d['value'], $historicalData);
        $n = count($values);

        if ($n < $seasonLength * 2) {
            // 数据不足一个完整周期，退化为移动平均
            return $this->movingAverageForecast($historicalData, $forecastDays, min($seasonLength, $n));
        }

        // 1. 计算趋势（线性回归斜率）
        $x = range(0, $n - 1);
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $values[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        $denom = ($n * $sumX2 - $sumX * $sumX);
        $slope = abs($denom) > 1e-10 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // 2. 计算季节因子
        $seasonalFactors = array_fill(0, $seasonLength, 0.0);
        $seasonCounts = array_fill(0, $seasonLength, 0);

        for ($i = 0; $i < $n; $i++) {
            $seasonIndex = $i % $seasonLength;
            $trendValue = $slope * $i + $intercept;
            $seasonalFactors[$seasonIndex] += $trendValue > 0 ? $values[$i] / $trendValue : 1.0;
            $seasonCounts[$seasonIndex]++;
        }

        // 平均季节因子
        for ($i = 0; $i < $seasonLength; $i++) {
            $seasonalFactors[$i] = $seasonCounts[$i] > 0 ? $seasonalFactors[$i] / $seasonCounts[$i] : 1.0;
        }

        // 归一化季节因子
        $avgFactor = array_sum($seasonalFactors) / $seasonLength;
        if ($avgFactor > 0) {
            for ($i = 0; $i < $seasonLength; $i++) {
                $seasonalFactors[$i] /= $avgFactor;
            }
        }

        // 3. 计算残差标准差
        $residuals = [];
        for ($i = 0; $i < $n; $i++) {
            $seasonIndex = $i % $seasonLength;
            $predicted = ($slope * $i + $intercept) * $seasonalFactors[$seasonIndex];
            $residuals[] = $values[$i] - $predicted;
        }
        $residualMean = array_sum($residuals) / $n;
        $variance = 0;
        foreach ($residuals as $r) {
            $variance += ($r - $residualMean) ** 2;
        }
        $stdDev = sqrt($variance / max(1, $n - 1));

        // 4. 预测
        $predictions = [];
        $lastDate = $historicalData[$n - 1]['date'] ?? date('Y-m-d');
        for ($i = 1; $i <= $forecastDays; $i++) {
            $predictedX = $n - 1 + $i;
            $seasonIndex = $predictedX % $seasonLength;
            $trendValue = $slope * $predictedX + $intercept;
            $predictedValue = $trendValue * $seasonalFactors[$seasonIndex];
            $margin = 1.96 * $stdDev;

            $predictions[] = [
                'date'        => $this->addDaysToDate($lastDate, $i),
                'value'       => round(max(0, $predictedValue), 2),
                'lower_bound' => round(max(0, $predictedValue - $margin), 2),
                'upper_bound' => round($predictedValue + $margin, 2),
                'confidence'  => 95,
                'season_index'=> $seasonIndex,
            ];
        }

        return [
            'success'     => true,
            'predictions' => $predictions,
            'model'       => [
                'slope'           => round($slope, 4),
                'intercept'       => round($intercept, 4),
                'season_length'   => $seasonLength,
                'seasonal_factors'=> array_map(fn($f) => round($f, 4), $seasonalFactors),
                'std_error'       => round($stdDev, 4),
            ],
        ];
    }

    // ========================================================================
    // AI 预测
    // ========================================================================

    /**
     * AI 预测 — 调用 AI 模型分析历史数据并预测未来趋势
     */
    public function aiForecast(array $historicalData, int $forecastDays): array
    {
        // 先用线性回归作为基础
        $baseline = $this->linearRegressionForecast($historicalData, $forecastDays);

        // 准备 AI prompt
        $dataStr = '';
        foreach ($historicalData as $d) {
            $dataStr .= "{$d['date']}: {$d['value']}\n";
        }

        $baselineStr = '';
        if (!empty($baseline['predictions'])) {
            foreach ($baseline['predictions'] as $p) {
                $baselineStr .= "{$p['date']}: {$p['value']} (CI: {$p['lower_bound']}-{$p['upper_bound']})\n";
            }
        }

        $prompt = "你是一位数据预测专家。请基于以下历史数据，预测未来 {$forecastDays} 天的趋势。\n\n"
            . "历史数据：\n{$dataStr}\n\n"
            . "统计模型预测（线性回归基准）：\n{$baselineStr}\n\n"
            . "请综合分析数据趋势，给出你的预测。请按以下JSON格式返回（不要包含markdown标记）：\n"
            . '{"predictions":[{"date":"YYYY-MM-DD","value":100,"reasoning":"分析原因"}],'
            . '"overall_trend":"upward/downward/flat","confidence":"high/medium/low","key_factors":["因素1","因素2"]}';

        try {
            $aiResult = $this->getProvider()->write($prompt, [
                'system_prompt' => '你是数据预测分析专家。请基于历史数据给出合理的预测。只返回JSON格式数据，不要额外文字。',
                'max_tokens'    => 1200,
                'temperature'   => 0.3,
            ]);

            // 清理可能的 markdown 包裹
            $cleanResult = trim($aiResult);
            if (str_starts_with($cleanResult, '```')) {
                $cleanResult = preg_replace('/^```(?:json)?\s*/', '', $cleanResult);
                $cleanResult = preg_replace('/\s*```$/', '', $cleanResult);
            }

            $parsed = json_decode($cleanResult, true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($parsed['predictions'])) {
                // 合并统计置信区间
                $statPredictions = $baseline['predictions'] ?? [];
                foreach ($parsed['predictions'] as $i => &$pred) {
                    if (isset($statPredictions[$i])) {
                        $stat = $statPredictions[$i];
                        $pred['lower_bound'] = $stat['lower_bound'] ?? null;
                        $pred['upper_bound'] = $stat['upper_bound'] ?? null;
                        $pred['confidence']  = 95;
                    }
                }

                return [
                    'success'      => true,
                    'predictions'  => $parsed['predictions'],
                    'overall_trend'=> $parsed['overall_trend'] ?? 'unknown',
                    'ai_confidence'=> $parsed['confidence'] ?? 'medium',
                    'key_factors'  => $parsed['key_factors'] ?? [],
                    'baseline'     => $baseline['model'] ?? [],
                    'source'       => 'ai',
                ];
            }

            // JSON 解析失败，返回统计基线
            return [
                'success'     => true,
                'predictions' => $baseline['predictions'] ?? [],
                'model'       => $baseline['model'] ?? [],
                'source'      => 'linear_regression_fallback',
                'note'        => 'AI返回格式异常，已降级为统计预测',
            ];
        } catch (\Throwable $e) {
            Log::error('AI趋势预测失败: ' . $e->getMessage());
            return [
                'success'     => true,
                'predictions' => $baseline['predictions'] ?? [],
                'model'       => $baseline['model'] ?? [],
                'source'      => 'linear_regression_fallback',
                'error'       => $e->getMessage(),
                'note'        => 'AI预测失败，已降级为统计预测',
            ];
        }
    }

    // ========================================================================
    // 便捷方法：从数据库获取历史数据并预测
    // ========================================================================

    /**
     * 从数据库获取时序数据并预测
     *
     * @param string $table 表名
     * @param string $valueField 值字段
     * @param string $timeField 时间字段
     * @param int $days 历史数据天数
     * @param int $forecastDays 预测天数
     * @param string $method 预测方法
     */
    public function predictFromDb(string $table, string $valueField, string $timeField, int $days = 30, int $forecastDays = 7, string $method = self::METHOD_LINEAR_REGRESSION): array
    {
        $startTime = strtotime("-{$days} days");

        // 按天聚合
        $rows = Db::name($table)
            ->field([
                "FROM_UNIXTIME({$timeField}, '%Y-%m-%d') as date",
                "SUM({$valueField}) as value",
            ])
            ->where($timeField, '>=', $startTime)
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        if (count($rows) < 3) {
            return [
                'success' => false,
                'msg'     => "数据库历史数据不足，仅找到 " . count($rows) . " 天的数据",
            ];
        }

        return $this->predict($rows, $forecastDays, $method);
    }

    // ========================================================================
    // 工具方法
    // ========================================================================

    /**
     * 日期加天数
     */
    private function addDaysToDate(string $date, int $days): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            $timestamp = time();
        }
        return date('Y-m-d', strtotime("+{$days} days", $timestamp));
    }

    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
    }
}
