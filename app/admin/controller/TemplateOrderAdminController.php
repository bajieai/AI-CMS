<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\admin\TemplateOrderAdminService;
use app\common\service\template\TemplateOrderService;
use app\common\service\template\TemplateRefundService;
use app\common\service\template\TemplateInvoiceService;

/**
 * 模板订单管理控制器 — V2.9.27 U-2, V2.9.28 M-1增强
 */
class TemplateOrderAdminController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 订单列表
     */
    public function index()
    {
        $params = $this->request->param();
        $result = TemplateOrderAdminService::getOrderList($params, 20);
        $stats = TemplateOrderAdminService::getStats();
        $this->assign(['list' => $result['data'] ?? [], 'stats' => $stats, 'params' => $params, 'menuActive' => 'template_order_admin']);
        return $this->view('/template_store/order_list');
    }

    /**
     * 订单详情（M-1a 增强：显示完整订单信息）
     */
    public function detail(int $id)
    {
        $order = TemplateOrderService::getOrderDetail($id);
        if (!$order) return $this->error('订单不存在');

        // 获取退款记录
        $refundService = new TemplateRefundService();
        $refundList = \app\common\model\TemplateRefund::where('order_id', $id)->order('id', 'desc')->select()->toArray();

        // 获取发票记录
        $invoiceService = new TemplateInvoiceService();
        $invoiceList = \app\common\model\TemplateInvoice::where('order_id', $id)->order('id', 'desc')->select()->toArray();

        $this->assign([
            'order' => $order,
            'refundList' => $refundList,
            'invoiceList' => $invoiceList,
            'menuActive' => 'template_order_admin',
        ]);
        return $this->view('/template_store/order_detail');
    }

    /**
     * 退款处理页面
     */
    public function refundHandle(int $id)
    {
        $refund = \app\common\model\TemplateRefund::with('order.template')->find($id);
        if (!$refund) return $this->error('退款记录不存在');

        $this->assign(['refund' => $refund, 'menuActive' => 'template_order_admin']);
        return $this->view('/template_store/refund_handle');
    }

    /**
     * 审核退款
     */
    public function approveRefund(int $id)
    {
        $remark = $this->request->post('admin_remark', '');
        $service = new TemplateRefundService();
        $result = $service->approveRefund($id, $this->adminInfo['id'] ?? 0, $remark);
        if ($result['success']) {
            $this->recordLog('审核退款通过', "退款ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 拒绝退款
     */
    public function rejectRefund(int $id)
    {
        $remark = $this->request->post('admin_remark', '');
        if (empty($remark)) return $this->error('请填写拒绝原因');

        $service = new TemplateRefundService();
        $result = $service->rejectRefund($id, $this->adminInfo['id'] ?? 0, $remark);
        if ($result['success']) {
            $this->recordLog('拒绝退款', "退款ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 旧版退款接口（保持兼容）
     */
    public function refund(int $id)
    {
        $reason = $this->request->post('reason', '');
        if (empty($reason)) return $this->error('请输入退款原因');
        $result = TemplateOrderAdminService::refundOrder($id, $reason);
        $this->recordLog('模板订单退款', '订单ID:' . $id . ' 原因:' . $reason);
        return $result['success'] ? $this->success($result['msg']) : $this->error($result['msg']);
    }

    /**
     * 发票管理列表
     */
    public function invoiceList()
    {
        $params = $this->request->get();
        $service = new TemplateInvoiceService();
        $data = $service->getList($params, 20);
        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'menuActive' => 'template_order_admin',
        ]);
        return $this->view('/template_store/invoice_list');
    }

    /**
     * 开具发票
     */
    public function issueInvoice(int $id)
    {
        $invoiceNo = $this->request->post('invoice_no', '');
        $invoiceFile = $this->request->post('invoice_file', '');
        if (empty($invoiceNo)) return $this->error('请填写发票号码');

        $service = new TemplateInvoiceService();
        $result = $service->issue($id, $invoiceNo, $invoiceFile);
        if ($result['success']) {
            $this->recordLog('开具发票', "发票ID:{$id}, 号码:{$invoiceNo}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 拒绝开票
     */
    public function rejectInvoice(int $id)
    {
        $reason = $this->request->post('reason', '');
        $service = new TemplateInvoiceService();
        $result = $service->reject($id, $reason);
        if ($result['success']) {
            $this->recordLog('拒绝开票', "发票ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }
}
