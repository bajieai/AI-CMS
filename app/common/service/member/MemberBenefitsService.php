<?php
declare(strict_types=1);

namespace app\common\service\member;

use app\common\model\Member;
use app\common\model\MemberPointsLog;
use think\facade\Db;
use think\facade\Cache;

class MemberBenefitsService
{
    private const CACHE_TAG = 'member_benefits';

    public function getBenefitsCenter(int $memberId): array
    {
        $member = Member::find($memberId);
        if (!$member) return ['success' => false];
        $levelService = new MemberLevelService();
        $levelResult = $levelService->calculateLevel($memberId);
        $points = (int)$member->points;
        $recentLogs = MemberPointsLog::where('member_id', $memberId)->order('create_time', 'desc')->limit(5)->select()->toArray();
        return ['current_level' => $levelResult['current_level'] ?? [], 'next_level' => $levelResult['next_level'] ?? null, 'upgrade_progress' => $levelResult['upgrade_progress'] ?? 100, 'points' => $points, 'total_points' => (int)$member->total_points, 'recent_activities' => $recentLogs];
    }

    public function getBenefitsComparison(): array
    {
        return (new MemberLevelService())->getBenefitsComparison();
    }

    public function getPointsBenefits(): array
    {
        return [['name' => '付费内容阅读', 'points' => 50, 'description' => '解锁一篇付费内容'], ['name' => '付费文件下载', 'points' => 20, 'description' => '下载一个付费文件'], ['name' => '模板折扣', 'points' => 100, 'description' => '模板购买9折优惠'], ['name' => '积分转赠', 'points' => 10, 'description' => '转赠积分给其他用户']];
    }

    public function getUsageRecords(int $memberId): array
    {
        $pointsLogs = MemberPointsLog::where('member_id', $memberId)->where('points', '<', 0)->order('create_time', 'desc')->limit(20)->select()->toArray();
        $paidRecords = Db::name('paid_content_record')->where('member_id', $memberId)->order('create_time', 'desc')->limit(20)->select()->toArray();
        return ['points_consumption' => $pointsLogs, 'paid_purchases' => $paidRecords];
    }

    public function getUsageStats(): array
    {
        return Cache::remember('usage_stats', function() {
            return ['total_paid_reads' => Db::name('paid_content_record')->where('pay_type', 'points')->count(), 'total_paid_downloads' => Db::name('paid_content_record')->where('pay_type', 'points')->where('content.paid_type', 'pay_download')->count(), 'vip_members' => Member::where('vip_expire_time', '>', time())->count()];
        }, 300);
    }
}
