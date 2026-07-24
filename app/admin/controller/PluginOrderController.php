<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginTradeService;

/**
 * 插件订单管理控制器 — V2.9.36 Sprint PLUG-SHOP
 */
class PluginOrderController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 创建订单
     */
    public function create()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $memberId = (int) session('user_id', 0);

        if ($pluginId <= 0) {
            return $this->error('参数错误');
        }
        if ($memberId <= 0) {
            return $this->error('请先登录');
        }

        $service = new PluginTradeService();
        $result = $service->createOrder($pluginId, $memberId);

        if ($result['code'] === 0) {
            $this->recordLog('创建插件订单', $result['data']['order_no'] ?? '');
        }

        return $result['code'] === 0
            ? $this->success($result['msg'], $result['data'] ?? [])
            : $this->error($result['msg']);
    }

    /**
     * 订单列表
     */
    public function list()
    {
        $memberId = (int) session('user_id', 0);
        $page = (int) $this->request->get('page', 1);

        $service = new PluginTradeService();
        $result = $service->getOrderList($memberId, $page);

        $this->assign([
            'list'       => $result['list'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/order');
    }

    /**
     * 订单详情
     */
    public function detail(int $id)
    {
        $service = new PluginTradeService();
        $order = $service->getOrderById($id);

        if (!$order) {
            return $this->error('订单不存在');
        }

        $this->assign([
            'order'      => $order,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/order');
    }

    /**
     * 取消订单
     */
    public function cancel(int $id)
    {
        $service = new PluginTradeService();
        $result = $service->cancelOrder($id);

        if ($result['code'] === 0) {
            $this->recordLog('取消插件订单', (string) $id);
        }

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 退款
     */
    public function refund(int $id)
    {
        $service = new PluginTradeService();
        $result = $service->refundOrder($id);

        if ($result['code'] === 0) {
            $this->recordLog('插件订单退款', (string) $id);
        }

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }
}
