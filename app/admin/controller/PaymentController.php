<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PaymentService;
use app\common\model\PaymentLog;
use app\common\model\PaidOrder;

/**
 * 支付管理后台控制器 - V2.5新增
 */
class PaymentController extends AdminBaseController
{
    /**
     * 支付记录列表
     */
    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $limit = 20;
        $status = $this->request->param('status', '');

        $query = PaidOrder::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page,
            'path' => '/admin/payment/index',
        ]);

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/payment_index');
    }

    /**
     * 支付配置
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $data = [
                'wechat_mch_id'      => $this->request->post('wechat_mch_id', ''),
                'wechat_app_id'      => $this->request->post('wechat_app_id', ''),
                'wechat_mch_serial'  => $this->request->post('wechat_mch_serial', ''),
                'wechat_api_key_v3'  => $this->request->post('wechat_api_key_v3', ''),
                'wechat_notify_url'  => $this->request->post('wechat_notify_url', ''),
                'wechat_enabled'     => (int) $this->request->post('wechat_enabled', 0),
            ];

            try {
                foreach ($data as $key => $value) {
                    \app\common\service\ConfigService::set('payment_' . $key, $value);
                }
                \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_CONFIG);
                return json(['code' => 0, 'msg' => '配置保存成功']);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        // 读取当前配置
        $config = [];
        foreach (['wechat_mch_id', 'wechat_app_id', 'wechat_mch_serial', 'wechat_api_key_v3', 'wechat_notify_url', 'wechat_enabled'] as $key) {
            $config[$key] = \app\common\service\ConfigService::get('payment_' . $key, '');
        }

        $this->assign('config', $config);
        return $this->view('/payment_config');
    }

    /**
     * 退款
     */
    public function refund()
    {
        $orderId = (int) $this->request->post('order_id', 0);
        $reason = $this->request->post('reason', '');

        if ($orderId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $result = PaymentService::refund($orderId, $reason);
            return json(['code' => 0, 'msg' => '退款申请已提交', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 收入统计
     */
    public function revenue()
    {
        try {
            $stats = PaymentService::getRevenueStats();
        } catch (\Exception $e) {
            $stats = ['today_revenue' => 0, 'month_revenue' => 0, 'total_revenue' => 0, 'today_orders' => 0, 'month_orders' => 0, 'total_orders' => 0];
        }

        $this->assign('stats', $stats);
        return $this->view('/revenue_index');
    }
}
