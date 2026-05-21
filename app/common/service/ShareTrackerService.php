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

namespace app\common\service;

use app\common\model\ShareLog;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Request;

/**
 * 分享追踪服务 - V2.9.9
 * 分享渠道统计、转化追踪、日报生成
 */
class ShareTrackerService
{
    private static string $cacheTag = 'share_tracker';

    /**
     * 记录分享事件
     */
    public static function track(int $contentId, string $channel): void
    {
        $memberId = session('member_id', 0);
        $ip = Request::ip();
        $referer = Request::header('referer', '');

        ShareLog::log($contentId, $channel, $memberId, $ip, $referer);

        // 清除统计缓存
        Cache::delete('share_overview_');
        Cache::delete('share_trend_');
    }

    /**
     * 获取分享概览统计
     */
    public static function getOverview(?int $startTime = null, ?int $endTime = null): array
    {
        $cacheKey = 'share_overview_' . ($startTime ?: '0') . '_' . ($endTime ?: '0');
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $startTime = $startTime ?? strtotime('-30 days');
        $endTime = $endTime ?? time();

        $total = Db::name('share_log')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->count();

        $channelStats = ShareLog::statsByChannel($startTime, $endTime);
        $channels = ['wechat' => 0, 'weibo' => 0, 'qq' => 0, 'copy' => 0, 'other' => 0];
        foreach ($channelStats as $item) {
            $ch = $item['channel'];
            if (isset($channels[$ch])) {
                $channels[$ch] = (int) $item['count'];
            } else {
                $channels['other'] += (int) $item['count'];
            }
        }

        // 环比（与上一周期对比）
        $period = $endTime - $startTime;
        $prevStart = $startTime - $period;
        $prevEnd = $startTime;
        $prevTotal = Db::name('share_log')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->count();
        $mom = $prevTotal > 0 ? round(($total - $prevTotal) / $prevTotal * 100, 1) : 0;

        $result = [
            'total'       => $total,
            'mom'         => $mom,
            'channels'    => $channels,
            'channel_list'=> $channelStats,
        ];

        Cache::set($cacheKey, $result, 600);
        return $result;
    }

    /**
     * 热门分享内容
     */
    public static function getTopContent(int $limit = 10, ?int $startTime = null, ?int $endTime = null): array
    {
        return ShareLog::topContent($limit, $startTime, $endTime);
    }

    /**
     * 分享趋势（按日）
     */
    public static function getTrend(int $days = 7): array
    {
        $cacheKey = 'share_trend_' . $days;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $startDate = strtotime("-{$days} days");
        $data = Db::name('share_log')
            ->field('FROM_UNIXTIME(created_at, "%Y-%m-%d") as date, channel, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->group('date, channel')
            ->order('date')
            ->select()
            ->toArray();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayData = array_filter($data, fn($d) => $d['date'] === $date);
            $result[] = [
                'date'    => $date,
                'total'   => array_sum(array_column($dayData, 'count')),
                'wechat'  => (int) array_sum(array_column(array_filter($dayData, fn($d) => $d['channel'] === 'wechat'), 'count')),
                'weibo'   => (int) array_sum(array_column(array_filter($dayData, fn($d) => $d['channel'] === 'weibo'), 'count')),
                'qq'      => (int) array_sum(array_column(array_filter($dayData, fn($d) => $d['channel'] === 'qq'), 'count')),
            ];
        }

        Cache::set($cacheKey, $result, 600);
        return $result;
    }

    /**
     * 生成分享日报（供CLI调用）
     */
    public static function generateDailyReport(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d', strtotime('yesterday'));
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');

        return [
            'date'        => $date,
            'overview'    => self::getOverview($start, $end),
            'top_content' => self::getTopContent(10, $start, $end),
            'generated_at'=> date('Y-m-d H:i:s'),
        ];
    }
}
