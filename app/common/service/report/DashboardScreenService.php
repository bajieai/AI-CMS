<?php
declare(strict_types=1);

namespace app\common\service\report;

use app\common\model\Content;
use app\common\model\Member;
use think\facade\Db;
use think\facade\Cache;

class DashboardScreenService
{
    private const CACHE_TAG = 'dashboard_screen';
    private const CACHE_TTL = 30;

    public function getScreen(int $screenId): array
    {
        $screens = [1 => 'getContentScreenData', 2 => 'getUserScreenData', 3 => 'getRevenueScreenData'];
        $method = $screens[$screenId] ?? 'getContentScreenData';
        return $this->$method();
    }

    public function getContentScreenData(): array
    {
        return Cache::remember('content_screen', function() {
            $todayStart = strtotime('today');
            return ['total_content' => Content::count(), 'today_new' => Content::where('create_time', '>=', $todayStart)->count(), 'audit_pass_rate' => $this->calcAuditPassRate(), 'avg_quality_score' => Db::name('content_quality_score')->avg('total_score') ?: 0, 'top10_reads' => Content::order('views', 'desc')->limit(10)->field('id,title,views')->select()->toArray()];
        }, self::CACHE_TTL);
    }

    public function getUserScreenData(): array
    {
        return Cache::remember('user_screen', function() {
            $todayStart = strtotime('today');
            return ['total_users' => Member::count(), 'today_new' => Member::where('create_time', '>=', $todayStart)->count(), 'level_distribution' => $this->getLevelDistribution(), 'paid_users' => Db::name('paid_content_record')->distinct('member_id')->count()];
        }, self::CACHE_TTL);
    }

    public function getRevenueScreenData(): array
    {
        return Cache::remember('revenue_screen', function() {
            $todayStart = strtotime('today');
            $monthStart = strtotime(date('Y-m-01'));
            return ['total_revenue' => Db::name('paid_content_record')->where('status', 1)->sum('amount'), 'today_revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $todayStart)->sum('amount'), 'month_revenue' => Db::name('paid_content_record')->where('status', 1)->where('create_time', '>=', $monthStart)->sum('amount'), 'paid_users' => Db::name('paid_content_record')->where('status', 1)->distinct('member_id')->count()];
        }, self::CACHE_TTL);
    }

    private function calcAuditPassRate(): float
    {
        $total = Content::whereNotNull('audit_time')->count();
        $passed = Content::where('status', 1)->whereNotNull('audit_time')->count();
        return $total > 0 ? round($passed / $total * 100, 1) : 0;
    }

    private function getLevelDistribution(): array
    {
        return Db::name('member')->alias('m')->join('member_level l', 'm.level_id = l.id', 'LEFT')->field('l.level_name, COUNT(*) as count')->group('m.level_id')->select()->toArray();
    }
}
