<?php
declare(strict_types=1);

namespace app\common\service\ops;

use think\facade\Cache;
use think\facade\Db;

/**
 * 内容质量监控中心服务
 * V2.9.38 OPS-DEEP-4
 * 复用ContentQualityScoreService 5维评分数据
 */
class ContentQualityMonitorService
{
    protected const CACHE_TAG = 'quality_monitor';
    protected const CACHE_TTL = 300;

    public function getOverview(): array
    {
        return Cache::remember('quality_dashboard', function() {
            $totalContents = Db::name('content')->count();
            $scoredContents = Db::name('content_quality')->count();
            $avgScore = Db::name('content_quality')->avg('total_score');
            
            $distribution = Db::name('content_quality')
                ->field('CASE WHEN total_score >= 90 THEN "excellent" WHEN total_score >= 75 THEN "good" WHEN total_score >= 60 THEN "average" WHEN total_score >= 40 THEN "poor" ELSE "very_poor" END as level, COUNT(*) as count')
                ->group('level')
                ->select()
                ->toArray();
            
            $dimensionScores = [];
            foreach (['completeness', 'readability', 'seo', 'image_match', 'tag_accuracy'] as $dim) {
                $dimensionScores[$dim] = round((float) Db::name('content_quality')->avg($dim . '_score'), 1);
            }
            
            $trend = Db::name('content_quality')
                ->whereTime('created_at', '-30 days')
                ->field("DATE(created_at) as date, AVG(total_score) as avg_score, COUNT(*) as count")
                ->group('date')
                ->select()
                ->toArray();
            
            return [
                'total_contents' => $totalContents,
                'scored_contents' => $scoredContents,
                'avg_score' => round((float) $avgScore, 1),
                'distribution' => $distribution,
                'dimension_scores' => $dimensionScores,
                'trend' => $trend,
            ];
        }, self::CACHE_TTL);
    }

    public function getQualityTrend(int $days = 30): array
    {
        return Db::name('content_quality')
            ->whereTime('created_at', '-' . $days . ' days')
            ->field("DATE(created_at) as date, AVG(total_score) as avg_score, COUNT(*) as count")
            ->group('date')
            ->select()
            ->toArray();
    }

    public function getLowQualityContents(int $threshold = 60, int $page = 1, int $limit = 20): array
    {
        $query = Db::name('content_quality')->where('total_score', '<', $threshold)->order('total_score', 'asc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    public function getColumnQuality(): array
    {
        return Db::name('content_quality')
            ->alias('cq')
            ->join('content c', 'cq.content_id = c.id')
            ->field('c.category_id, AVG(cq.total_score) as avg_score, COUNT(*) as count')
            ->group('c.category_id')
            ->select()
            ->toArray();
    }

    public function getAlertConfig(): array
    {
        $config = Db::name('system_config')->where('config_key', 'quality_alert_config')->value('config_value');
        return $config ? json_decode($config, true) : ['threshold' => 60, 'notify_channels' => ['in_app'], 'enabled' => true];
    }

    public function setAlertConfig(array $config): bool
    {
        $exists = Db::name('system_config')->where('config_key', 'quality_alert_config')->find();
        if ($exists) {
            Db::name('system_config')->where('config_key', 'quality_alert_config')->update(['config_value' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        } else {
            Db::name('system_config')->insert(['config_key' => 'quality_alert_config', 'config_value' => json_encode($config, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
        }
        return true;
    }

    public function checkAlerts(): array
    {
        $config = $this->getAlertConfig();
        if (!($config['enabled'] ?? false)) return [];
        
        $lowQuality = $this->getLowQualityContents($config['threshold'] ?? 60);
        if ($lowQuality['total'] > 0) {
            // 发送告警
            $notifyService = new \app\common\service\system\UnifiedNotifyService();
            $notifyService->send(0, 'system', [
                'title' => '内容质量告警',
                'content' => "发现{$lowQuality['total']}篇内容质量分低于{$config['threshold']}，请及时处理。",
            ], $config['notify_channels'] ?? ['in_app']);
        }
        return ['alerts' => $lowQuality['total']];
    }

    public function generateReport(string $period = 'daily'): array
    {
        $days = $period === 'weekly' ? 7 : ($period === 'monthly' ? 30 : 1);
        $overview = $this->getOverview();
        $trend = $this->getQualityTrend($days);
        $lowQuality = $this->getLowQualityContents();
        $columnQuality = $this->getColumnQuality();
        
        return [
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'overview' => $overview,
            'trend' => $trend,
            'low_quality_count' => $lowQuality['total'],
            'column_quality' => $columnQuality,
        ];
    }

    public function getIssueList(int $page = 1, int $limit = 20): array
    {
        $query = Db::name('content_quality')->where('total_score', '<', 70)->order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }
}
