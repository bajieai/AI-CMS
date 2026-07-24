<?php
declare(strict_types=1);

namespace app\common\service\report;

use app\common\model\Content;
use think\facade\Db;
use think\facade\Cache;

/**
 * 内容分析报表 — V2.9.34 DR-4
 * 4个分析维度：生产/消费/互动/SEO
 */
class ContentAnalysisService
{
    private const CACHE_TAG = 'content_analysis';
    private const CACHE_TTL = 300;

    public function getProductionAnalysis(): array
    {
        return Cache::remember('production', function() {
            $todayStart = strtotime('today');
            $weekStart = strtotime('-7 days');
            $monthStart = strtotime('-30 days');
            return [
                'today_count' => Content::where('create_time', '>=', $todayStart)->count(),
                'week_count' => Content::where('create_time', '>=', $weekStart)->count(),
                'month_count' => Content::where('create_time', '>=', $monthStart)->count(),
                'model_distribution' => Content::field('content_model, COUNT(*) as count')->group('content_model')->select()->toArray(),
                'top_authors' => Content::field('member_id, COUNT(*) as count')->where('member_id', '>', 0)->group('member_id')->order('count', 'desc')->limit(10)->select()->toArray(),
                'ai_generated_ratio' => $this->calcAiRatio(),
            ];
        }, self::CACHE_TTL);
    }

    public function getConsumptionAnalysis(): array
    {
        return Cache::remember('consumption', function() {
            return [
                'total_views' => Content::sum('views'),
                'today_views' => Content::where('update_time', '>=', strtotime('today'))->sum('views'),
                'top10_content' => Content::order('views', 'desc')->limit(10)->field('id,title,views')->select()->toArray(),
                'model_views' => Content::field('content_model, SUM(views) as total_views')->group('content_model')->select()->toArray(),
            ];
        }, self::CACHE_TTL);
    }

    public function getInteractionAnalysis(): array
    {
        return Cache::remember('interaction', function() {
            $totalViews = Content::sum('views');
            $totalComments = Db::name('comment')->count();
            $totalLikes = Db::name('content_like')->count();
            return [
                'total_comments' => $totalComments,
                'total_likes' => $totalLikes,
                'interaction_rate' => $totalViews > 0 ? round(($totalComments + $totalLikes) / $totalViews * 100, 2) : 0,
                'top_interaction' => Content::order(Db::raw('comments + likes'), 'desc')->limit(10)->field('id,title,comments,likes')->select()->toArray(),
            ];
        }, self::CACHE_TTL);
    }

    public function getSeoAnalysis(): array
    {
        return Cache::remember('seo', function() {
            $total = Content::count();
            $hasSeoTitle = Content::where('seo_title', '<>', '')->count();
            $hasSeoDesc = Content::where('seo_description', '<>', '')->count();
            $hasKeywords = Content::where('seo_keywords', '<>', '')->count();
            $avgScore = Db::name('content_quality_score')->avg('seo_score') ?: 0;
            return [
                'avg_seo_score' => round($avgScore, 1),
                'seo_title_coverage' => $total > 0 ? round($hasSeoTitle / $total * 100, 1) : 0,
                'seo_desc_coverage' => $total > 0 ? round($hasSeoDesc / $total * 100, 1) : 0,
                'keywords_coverage' => $total > 0 ? round($hasKeywords / $total * 100, 1) : 0,
            ];
        }, self::CACHE_TTL);
    }

    private function calcAiRatio(): float
    {
        $total = Content::count();
        $aiGenerated = Content::where('ai_generated', 1)->count();
        return $total > 0 ? round($aiGenerated / $total * 100, 1) : 0;
    }
}
