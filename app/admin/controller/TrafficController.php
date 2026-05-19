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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Cache;
use think\facade\Db;

/**
 * 流量分析看板 - V2.8新增
 */
class TrafficController extends AdminBaseController
{
    /**
     * 流量看板页面
     */
    public function index()
    {
        return $this->view('/traffic_index');
    }

    /**
     * V2.9.9 B-2: 来源分析（按大类聚合）
     */
    public function getSourceStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $raw = Db::name('visit_log')
            ->field('referrer, COUNT(*) as count')
            ->where('visit_time', '>=', $startDate)
            ->group('referrer')
            ->select()
            ->toArray();
        
        // 按source_category聚合
        $categories = ['direct' => 0, 'search' => 0, 'social' => 0, 'referral' => 0, 'other' => 0];
        foreach ($raw as $item) {
            $cat = \app\common\service\VisitService::detectSourceCategory($item['referrer'] ?? '');
            $categories[$cat] = ($categories[$cat] ?? 0) + (int) $item['count'];
        }
        
        $data = [];
        foreach ($categories as $type => $count) {
            $data[] = ['source_type' => $type, 'count' => $count];
        }
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 24小时时段分布
     */
    public function getHourlyStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('visit_log')
            ->field('HOUR(FROM_UNIXTIME(visit_time)) as hour, COUNT(*) as count')
            ->where('visit_time', '>=', $startDate)
            ->group('hour')
            ->order('hour')
            ->select()
            ->toArray();
        
        $result = array_fill(0, 24, 0);
        foreach ($data as $item) {
            $result[(int)$item['hour']] = (int)$item['count'];
        }
        
        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 设备分布（从UA解析设备类型）
     */
    public function getDeviceStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('visit_log')
            ->field("CASE
                WHEN ua LIKE '%Mobile%' AND ua NOT LIKE '%iPad%' THEN 'mobile'
                WHEN ua LIKE '%iPad%' OR (ua LIKE '%Android%' AND ua NOT LIKE '%Mobile%') THEN 'tablet'
                WHEN ua LIKE '%Windows%' OR ua LIKE '%Macintosh%' OR ua LIKE '%Linux%' THEN 'desktop'
                WHEN ua LIKE '%bot%' OR ua LIKE '%spider%' OR ua LIKE '%crawler%' THEN 'bot'
                ELSE 'unknown'
            END as device, COUNT(*) as count")
            ->where('visit_time', '>=', $startDate)
            ->group('device')
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 受访页面排行
     */
    public function getPageRank()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $limit = min((int) $this->request->get('limit', 20), 50);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('visit_log')
            ->field('page_url, COUNT(*) as pv, COUNT(DISTINCT ip) as uv')
            ->where('visit_time', '>=', $startDate)
            ->group('page_url')
            ->order('pv', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * V2.9.9 B-2: 跳出率
     */
    public function getBounceRate()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getBounceRate($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.9 B-2: 浏览器分布
     */
    public function getBrowserStats()
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getBrowserStats($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.9 B-2: 热门内容+停留时长
     */
    public function getTopContentWithDuration()
    {
        try {
            $limit = (int) $this->request->get('limit', 10);
            $days = (int) $this->request->get('days', 7);
            $data = \app\common\service\DashboardService::getTopContentWithDuration($limit, $days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.9.9 B-2: DAU/MAU
     */
    public function getDauMau()
    {
        try {
            $days = (int) $this->request->get('days', 30);
            if ($days > 90) $days = 90;
            $data = \app\common\service\DashboardService::getDauMau($days);
            return json(['code' => 0, 'data' => $data]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
