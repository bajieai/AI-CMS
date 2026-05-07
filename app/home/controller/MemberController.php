<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Notification as NotificationModel;
use app\common\service\MemberFavoriteService;
use app\common\service\MemberService;
use think\Request;

/**
 * 前台会员控制器
 */
class MemberController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    protected MemberService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new MemberService;
    }

    /**
     * 注册页面
     */
    public function register(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            $result = $this->service->register($data);
            return json($result);
        }
        return $this->view('/member_register');
    }

    /**
     * 登录页面
     */
    public function login(Request $request)
    {
        if ($request->isPost()) {
            $username = $request->post('username', '');
            $password = $request->post('password', '');
            $result = $this->service->login($username, $password);
            return json($result);
        }
        return $this->view('/member_login');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if ($this->memberInfo) {
            $this->service->logout($this->memberInfo['id']);
        }
        return redirect('/');
    }

    /**
     * 个人中心
     */
    public function profile(Request $request)
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        if ($request->isPost()) {
            $data = $request->post();
            $result = $this->service->updateProfile($this->memberInfo['id'], $data);
            return json($result);
        }

        return $this->view('/member_profile', ['member' => $this->memberInfo]);
    }

    /**
     * V2.7: 我的积分
     */
    public function points()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = \app\common\model\PointsLog::where('member_id', $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        // 连续签到天数（从会员信息获取）
        $consecutiveDays = $this->memberInfo['signin_count'] ?? 0;

        return $this->view('/member_points', [
            'list' => $list,
            'consecutive_days' => $consecutiveDays,
            'member' => $this->memberInfo,
        ]);
    }

    /**
     * 我的收藏
     */
    public function favorite()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $service = new MemberFavoriteService();
        $result = $service->getList($this->memberInfo['id'], 1, 20);

        return $this->view('/member_favorite', ['list' => $result['data'] ?? []]);
    }

    /**
     * 取消收藏（AJAX）
     */
    public function favoriteRemove(Request $request)
    {
        if (!$this->isMemberLogin) {
            return json(['success' => false, 'msg' => '请先登录']);
        }

        $contentId = (int) $request->post('content_id', 0);
        $service = new MemberFavoriteService();
        $result = $service->remove($this->memberInfo['id'], $contentId);
        return json($result);
    }

    /**
     * 消息通知
     */
    public function notification()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = NotificationModel::where('receiver_type', 'member')
            ->where('receiver_id', $this->memberInfo['id'])
            ->order('create_time', 'desc')
            ->select();

        $unreadCount = NotificationModel::where('receiver_type', 'member')
            ->where('receiver_id', $this->memberInfo['id'])
            ->where('is_read', 0)
            ->count();

        return $this->view('/member_notification', [
            'list' => $list,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * 标记通知已读（AJAX）
     */
    public function notificationRead(Request $request)
    {
        if (!$this->isMemberLogin) {
            return json(['success' => false, 'msg' => '请先登录']);
        }

        $id = (int) $request->post('id', 0);
        NotificationModel::where('id', $id)
            ->where('receiver_id', $this->memberInfo['id'])
            ->update(['is_read' => 1]);

        return json(['success' => true, 'msg' => '已读']);
    }

    /**
     * V2.7: 积分兑换记录
     */
    public function exchangeLog()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = \app\common\model\PointsExchange::where('member_id', $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        return $this->view('/member_exchange_log', [
            'list' => $list,
            'member' => $this->memberInfo,
        ]);
    }

    /**
     * 标记所有通知已读（AJAX）
     */
    public function notificationReadAll()
    {
        if (!$this->isMemberLogin) {
            return json(['success' => false, 'msg' => '请先登录']);
        }

        NotificationModel::where('receiver_type', 'member')
            ->where('receiver_id', $this->memberInfo['id'])
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return json(['success' => true, 'msg' => '全部已读']);
    }
}
