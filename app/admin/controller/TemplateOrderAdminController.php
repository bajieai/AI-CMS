<?php
declare(strict_types=1);
namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\admin\TemplateOrderAdminService;
use app\common\service\template\TemplateOrderService;

class TemplateOrderAdminController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $params = $this->request->param();
        $list = TemplateOrderAdminService::getOrderList($params, 20);
        $stats = TemplateOrderAdminService::getStats();
        $this->assign(['list' => $list, 'stats' => $stats, 'params' => $params]);
        return $this->view('/template_order_admin_index');
    }

    public function detail(int $id)
    {
        $order = TemplateOrderService::getOrderDetail($id);
        if (!$order) return $this->error('订单不存在');
        $this->assign('order', $order);
        return $this->view('/template_order_admin_detail');
    }

    public function refund(int $id)
    {
        $reason = $this->request->post('reason', '');
        if (empty($reason)) return $this->error('请输入退款原因');
        $result = TemplateOrderAdminService::refundOrder($id, $reason);
        $this->recordLog('模板订单退款', '订单ID:' . $id . ' 原因:' . $reason);
        return $result['success'] ? $this->success($result['msg']) : $this->error($result['msg']);
    }
}
