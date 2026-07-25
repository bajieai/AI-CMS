<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginStoreAdminService;

/**
 * 插件商店后台控制器 — V2.9.36 Sprint PLUG-SHOP
 */
class PluginStoreAdminController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 后台总览
     */
    public function index()
    {
        $service = new PluginStoreAdminService();
        $overview = $service->getOverview();
        $orderStats = $service->getOrderStats();

        $this->assign([
            'overview'    => $overview,
            'order_stats' => $orderStats,
            'menuActive'  => 'plugin_store_admin',
        ]);

        return $this->view('/plugin_store/admin');
    }

    /**
     * 统计趋势
     */
    public function stats()
    {
        $days = (int) $this->request->get('days', 30);
        $service = new PluginStoreAdminService();
        $stats = $service->getStoreStats($days);

        if ($this->isRealAjax()) {
            return $this->success('ok', $stats);
        }

        $this->assign([
            'stats'      => $stats,
            'days'       => $days,
            'menuActive' => 'plugin_store_admin',
        ]);

        return $this->view('/plugin_store/admin');
    }

    /**
     * 审核插件（上架/下架）
     */
    public function auditPlugin()
    {
        $id = (int) $this->request->post('id', 0);
        $action = $this->request->post('action', '');

        if ($id <= 0 || empty($action)) {
            return $this->error('参数错误');
        }

        $service = new PluginStoreAdminService();
        $result = $service->auditPlugin($id, $action);

        if ($result['code'] === 0) {
            $this->recordLog('审核插件', "ID:{$id} Action:{$action}");
        }

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 设置精选
     */
    public function setFeatured()
    {
        $id = (int) $this->request->post('id', 0);
        $featured = (bool) $this->request->post('featured', false);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $service = new PluginStoreAdminService();
        $result = $service->setFeatured($id, $featured);

        if ($result['code'] === 0) {
            $this->recordLog('设置插件精选', "ID:{$id} Featured:" . ($featured ? 1 : 0));
        }

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }
}
