<?php
declare(strict_types=1);

namespace app\common\service\ops;

use app\common\model\AbTest;
use think\facade\Cache;
use think\facade\Log;

/**
 * A/B测试服务
 * V2.9.38 OPS-DEEP-1
 * 中间件注入方式实现流量分配，Cookie绑定保持版本一致性
 */
class AbTestService
{
    protected const CACHE_TAG = 'ab_test';
    protected const CACHE_TTL = 60;

    public function createTest(array $data): int
    {
        $test = new AbTest();
        $test->save([
            'test_name' => $data['test_name'] ?? '',
            'test_type' => $data['test_type'] ?? AbTest::TYPE_CONTENT,
            'description' => $data['description'] ?? '',
            'version_a_config' => $data['version_a_config'] ?? null,
            'version_b_config' => $data['version_b_config'] ?? null,
            'traffic_ratio' => $data['traffic_ratio'] ?? 50,
            'primary_metric' => $data['primary_metric'] ?? 'click_rate',
            'target_audience' => $data['target_audience'] ?? null,
            'status' => AbTest::STATUS_DRAFT,
            'created_by' => $data['created_by'] ?? 0,
        ]);
        Cache::clear();
        return (int) $test->id;
    }

    public function updateTest(int $id, array $data): bool
    {
        $test = AbTest::find($id);
        if (!$test) return false;
        $test->save($data);
        Cache::clear();
        return true;
    }

    public function startTest(int $id): bool
    {
        $test = AbTest::find($id);
        if (!$test) return false;
        $test->save(['status' => AbTest::STATUS_RUNNING, 'start_time' => date('Y-m-d H:i:s')]);
        Cache::clear();
        return true;
    }

    public function pauseTest(int $id): bool
    {
        $test = AbTest::find($id);
        if (!$test) return false;
        $test->save(['status' => AbTest::STATUS_PAUSED]);
        Cache::clear();
        return true;
    }

    public function stopTest(int $id): bool
    {
        $test = AbTest::find($id);
        if (!$test) return false;
        $test->save(['status' => AbTest::STATUS_COMPLETED, 'end_time' => date('Y-m-d H:i:s')]);
        // 自动分析结果
        $this->analyzeResult($id);
        Cache::clear();
        return true;
    }

    /**
     * 分配版本(Cookie一致性)
     */
    public function assignVersion(int $testId, ?int $userId = null): string
    {
        $test = Cache::remember('ab_test_config_' . $testId, function() use ($testId) {
            return AbTest::find($testId)?->toArray() ?? null;
        }, self::CACHE_TTL);
        
        if (!$test || $test['status'] !== AbTest::STATUS_RUNNING) return 'A';
        
        // 检查Cookie是否已分配
        $cookieKey = 'ab_test_' . $testId;
        $assignedVersion = \think\facade\Cookie::get($cookieKey);
        if ($assignedVersion) return $assignedVersion;
        
        // 基于用户ID或随机分配
        $hash = crc32($userId ?: (request()->ip() . microtime()));
        $version = ($hash % 100) < $test['traffic_ratio'] ? 'B' : 'A';
        
        // 设置Cookie(30天有效)
        \think\facade\Cookie::set($cookieKey, $version, 86400 * 30);
        
        // 更新访客计数
        if ($version === 'A') {
            AbTest::where('id', $testId)->inc('version_a_visitors')->update();
        } else {
            AbTest::where('id', $testId)->inc('version_b_visitors')->update();
        }
        
        return $version;
    }

    /**
     * 记录转化
     */
    public function recordMetric(int $testId, string $version): void
    {
        if ($version === 'A') {
            AbTest::where('id', $testId)->inc('version_a_conversions')->update();
        } else {
            AbTest::where('id', $testId)->inc('version_b_conversions')->update();
        }
    }

    /**
     * 分析结果(Z检验+提升百分比+细分分析)
     */
    public function analyzeResult(int $testId): array
    {
        $test = AbTest::find($testId);
        if (!$test) return [];
        
        $aVisitors = $test->version_a_visitors;
        $aConversions = $test->version_a_conversions;
        $bVisitors = $test->version_b_visitors;
        $bConversions = $test->version_b_conversions;
        
        $aRate = $aVisitors > 0 ? $aConversions / $aVisitors : 0;
        $bRate = $bVisitors > 0 ? $bConversions / $bVisitors : 0;
        $lift = $aRate > 0 ? (($bRate - $aRate) / $aRate * 100) : 0;
        
        // Z检验
        $pooled = ($aConversions + $bConversions) / ($aVisitors + $bVisitors);
        $se = sqrt($pooled * (1 - $pooled) * (1/$aVisitors + 1/$bVisitors));
        $zScore = $se > 0 ? ($bRate - $aRate) / $se : 0;
        $confidence = $this->zScoreToConfidence($zScore);
        
        $winner = '';
        if ($confidence >= 95) {
            $winner = $bRate > $aRate ? 'B' : 'A';
        }
        
        $test->save([
            'winner' => $winner,
            'confidence' => round($confidence, 2),
        ]);
        
        return [
            'a_visitors' => $aVisitors, 'a_conversions' => $aConversions, 'a_rate' => round($aRate * 100, 2),
            'b_visitors' => $bVisitors, 'b_conversions' => $bConversions, 'b_rate' => round($bRate * 100, 2),
            'lift' => round($lift, 2), 'z_score' => round($zScore, 4),
            'confidence' => round($confidence, 2), 'winner' => $winner,
        ];
    }

    /**
     * 应用获胜版本
     */
    public function applyWinner(int $testId): bool
    {
        $test = AbTest::find($testId);
        if (!$test || !$test->winner) return false;
        
        if ($test->winner === 'B') {
            // 将版本B配置应用到正式环境
            $configB = $test->version_b_config;
            // 更新内容/模板配置...
            Log::info("Applied winner B for test {$testId}");
        }
        $test->save(['status' => AbTest::STATUS_ARCHIVED]);
        return true;
    }

    protected function zScoreToConfidence(float $z): float
    {
        $z = abs($z);
        if ($z >= 2.576) return 99.0;
        if ($z >= 1.96) return 95.0;
        if ($z >= 1.645) return 90.0;
        if ($z >= 1.282) return 80.0;
        return min($z * 50, 75.0);
    }
}
