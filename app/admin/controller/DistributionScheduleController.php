<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\channel\DistributionScheduleService;
use app\common\service\channel\WeChatChannelService;
use app\common\service\channel\PlatformChannelService;

/**
 * 定时分发控制器 — V2.9.34 CD-4 / CD-1/CD-2分发执行
 */
class DistributionScheduleController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new DistributionScheduleService();
        $pendingList = $service->getPendingList();
        $autoRules = $service->getAutoRules();
        $this->assign('pendingList', $pendingList);
        $this->assign('autoRules', $autoRules);
        $this->assign('menuActive', 'distribution_schedule');
        return $this->view('/distribution_schedule/index');
    }

    public function create()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $platforms = $this->request->post('platforms', []);
        $scheduleTime = (int)$this->request->post('schedule_time', 0);
        $service = new DistributionScheduleService();
        $result = $service->create($contentId, $platforms, $scheduleTime);
        if ($result['success'] ?? false) {
            return $this->success('创建成功', $result);
        }
        return $this->error($result['message'] ?? '创建失败');
    }

    public function cancel()
    {
        $scheduleId = (int)$this->request->param('id', 0);
        $service = new DistributionScheduleService();
        $result = $service->cancel($scheduleId);
        if ($result['success'] ?? false) {
            return $this->success('取消成功');
        }
        return $this->error($result['message'] ?? '取消失败');
    }

    public function execute()
    {
        $scheduleId = (int)$this->request->param('id', 0);
        $service = new DistributionScheduleService();
        $result = $service->execute($scheduleId);
        if ($result['success'] ?? false) {
            return $this->success('执行成功', $result);
        }
        return $this->error($result['message'] ?? '执行失败');
    }

    public function saveAutoRule()
    {
        $rule = $this->request->post();
        $service = new DistributionScheduleService();
        $result = $service->saveAutoRule($rule);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    /**
     * CD-1: 微信公众号分发
     */
    public function publishWechat()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $accountId = (int)$this->request->post('account_id', 0);
        $service = new WeChatChannelService();
        $result = $service->publish($contentId, $accountId);
        if ($result['success'] ?? false) {
            return $this->success('分发成功', $result);
        }
        return $this->error($result['message'] ?? '分发失败');
    }

    /**
     * CD-2: 平台分发(头条/知乎/微博)
     */
    public function publishPlatform()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $accountId = (int)$this->request->post('account_id', 0);
        $service = new PlatformChannelService();
        $result = $service->publish($contentId, $accountId);
        if ($result['success'] ?? false) {
            return $this->success('分发成功', $result);
        }
        return $this->error($result['message'] ?? '分发失败');
    }
}
