<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PaidOrder;

/**
 * 付费订单后台管理控制器
 */
class PaidOrderController extends AdminBaseController
{
    /**
     * 订单列表
     */
    public function index()
    {
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $status = $this->request->get('status', '');

        $query = PaidOrder::with(['member', 'content'])->order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->page($page, $limit)->select();
        $total = $query->count();

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list, 'count' => $total]);
        }
        $this->assign('list', $list);
        return $this->view('/paid_order_index');
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $id = (int) $this->request->get('id', 0);
        $order = PaidOrder::with(['member', 'content'])->find($id);
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        return json(['code' => 0, 'data' => $order]);
    }

    /**
     * 退款
     */
    public function refund()
    {
        $id = (int) $this->request->post('id', 0);
        $order = PaidOrder::find($id);
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }
        if ($order->status != 1) {
            return json(['code' => 1, 'msg' => '仅已支付订单可退款']);
        }

        $order->status = 2; // 已退款
        $order->save();

        // 退回积分
        if ($order->pay_type === 'points') {
            try {
                \app\common\service\PointsService::add(
                    $order->member_id,
                    (int) $order->price,
                    'refund',
                    $order->content_id,
                    '订单退款: ' . $order->order_sn
                );
            } catch (\Throwable) {}
        }

        return json(['code' => 0, 'msg' => '退款成功']);
    }

    /**
     * 付费统计
     */
    public function stats()
    {
        $todayStart = strtotime('today');
        $monthStart = strtotime(date('Y-m-01'));

        $totalRevenue = PaidOrder::where('status', 1)->sum('price');
        $todayRevenue = PaidOrder::where('status', 1)->where('paid_at', '>=', $todayStart)->sum('price');
        $monthRevenue = PaidOrder::where('status', 1)->where('paid_at', '>=', $monthStart)->sum('price');

        $totalOrders = PaidOrder::where('status', 1)->count();
        $todayOrders = PaidOrder::where('status', 1)->where('paid_at', '>=', $todayStart)->count();

        return json([
            'code' => 0,
            'data' => [
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'total_orders'  => $totalOrders,
                'today_orders'  => $todayOrders,
            ],
        ]);
    }
}
