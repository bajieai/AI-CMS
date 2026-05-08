<?php
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
     * 来源分析
     */
    public function getSourceStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('visit_log')
            ->field('source_type, COUNT(*) as count')
            ->where('visit_time', '>=', $startDate)
            ->group('source_type')
            ->select()
            ->toArray();
        
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
}
