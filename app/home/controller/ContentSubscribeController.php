<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\ContentSubscription;

/**
 * 内容订阅前台控制器 - V2.9.29 Sprint I-7
 */
class ContentSubscribeController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function toggle()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $userId = $this->memberInfo['id'] ?? 0;
        if ($userId <= 0) return $this->error('请先登录', 1, ['login_url' => '/member/login']);

        $type = $this->request->post('subscribe_type', 'category');
        $targetId = (int) $this->request->post('subscribe_id', 0);
        $emailNotify = (int) $this->request->post('notify_email', 0);
        $digestFreq = $this->request->post('digest_frequency', 'instant');

        $exists = ContentSubscription::where('user_id', $userId)
            ->where('subscribe_type', $type)
            ->where('subscribe_id', $targetId)
            ->find();
        if ($exists) {
            $exists->delete();
            return $this->success('已取消订阅', ['subscribed' => false]);
        }
        ContentSubscription::create([
            'user_id' => $userId,
            'subscribe_type' => $type,
            'subscribe_id' => $targetId,
            'notify_email' => $emailNotify,
            'notify_site' => 1,
            'digest_frequency' => $digestFreq,
        ]);
        return $this->success('订阅成功', ['subscribed' => true]);
    }

    public function mySubscriptions()
    {
        $userId = $this->memberInfo['id'] ?? 0;
        $list = ContentSubscription::where('user_id', $userId)->order('id', 'desc')->paginate(15);
        $this->assign('list', $list);
        return $this->view('/my_subscriptions');
    }
}
