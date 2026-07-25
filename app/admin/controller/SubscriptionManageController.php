<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\member\SubscriptionService;

/**
 * 订阅管理控制器 — V2.9.34 MEM-4
 */
class SubscriptionManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new SubscriptionService();
        $expiringSoon = $service->getExpiringSoon(7);
        $stats = $service->getStats();
        $this->assign('expiringSoon', $expiringSoon);
        $this->assign('stats', $stats);
        $this->assign('menuActive', 'subscription_manage');
        return $this->view('/subscription_manage/index');
    }

    public function checkVip()
    {
        $memberId = (int)$this->request->param('member_id', 0);
        $service = new SubscriptionService();
        $result = $service->checkVipStatus($memberId);
        return json($result);
    }

    public function unsubscribe()
    {
        $subscriptionId = (int)$this->request->param('id', 0);
        $service = new SubscriptionService();
        $result = $service->unsubscribe($subscriptionId);
        if ($result['success'] ?? false) {
            return $this->success('取消成功');
        }
        return $this->error($result['message'] ?? '取消失败');
    }
}
