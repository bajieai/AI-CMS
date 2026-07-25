<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\system\UnifiedNotifyService;
use app\common\service\system\NotifyChannelManager;
use app\common\service\system\NotifySubscriptionService;
use think\facade\Json;

/**
 * 统一通知中心控制器
 * V2.9.38 SYS-INTEG-5
 */
class NotifyCenterController extends AdminBaseController
{
    protected UnifiedNotifyService $notifyService;
    protected NotifyChannelManager $channelManager;
    protected NotifySubscriptionService $subscriptionService;

    public function __construct()
    {
        parent::__construct(app());
        $this->notifyService = new UnifiedNotifyService();
        $this->channelManager = new NotifyChannelManager();
        $this->subscriptionService = new NotifySubscriptionService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $query = \think\facade\Db::name('notify_log')->order('id', 'desc');
        if ($scenario = $this->request->param('scenario')) $query->where('notify_scenario', $scenario);
        $total = $query->count();
        $list = $query->page($page, 20)->select()->toArray();
        return $this->view('notify_center/index', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function templates()
    {
        $templates = \think\facade\Db::name('notify_template')->order('id', 'desc')->paginate(20);
        return $this->view('notify_center/templates', ['templates' => $templates]);
    }

    public function channels()
    {
        $channels = $this->channelManager->listChannels();
        $stats = $this->channelManager->getChannelStats();
        return $this->view('notify_center/channels', ['channels' => $channels, 'stats' => $stats]);
    }

    public function enableChannel()
    {
        $channel = $this->request->param('channel', '');
        $this->channelManager->enableChannel($channel);
        return Json::success('已启用');
    }

    public function disableChannel()
    {
        $channel = $this->request->param('channel', '');
        $this->channelManager->disableChannel($channel);
        return Json::success('已禁用');
    }

    public function testChannel()
    {
        $channel = $this->request->param('channel', '');
        $result = $this->channelManager->testChannel($channel);
        return Json::success('ok', $result);
    }

    public function subscriptions()
    {
        return $this->view('notify_center/subscriptions');
    }

    public function send()
    {
        $to = $this->request->param('to', '');
        $scenario = $this->request->param('scenario', '');
        $params = $this->request->param('params', []);
        $channels = $this->request->param('channels', null);
        $result = $this->notifyService->send($to, $scenario, $params, $channels);
        return Json::success('发送完成', $result);
    }
}
