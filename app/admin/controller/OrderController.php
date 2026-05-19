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
use app\common\model\Order;

/**
 * V2.9.4 订单管理控制器
 */
class OrderController extends AdminBaseController
{
    /**
     * 订单列表
     */
    public function index()
    {
        $status = $this->request->get('status', '');
        $source = $this->request->get('source', '');
        $keyword = $this->request->get('keyword', '');
        $page = (int) $this->request->get('page', 1);

        $query = Order::order('id', 'desc');

        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if (!empty($source)) {
            $query->where('source', $source);
        }
        if (!empty($keyword)) {
            $query->where('order_no', 'like', "%{$keyword}%");
        }

        $list = $query->page($page, 20)->select();
        $total = $query->count();

        $this->assign('orders', $list);
        $this->assign('total', $total);
        $this->assign('page', $page);
        $this->assign('status', $status);
        $this->assign('source', $source);
        $this->assign('keyword', $keyword);

        return $this->view('/order_list');
    }

    /**
     * 订单详情
     */
    public function detail(int $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->error('订单不存在');
        }

        $this->assign('order', $order);
        return $this->view('/order_detail');
    }

    /**
     * 关闭订单
     */
    public function close()
    {
        $id = (int) $this->request->post('id', 0);
        $order = Order::find($id);
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        try {
            $order->close();
            return json(['code' => 0, 'msg' => '订单已关闭']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
