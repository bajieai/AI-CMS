<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Ad as AdModel;
use app\common\model\AdStat as AdStatModel;
use think\facade\Cache;
use think\facade\Db;

/**
 * 广告服务
 */
class AdService
{
    /**
     * 获取广告位下的有效广告
     */
    public function getAds(string $positionCode): array
    {
        $cacheKey = "ads_{$positionCode}";
        return Cache::tag(CacheService::TAG_AD)->remember($cacheKey, function () use ($positionCode) {
            $now = time();
            return AdModel::alias('a')
                ->join('ad_position p', 'a.position_id = p.id')
                ->where('p.code', $positionCode)
                ->where('p.status', 1)
                ->where('a.status', 1)
                ->where('a.start_time', '<=', $now)
                ->where('a.end_time', '>=', $now)
                ->order('a.sort', 'asc')
                ->field('a.*')
                ->select()
                ->toArray();
        });
    }

    /**
     * 记录广告展示
     */
    public function trackView(int $adId): void
    {
        $today = date('Y-m-d');
        Db::name('ad_stat')
            ->where('ad_id', $adId)
            ->where('stat_date', $today)
            ->inc('views', 1)
            ->update();
    }

    /**
     * 记录广告点击
     */
    public function trackClick(int $adId): void
    {
        $today = date('Y-m-d');
        Db::name('ad_stat')
            ->where('ad_id', $adId)
            ->where('stat_date', $today)
            ->inc('clicks', 1)
            ->update();
    }

    /**
     * 获取广告统计
     */
    public function getStats(int $adId, string $startDate, string $endDate): array
    {
        return AdStatModel::where('ad_id', $adId)
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->order('stat_date', 'asc')
            ->select()
            ->toArray();
    }
}