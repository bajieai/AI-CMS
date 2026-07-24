<?php
declare(strict_types=1);

namespace app\common\service\operation;

use app\common\model\Content;
use think\facade\Db;
use think\facade\Cache;

/**
 * AI运营助手 — V2.9.34 OPS2-5
 * 运营建议+智能提醒+报告自动生成+知识库
 */
class OperationAssistantService
{
    private const CACHE_TAG = 'operation_assistant';

    public function getSuggestions(): array
    {
        return Cache::remember('suggestions', function() {
            $suggestions = [];
            $lowQuality = Content::where('quality_score', '<', 60)->count();
            if ($lowQuality > 0) $suggestions[] = ['type' => 'content_optimization', 'priority' => 'high', 'suggestion' => "有{$lowQuality}篇低质量内容建议修复"];
            $todayPublished = Content::where('create_time', '>=', strtotime('today'))->count();
            if ($todayPublished === 0) $suggestions[] = ['type' => 'publish', 'priority' => 'medium', 'suggestion' => '今日尚未发布新内容，建议发布'];
            $bestHour = $this->getBestPublishHour();
            if ($bestHour) $suggestions[] = ['type' => 'publish_time', 'priority' => 'low', 'suggestion' => "最佳发布时间段: {$bestHour}"];
            $suggestions[] = ['type' => 'distribute', 'priority' => 'medium', 'suggestion' => '建议将优质内容分发到微信+头条+知乎'];
            $suggestions[] = ['type' => 'member', 'priority' => 'low', 'suggestion' => '建议对7天未登录用户发送召回通知'];
            return $suggestions;
        }, 300);
    }

    public function getSmartAlerts(): array
    {
        return Cache::remember('alerts', function() {
            $alerts = [];
            $qualityTrend = Db::name('content_quality_score')->where('create_time', '>=', strtotime('-3 days'))->avg('total_score');
            $oldQuality = Db::name('content_quality_score')->where('create_time', '<', strtotime('-3 days'))->avg('total_score');
            if ($qualityTrend && $oldQuality && $qualityTrend < $oldQuality) $alerts[] = ['type' => 'quality_decline', 'level' => 'warning', 'message' => '内容质量评分连续下降'];
            $todayActive = Db::name('member')->where('login_time', '>=', strtotime('today'))->count();
            $yesterdayActive = Db::name('member')->where('login_time', '>=', strtotime('yesterday'))->where('login_time', '<', strtotime('today'))->count();
            if ($yesterdayActive > 0 && $todayActive < $yesterdayActive * 0.7) $alerts[] = ['type' => 'user_decline', 'level' => 'warning', 'message' => '今日活跃用户数下降超过30%'];
            return $alerts;
        }, 300);
    }

    public function generateDailyReport(): array
    {
        $todayStart = strtotime('today');
        return ['date' => date('Y-m-d'), 'published' => Content::where('create_time', '>=', $todayStart)->count(), 'views' => Content::where('update_time', '>=', $todayStart)->sum('views'), 'new_users' => Db::name('member')->where('create_time', '>=', $todayStart)->count(), 'revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $todayStart)->sum('amount'), 'distributed' => Db::name('push_channel')->where('create_time', '>=', $todayStart)->count()];
    }

    public function generateWeeklyReport(): array
    {
        $weekStart = strtotime('monday this week');
        return ['week' => date('Y-W'), 'published' => Content::where('create_time', '>=', $weekStart)->count(), 'views' => Content::where('update_time', '>=', $weekStart)->sum('views'), 'new_users' => Db::name('member')->where('create_time', '>=', $weekStart)->count(), 'revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $weekStart)->sum('amount')];
    }

    public function generateMonthlyReport(): array
    {
        $monthStart = strtotime(date('Y-m-01'));
        return ['month' => date('Y-m'), 'published' => Content::where('create_time', '>=', $monthStart)->count(), 'views' => Content::where('update_time', '>=', $monthStart)->sum('views'), 'new_users' => Db::name('member')->where('create_time', '>=', $monthStart)->count(), 'revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $monthStart)->sum('amount')];
    }

    public function getKnowledgeBase(): array
    {
        return Cache::remember('knowledge_base', function() {
            return [
                ['category' => 'best_practice', 'title' => '内容发布最佳时间', 'content' => '工作日9-11点和14-16点发布效果最佳'],
                ['category' => 'best_practice', 'title' => 'SEO优化建议', 'content' => '确保每篇内容有SEO标题、描述和关键词'],
                ['category' => 'faq', 'title' => '如何提升内容质量', 'content' => '使用AI质量评分+修复管线提升内容质量'],
                ['category' => 'faq', 'title' => '如何增加付费收入', 'content' => '设置合理的付费价格+预览比例+推广优质付费内容'],
                ['category' => 'training', 'title' => '多语言运营指南', 'content' => '使用翻译工作台批量翻译+审核机制确保质量'],
                ['category' => 'case', 'title' => '行业信息平台案例', 'content' => '某化工行业平台通过付费内容+VIP订阅实现月收入5万'],
            ];
        }, 3600);
    }

    private function getBestPublishHour(): ?string
    {
        $hourly = Content::field('FROM_UNIXTIME(create_time, "%H") as hour, SUM(views) as total_views')->group('hour')->order('total_views', 'desc')->find();
        return $hourly ? $hourly['hour'] . ':00-' . ($hourly['hour'] + 1) . ':00' : null;
    }
}
