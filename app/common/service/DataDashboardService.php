<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * 运营数据看板服务 - V2.9.13
 *
 * 与 DashboardService 的区别：
 * - DashboardService: 基于系统基础数据（用户数/内容数/订单数等），面向"系统健康概览"
 * - DataDashboardService: 基于 visit_log 等行为数据，面向"内容运营分析"
 */
class DataDashboardService
{
    /**
     * 获取概览数据（PV/UV/内容统计）
     */
    public static function getOverview(int $days = 7): array
    {
        $endTime = time();
        $startTime = strtotime("-{$days} days");

        // PV: visit_log按content_id分组计数
        $pv = Db::name('visit_log')
            ->whereBetween('visit_time', [$startTime, $endTime])
            ->count();

        // UV: visit_log按visitor_id去重
        $uv = Db::name('visit_log')
            ->whereBetween('visit_time', [$startTime, $endTime])
            ->group('visitor_id')
            ->count();

        $totalContent = Db::name('content')->where('status', 2)->count();
        $newContent = Db::name('content')
            ->where('status', 2)
            ->whereBetween('create_time', [$startTime, $endTime])
            ->count();

        return [
            'pv'           => $pv,
            'uv'           => $uv,
            'total_content'=> $totalContent,
            'new_content'  => $newContent,
            'days'         => $days,
        ];
    }

    /**
     * 获取访问趋势（按天聚合PV/UV）
     */
    public static function getTrend(int $days = 7): array
    {
        $endTime = strtotime('tomorrow') - 1;
        $startTime = strtotime("-{$days} days", strtotime('today'));

        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dayStart = strtotime("-{$i} days", strtotime('today'));
            $dayEnd = $dayStart + 86399;
            $dayLabel = date('m-d', $dayStart);

            $dayPv = Db::name('visit_log')
                ->whereBetween('visit_time', [$dayStart, $dayEnd])
                ->count();

            $dayUv = Db::name('visit_log')
                ->whereBetween('visit_time', [$dayStart, $dayEnd])
                ->group('visitor_id')
                ->count();

            $dates[] = [
                'date' => $dayLabel,
                'pv'   => $dayPv,
                'uv'   => $dayUv,
            ];
        }

        return $dates;
    }

    /**
     * 获取内容分类分布（按cate_id分组）
     */
    public static function getCategoryDist(): array
    {
        $result = Db::name('content')
            ->field('cate_id, COUNT(*) as count')
            ->where('status', '>=', 0)
            ->group('cate_id')
            ->select()
            ->toArray();

        $cates = Db::name('cate')->column('name', 'id');

        $data = [];
        foreach ($result as $row) {
            $cateName = $cates[$row['cate_id']] ?? '未分类';
            $data[] = [
                'name'  => $cateName,
                'value' => (int) $row['count'],
            ];
        }

        // 按数量降序
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return $data;
    }

    /**
     * 获取热门内容Top10（views*0.7 + comment_count*0.3）
     */
    public static function getHotContent(int $limit = 10): array
    {
        // 使用子查询计算评论数
        $list = Db::name('content')
            ->alias('c')
            ->field([
                'c.id',
                'c.title',
                'c.views',
                'c.cover',
                'IFNULL(cc.comment_count, 0) as comment_count',
                '(c.views * 0.7 + IFNULL(cc.comment_count, 0) * 0.3) as hot_score',
            ])
            ->leftJoin(
                '(SELECT content_id, COUNT(*) as comment_count FROM ' . config('database.connections.mysql.prefix') . 'comment WHERE status = 1 GROUP BY content_id) cc',
                'c.id = cc.content_id'
            )
            ->where('c.status', 2)
            ->order('hot_score', 'DESC')
            ->limit($limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['hot_score'] = round((float) $item['hot_score'], 1);
        }

        return $list;
    }

    /**
     * 获取运营日报数据
     */
    public static function getReport(int $days = 1): array
    {
        $endTime = time();
        $startTime = strtotime("-{$days} days");

        $contentCount = Db::name('content')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->count();

        $aiUsage = Db::name('ai_log')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->count();

        $templateInstall = Db::name('template_install')
            ->whereBetween('install_time', [$startTime, $endTime])
            ->count();

        $newMembers = Db::name('member')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->count();

        return [
            'period'           => $days,
            'content_count'    => $contentCount,
            'ai_usage'         => $aiUsage,
            'template_install' => $templateInstall,
            'new_members'      => $newMembers,
        ];
    }
}
