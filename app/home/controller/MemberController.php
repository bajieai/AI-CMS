<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Notification as NotificationModel;
use app\common\service\MemberFavoriteService;
use app\common\service\CaptchaService;
use app\common\service\MemberLevelService;
use app\common\service\MemberService;
use app\common\service\NotificationService;
use app\common\service\UploadService;
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
            // V2.9.9: 验证码校验
            if (CaptchaService::isFormCaptchaRequired('register')) {
                $captchaKey = $data['captcha_key'] ?? '';
                $captchaAnswer = $data['captcha_answer'] ?? '';
                if (empty($captchaKey) || empty($captchaAnswer)) {
                    return json(['success' => false, 'msg' => '请完成验证码验证']);
                }
                if (!CaptchaService::verify($captchaKey, $captchaAnswer)) {
                    return json(['success' => false, 'msg' => '验证码错误']);
                }
            }
            $result = $this->service->register($data);
            return json($result);
        }
        return $this->view('/member_register');
    }

    /**
     * 获取验证码
     */
    public function captcha()
    {
        try {
            $data = CaptchaService::generate();
            if (empty($data['image'])) {
                throw new \RuntimeException('验证码生成失败：图片为空');
            }
            return json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            // 降级：返回纯文本验证码（Docker内GD可能无中文字体）
            $a = random_int(1, 20);
            $b = random_int(1, 20);
            $answer = $a + $b;
            $key = 'captcha_' . md5(uniqid((string) mt_rand(), true));
            \think\facade\Cache::set($key, (string) $answer, 300);
            return json(['success' => true, 'data' => [
                'key'   => $key,
                'image' => '',
                'text'  => "{$a} + {$b} = ?",
            ]]);
        }
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
     * V2.9.10-fix: 旧URL 301重定向到用户中心统一入口
     */
    public function home()
    {
        return redirect('/member/index', 301);
    }

    /**
     * V2.9.10: 个人首页（用户中心入口）
     */
    public function index()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = $this->memberInfo['id'];

        // 汇总统计数据
        $stats = [
            'points'      => (int) ($this->memberInfo['points'] ?? 0),
            'favorite'    => \app\common\model\MemberFavorite::where('member_id', $memberId)->count(),
            'comment'     => \app\common\model\Comment::where('member_id', $memberId)->count(),
            'notification'=> \app\common\model\Notification::where('receiver_type', 'member')->where('receiver_id', $memberId)->where('is_read', 0)->count(),
        ];

        // 最近通知
        $recentNotifications = \app\common\model\Notification::where('receiver_type', 'member')
            ->where('receiver_id', $memberId)
            ->order('create_time', 'desc')
            ->limit(5)
            ->select();

        return $this->view('/member_index', [
            'member' => $this->memberInfo,
            'stats' => $stats,
            'recent_notifications' => $recentNotifications,
            'ucenter_active' => 'index',
        ]);
    }

    /**
     * V2.9.10: 我的订单
     */
    public function orders()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = \app\common\model\Order::where('user_id', $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        return $this->view('/member_orders', [
            'list' => $list,
            'member' => $this->memberInfo,
            'ucenter_active' => 'orders',
        ]);
    }

    /**
     * V2.9.10: 我的评论
     */
    public function comments()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = \app\common\model\Comment::with('content')
            ->where('member_id', $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        return $this->view('/member_comments', [
            'list' => $list,
            'member' => $this->memberInfo,
            'ucenter_active' => 'comments',
        ]);
    }

    /**
     * 个人资料
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

        return $this->view('/member_profile', [
            'member' => $this->memberInfo,
            'ucenter_active' => 'profile',
        ]);
    }

    /**
     * V2.8: 我的邀请（邀请返积分）
     */
    public function invite()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = (int) $this->memberInfo['id'];
        
        // 生成或获取邀请码
        $inviteCode = \app\common\model\InviteLog::where('inviter_id', $memberId)->value('invite_code');
        if (!$inviteCode) {
            $inviteCode = \app\common\model\InviteLog::generateCode($memberId);
            // 创建一条自引用记录作为邀请码持有者
            $log = new \app\common\model\InviteLog();
            $log->save([
                'inviter_id' => $memberId,
                'invitee_id' => 0,
                'invite_code' => $inviteCode,
                'invitee_ip' => '',
                'reward_points' => 0,
                'reward_stage' => 0,
                'create_time' => time(),
            ]);
        }
        
        // 邀请统计
        $inviteCount = \app\common\model\InviteLog::where('inviter_id', $memberId)->where('invitee_id', '>', 0)->count();
        $invitePoints = \app\common\model\InviteLog::where('inviter_id', $memberId)->sum('reward_points') ?? 0;
        
        // 邀请列表
        $inviteList = \app\common\model\InviteLog::where('inviter_id', $memberId)
            ->where('invitee_id', '>', 0)
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        return $this->view('/member_invite', [
            'invite_code' => $inviteCode,
            'invite_count' => $inviteCount,
            'invite_points' => $invitePoints,
            'invite_list' => $inviteList,
            'ucenter_active' => 'invite',
        ]);
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
            'ucenter_active' => 'points',
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
        $result = $service->getList((int) $this->memberInfo['id'], 1, 20);

        return $this->view('/member_favorite', [
            'list' => $result['data'] ?? [],
            'ucenter_active' => 'favorite',
        ]);
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

        $memberId = $this->memberInfo['id'];
        $type = $this->request->get('type', '');

        $query = NotificationModel::where('receiver_type', 'member')
            ->where('receiver_id', $memberId);

        $validTypes = ['system', 'review', 'publish', 'comment_reply', 'level_upgrade', 'level_downgrade', 'level_grace_warning', 'content_approve', 'content_reject', 'reward_receive'];
        if ($type && in_array($type, $validTypes)) {
            $query->where('type', $type);
        } elseif ($type === 'content_audit') {
            $query->whereIn('type', ['content_approve', 'content_reject']);
        }

        $list = $query->order('create_time', 'desc')->paginate(20);

        $unreadCount = NotificationModel::where('receiver_type', 'member')
            ->where('receiver_id', $memberId)
            ->where('is_read', 0)
            ->count();

        // V2.9.5 分类未读统计
        $typeCounts = [];
        try {
            $typeCounts = NotificationModel::where('receiver_type', 'member')
                ->where('receiver_id', $memberId)
                ->where('is_read', 0)
                ->group('type')
                ->column('count(*)', 'type');
        } catch (\Throwable) {}

        // V2.9.19 N-1b: 通知概览统计
        $notifStats = NotificationService::getStats($memberId);

        return $this->view('/member_notification', [
            'list' => $list,
            'unread_count' => $unreadCount,
            'type_counts' => $typeCounts,
            'current_type' => $type,
            'notif_stats' => $notifStats,
            'ucenter_active' => 'notification',
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

        $list = \app\common\model\PointsExchange::where('user_id', $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        return $this->view('/member_exchange_log', [
            'list' => $list,
            'member' => $this->memberInfo,
            'ucenter_active' => 'exchange',
        ]);
    }

    /**
     * 会员头像上传（AJAX）
     */
    public function uploadAvatar(Request $request)
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        $file = $request->file('file');
        if (empty($file)) {
            return json(['code' => 1, 'msg' => '请选择文件']);
        }

        try {
            $service = new UploadService();
            $result = $service->uploadImage($file);
            // 自动保存到头像字段
            $this->service->updateProfile((int) $this->memberInfo['id'], ['avatar' => $result['url']]);
            return json(['code' => 0, 'msg' => '头像上传成功', 'data' => ['url' => $result['url']]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
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

    /**
     * V2.9.10: 我的优惠券
     */
    public function coupon()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $list = \app\common\model\UserCoupon::where('member_id', (int) $this->memberInfo['id'])
            ->order('id', 'desc')
            ->paginate(20);

        return $this->view('/member_coupon', [
            'list' => $list,
            'member' => $this->memberInfo,
            'ucenter_active' => 'coupon',
        ]);
    }

    /**
     * V2.9.3 M20: 会员等级进度页
     */
    public function level()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = (int) $this->memberInfo['id'];
        $progress = MemberLevelService::getLevelProgress($memberId);
        $levels = MemberLevelService::getList();
        // 确保等级数据中可能存在但数据库无此字段的key有默认值，防止模板报错
        foreach ($levels as &$lv) {
            $lv['_daily_ai_quota'] = (int) ($lv['daily_ai_quota'] ?? 0);
        }
        unset($lv);

        // V2.9.5 等级历史时间线
        $timeline = \app\common\model\MemberDowngradeLog::getTimeline($memberId);

        return $this->view('/member_level', [
            'progress' => $progress,
            'levels' => $levels,
            'member' => $this->memberInfo,
            'timeline' => $timeline,
            'ucenter_active' => 'level',
        ]);
    }

    /**
     * V2.9.18 U-1: 我的发布
     */
    public function publish()
    {
        $memberId = $this->memberInfo['id'] ?? 0;
        if (!$memberId) return redirect('/member/login');

        $page = (int) request()->get('page', 1);
        $status = request()->get('status', '');

        $query = \app\common\model\Content::where('user_id', $memberId)
            ->order('id', 'desc');

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->page($page, 15)->select();

        return $this->view('/member_publish', [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'statusFilter' => $status,
            'ucenter_active' => 'publish',
        ]);
    }

    /**
     * V2.9.19 U-1: 内容统计面板
     */
    public function stats()
    {
        $memberId = $this->memberInfo['id'] ?? 0;
        if (!$memberId) return redirect('/member/login');

        $cacheKey = 'member_stats_' . $memberId;
        $cacheTag = 'member';

        $stats = \think\facade\Cache::tag($cacheTag)->remember($cacheKey, function () use ($memberId) {
            $totalPublished = \app\common\model\Content::where('user_id', $memberId)
                ->where('status', 1)->count();
            $monthPublished = \app\common\model\Content::where('user_id', $memberId)
                ->where('status', 1)
                ->whereTime('create_time', 'month')
                ->count();
            $totalViews = (int) \app\common\model\Content::where('user_id', $memberId)
                ->sum('views');
            $totalShares = \app\common\model\ShareClick::whereIn('content_id', function ($q) use ($memberId) {
                $q->name('id')->from('content')->where('user_id', $memberId);
            })->count();
            $avgViews = $totalPublished > 0 ? round($totalViews / $totalPublished) : 0;

            return compact('totalPublished', 'monthPublished', 'totalViews', 'totalShares', 'avgViews');
        }, 60);

        // 近30天阅读趋势
        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayViews = \app\common\model\Content::where('user_id', $memberId)
                ->where('status', 1)
                ->whereDate('update_time', $date)
                ->sum('views');
            $trend[] = ['date' => $date, 'views' => (int) $dayViews];
        }

        // 阅读量 TOP5
        $top5 = \app\common\model\Content::where('user_id', $memberId)
            ->where('status', 1)
            ->order('views', 'desc')
            ->limit(5)
            ->select();

        return $this->view('/member_stats', [
            'stats'  => $stats,
            'trend'  => $trend,
            'top5'   => $top5,
            'ucenter_active' => 'stats',
        ]);
    }

    /**
     * V2.9.18 U-1: 偏好设置
     */
    public function preferences(Request $request)
    {
        $memberId = $this->memberInfo['id'] ?? 0;
        if (!$memberId) return redirect('/member/login');

        if ($request->isPost()) {
            $langPref  = $request->post('lang_pref', '');
            $emailNotify = (int) $request->post('email_notify', 0);
            $notifyOn   = (int) $request->post('notify_on', 1);

            \app\common\model\Member::where('id', $memberId)->update([
                'lang_pref'    => $langPref,
                'email_notify' => $emailNotify,
                'notify_on'    => $notifyOn,
                'update_time'  => time(),
            ]);

            return json(['code' => 0, 'msg' => '偏好设置已保存']);
        }

        $member = \app\common\model\Member::find($memberId);
        $languages = \app\common\model\TranslateLanguage::where('status', 1)->select();

        return $this->view('/member_preferences', [
            'member'    => $member,
            'languages' => $languages,
            'ucenter_active' => 'preferences',
        ]);
    }
}
