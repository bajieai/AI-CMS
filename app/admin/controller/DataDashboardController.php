<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\DataDashboardService;

/**
 * 运营数据看板控制器 - V2.9.13
 *
 * 菜单名：运营分析（与"数据看板"/DashboardController区分）
 */
class DataDashboardController extends AdminBaseController
{
    /**
     * 看板首页
     */
    public function index()
    {
        $days = (int) $this->request->param('days', 7);
        $overview = DataDashboardService::getOverview($days);
        $trend = DataDashboardService::getTrend($days);
        $categoryDist = DataDashboardService::getCategoryDist();
        $hotContent = DataDashboardService::getHotContent(10);

        $this->assign([
            'overview'      => $overview,
            'trend'         => $trend,
            'categoryDist'  => $categoryDist,
            'hotContent'    => $hotContent,
            'days'          => $days,
        ]);

        return $this->view('/dashboard/data/index');
    }

    /**
     * API: 概览数据
     */
    public function overview()
    {
        $days = (int) $this->request->param('days', 7);
        $data = DataDashboardService::getOverview($days);
        return json(['success' => true, 'data' => $data]);
    }

    /**
     * API: 趋势数据
     */
    public function trend()
    {
        $days = (int) $this->request->param('days', 7);
        $data = DataDashboardService::getTrend($days);
        return json(['success' => true, 'data' => $data]);
    }

    /**
     * API: 内容分类分布
     */
    public function category()
    {
        $data = DataDashboardService::getCategoryDist();
        return json(['success' => true, 'data' => $data]);
    }

    /**
     * API: 热门内容
     */
    public function hotContent()
    {
        $limit = (int) $this->request->param('limit', 10);
        $data = DataDashboardService::getHotContent($limit);
        return json(['success' => true, 'data' => $data]);
    }

    /**
     * API: 运营日报
     */
    public function report()
    {
        $days = (int) $this->request->param('days', 1);
        $data = DataDashboardService::getReport($days);
        return json(['success' => true, 'data' => $data]);
    }
}
