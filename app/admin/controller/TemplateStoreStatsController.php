<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateStatsAggregator;

/**
 * 模板商店统计看板控制器 — V2.9.28 M-3
 */
class TemplateStoreStatsController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 统计看板首页
     */
    public function index()
    {
        $service = new TemplateStatsAggregator();
        $stats = $service->getDashboardStats();

        $this->assign([
            'stats' => $stats,
            'menuActive' => 'template_store_stats',
        ]);

        return $this->view('/template_store/stats_dashboard');
    }

    /**
     * 模板排行榜
     */
    public function ranking()
    {
        $orderBy = $this->request->get('order_by', 'revenue');
        $service = new TemplateStatsAggregator();
        $list = $service->getTopRanking(50, $orderBy);

        $this->assign([
            'list' => $list,
            'orderBy' => $orderBy,
            'menuActive' => 'template_store_stats',
        ]);

        return $this->view('/template_store/stats_ranking');
    }

    /**
     * 收入趋势（AJAX）
     */
    public function revenueTrend()
    {
        $days = (int)$this->request->get('days', 30);
        $service = new TemplateStatsAggregator();
        $trend = $service->getRevenueTrend($days);
        return json(['code' => 0, 'data' => $trend]);
    }

    /**
     * 手动触发每日聚合
     */
    public function aggregate()
    {
        $date = $this->request->post('date', '');
        $service = new TemplateStatsAggregator();
        $result = $service->aggregateDaily($date);
        $this->recordLog('手动聚合统计', $result['date'] ?? '');
        return $this->success('聚合完成');
    }
}
