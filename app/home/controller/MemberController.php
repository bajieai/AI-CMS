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
use app\common\model\Notification;
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

            // V2.9.54: 手机号+短信验证码注册分支（与用户名+邮箱注册并存，后台开关控制）
            $isPhoneRegister = !empty($data['mobile'])
                || ($data['register_type'] ?? '') === 'phone'
                || (!empty($data['sms_code']) && empty($data['username']));
            if ($isPhoneRegister) {
                // 手机号注册的防刷已在发码接口完成（图形验证码+频率限制），此处只做短信验证码校验
                $result = $this->service->registerByPhone($data);
                return json($result);
            }

            // V2.9.59: 邮箱验证码核验分支（用户名注册 + 邮箱验证码，后台开关控制）
            if (!empty($data['email_code'])
                && (int) \app\common\service\ConfigService::get('member_register_email_code_enabled', 0)) {
                $result = $this->service->registerByEmailCode($data);
                return json($result);
            }

            // V2.9.9: 验证码校验（用户名+邮箱注册）
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
        // V2.9.54: 注册页模板变量（图形验证码按后台配置动态显示；手机号注册开关全局已assign）
        $this->assign('captcha_required', CaptchaService::isFormCaptchaRequired('register'));
        return $this->view('/member_register');
    }

    /**
     * V2.9.54: 发送注册短信验证码
     * 防刷三层：后台开关 → 图形验证码（复用注册表单开关）→ SmsService 内置（60s频率/单IP日限10次/验证码5分钟）
     */
    public function sendSmsCode(Request $request)
    {
        // 后台开关校验
        if (!(int) \app\common\service\ConfigService::get('member_register_phone_enabled', 0)) {
            return json(['success' => false, 'msg' => '手机号注册未启用']);
        }

        $data = $request->post();

        // 图形验证码防刷（与注册表单共用 captcha_register 配置，防止脚本刷短信造成费用损失）
        if (CaptchaService::isFormCaptchaRequired('register')) {
            $captchaKey = (string) ($data['captcha_key'] ?? '');
            $captchaAnswer = (string) ($data['captcha_answer'] ?? '');
            if ($captchaKey === '' || $captchaAnswer === '' || !CaptchaService::verify($captchaKey, $captchaAnswer)) {
                return json(['success' => false, 'msg' => '请先完成图形验证码验证', 'refresh_captcha' => true]);
            }
        }

        // 手机号格式校验
        $mobile = trim((string) ($data['mobile'] ?? ''));
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json(['success' => false, 'msg' => '请输入正确的手机号']);
        }

        // 手机号唯一性预检（友好提示）
        if (\app\common\model\Member::where('mobile', $mobile)->find()) {
            return json(['success' => false, 'msg' => '该手机号已注册，请直接登录']);
        }

        // 发送（SmsService 内置：60秒/手机号频率限制、单IP每日10次、验证码5分钟有效）
        try {
            (new \app\common\service\system\SmsService())->sendVerifyCode($mobile, 'register');
        } catch (\Throwable $e) {
            // 常见：短信通道未配置、发送频率限制、通道故障
            return json(['success' => false, 'msg' => '短信发送失败：' . $e->getMessage()]);
        }

        return json(['success' => true, 'msg' => '验证码已发送，5分钟内有效']);
    }

    /**
     * V2.9.59: 发送注册邮箱验证码（用户名注册方式的邮箱真实性核验）
     * 防刷：后台开关 → 图形验证码（复用注册表单配置）→ 60秒/邮箱频率 + 单IP每日20次
     */
    public function sendEmailCode(Request $request)
    {
        // 后台开关校验
        if (!(int) \app\common\service\ConfigService::get('member_register_email_code_enabled', 0)) {
            return json(['success' => false, 'msg' => '邮箱验证码核验未启用']);
        }

        $data = $request->post();
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        // 邮箱格式校验
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => false, 'msg' => '请输入正确的邮箱地址']);
        }

        // 图形验证码防刷（与注册表单共用 captcha_register 配置，防止脚本刷邮件）
        if (CaptchaService::isFormCaptchaRequired('register')) {
            $captchaKey = (string) ($data['captcha_key'] ?? '');
            $captchaAnswer = (string) ($data['captcha_answer'] ?? '');
            if ($captchaKey === '' || $captchaAnswer === '' || !CaptchaService::verify($captchaKey, $captchaAnswer)) {
                return json(['success' => false, 'msg' => '请先完成图形验证码验证', 'refresh_captcha' => true]);
            }
        }

        // 邮箱唯一性预检（友好提示）
        if (\app\common\model\Member::where('email', $email)->find()) {
            return json(['success' => false, 'msg' => '该邮箱已被注册，请直接登录']);
        }

        // 频率限制：60秒/邮箱（防止单邮箱被骚扰）
        $freqKey = 'email_freq_' . md5($email);
        $lastSend = \think\facade\Cache::get($freqKey);
        if ($lastSend && (time() - (int) $lastSend) < 60) {
            return json(['success' => false, 'msg' => '发送过于频繁，请60秒后再试']);
        }

        // IP 每日上限 20 次（防批量刷邮件，邮件配额比短信宽松）
        $ipKey = 'email_ip_' . ($request->ip() ?? '0.0.0.0');
        $ipCount = (int) \think\facade\Cache::get($ipKey, 0);
        if ($ipCount >= 20) {
            return json(['success' => false, 'msg' => '该IP今日发送次数已达上限']);
        }

        // 生成验证码（5分钟有效）
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = 'email_code_register_' . $email;
        \think\facade\Cache::set($cacheKey, $code, 300);

        // 发送邮件（EmailService：与密码找回同链路，SMTP 未配置时返回 false）
        $siteName = \app\common\service\ConfigService::get('site_name', 'AI-CMS');
        $subject = '【' . $siteName . '】邮箱验证码';
        $body = "<p>您的验证码是：<strong style='font-size:24px;letter-spacing:4px'>{$code}</strong></p><p>有效期5分钟，请勿泄露给他人。</p>";
        $sent = \app\common\service\EmailService::send($email, $subject, $body);

        if (!$sent) {
            // V2.9.59: 发送失败删除验证码缓存（防止用未送达的码注册，与 SmsService 同修复）
            \think\facade\Cache::delete($cacheKey);
            \think\facade\Cache::set($freqKey, time(), 60);
            return json(['success' => false, 'msg' => '邮件发送失败，请联系管理员检查邮件配置']);
        }

        \think\facade\Cache::set($freqKey, time(), 60);
        \think\facade\Cache::set($ipKey, $ipCount + 1, 86400);

        return json(['success' => true, 'msg' => '验证码已发送到您的邮箱，5分钟内有效']);
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

            // 检查是否需要验证码
            $captchaEnabled = (int) \app\common\model\Config::getValue('captcha_enabled', '0');
            $captchaLogin   = (int) \app\common\model\Config::getValue('captcha_login', '0');
            if ($captchaEnabled && $captchaLogin) {
                $captchaKey  = $request->post('captcha_key', '');
                $captchaCode = $request->post('captcha', '');
                if (empty($captchaKey) || empty($captchaCode)) {
                    return json(['success' => false, 'msg' => '请输入验证码']);
                }
                if (!\app\common\service\CaptchaService::verify($captchaKey, $captchaCode)) {
                    return json(['success' => false, 'msg' => '验证码错误或已过期']);
                }
            }

            $result = $this->service->login($username, $password);
            return json($result);
        }

        // GET: 传递验证码开关状态到模板
        $captchaEnabled = (int) \app\common\model\Config::getValue('captcha_enabled', '0');
        $captchaLogin   = (int) \app\common\model\Config::getValue('captcha_login', '0');
        $this->assign('captcha_login', $captchaEnabled && $captchaLogin ? 1 : 0);
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
     * 忘记密码 - 发送重置邮件
     */
    public function forgotPassword(Request $request)
    {
        if ($request->isPost()) {
            $email = trim($request->post('email', ''));
            if (empty($email)) {
                return json(['success' => false, 'msg' => '请输入邮箱']);
            }
            $authService = new \app\common\service\AuthService();
            $result = $authService->sendPasswordResetEmail($email);
            return json(['success' => $result['code'] === 0, 'msg' => $result['msg']]);
        }
        return $this->view('/member_forgot');
    }

    /**
     * 重置密码
     */
    public function passwordReset(Request $request)
    {
        $token = trim($request->param('token', ''));
        if (empty($token)) {
            return redirect('/member/password/forgot')->with('error', '重置链接无效');
        }

        if ($request->isPost()) {
            $newPassword = $request->post('password', '');
            $confirmPassword = $request->post('confirm_password', '');
            if (empty($newPassword)) {
                return json(['success' => false, 'msg' => '请输入新密码']);
            }
            if ($newPassword !== $confirmPassword) {
                return json(['success' => false, 'msg' => '两次密码不一致']);
            }
            $authService = new \app\common\service\AuthService();
            $result = $authService->resetPassword($token, $newPassword);
            return json(['success' => $result['code'] === 0, 'msg' => $result['msg']]);
        }

        $this->assign('token', $token);
        return $this->view('/member_reset');
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
    /**
     * 修改密码
     */
    public function changePassword(Request $request)
    {
        if (!$this->isMemberLogin) {
            return json(['success' => false, 'msg' => '请先登录']);
        }
        try {
            $result = $this->service->changePassword(
                (int) $this->memberInfo['id'],
                $request->post('old_password', ''),
                $request->post('new_password', '')
            );
            return json($result);
        } catch (\Throwable $e) {
            return json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function profile(Request $request)
    {
        try {
            if (!$this->isMemberLogin) {
                return redirect('/member/login');
            }

            if ($request->isPost()) {
                $data = $request->post();
                $result = $this->service->updateProfile((int) $this->memberInfo['id'], $data);
                // 如果同时填了新旧密码，一并修改
                $oldPwd = $request->post('old_password', '');
                $newPwd = $request->post('new_password', '');
                if (!empty($oldPwd) && !empty($newPwd)) {
                    $pwdResult = $this->service->changePassword((int) $this->memberInfo['id'], $oldPwd, $newPwd);
                    if (!$pwdResult['success']) {
                        return json($pwdResult);
                    }
                }
                return json($result);
            }

            return $this->view('/member_profile', [
                'member' => $this->memberInfo,
                'ucenter_active' => 'profile',
            ]);
        } catch (\Throwable $e) {
            if ($request->isPost()) {
                return json(['success' => false, 'msg' => $e->getMessage()]);
            }
            return $this->view('/member_profile', [
                'member' => $this->memberInfo ?? [],
                'ucenter_active' => 'profile',
                'error_msg' => $e->getMessage(),
            ]);
        }
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
        
        // V2.9.42: 邀请码直接从 member 表读取（注册时已自动生成）
        $inviteCode = \app\common\model\Member::where('id', $memberId)->value('invite_code');
        if (empty($inviteCode)) {
            // 兼容旧数据：生成并回写
            $inviteCode = strtoupper(substr(md5(uniqid((string)$memberId, true)), 0, 8));
            \app\common\model\Member::where('id', $memberId)->update(['invite_code' => $inviteCode]);
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
        try {
            if (!$this->isMemberLogin) {
                return redirect('/member/login');
            }

            $service = new MemberFavoriteService();
            $result = $service->getList((int) $this->memberInfo['id'], 1, 20);

            return $this->view('/member_favorite', [
                'list' => $result['data'] ?? [],
                'ucenter_active' => 'favorite',
            ]);
        } catch (\Throwable $e) {
            return $this->view('/member_favorite', [
                'list' => [],
                'ucenter_active' => 'favorite',
                'error_msg' => $e->getMessage(),
            ]);
        }
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

        try {

        $memberId = $this->memberInfo['id'];
        $type = $this->request->get('type', '');

        $query = Notification::where('receiver_type', 'member')
            ->where('receiver_id', $memberId);

        $validTypes = ['system', 'review', 'publish', 'comment_reply', 'level_upgrade', 'level_downgrade', 'level_grace_warning', 'content_approve', 'content_reject', 'reward_receive'];
        if ($type && in_array($type, $validTypes)) {
            $query->where('type', $type);
        } elseif ($type === 'content_audit') {
            $query->whereIn('type', ['content_approve', 'content_reject']);
        }

        $list = $query->order('create_time', 'desc')->paginate(20);

        $unreadCount = Notification::where('receiver_type', 'member')
            ->where('receiver_id', $memberId)
            ->where('is_read', 0)
            ->count();

        // V2.9.5 分类未读统计
        $typeCounts = [];
        try {
            $typeCounts = Notification::where('receiver_type', 'member')
                ->where('receiver_id', $memberId)
                ->where('is_read', 0)
                ->group('type')
                ->column('count(*)', 'type');
        } catch (\Throwable) {}

        // V2.9.19 N-1b: 通知概览统计
        $notifStats = NotificationService::getStats((int) $memberId);

        return $this->view('/member_notification', [
            'list' => $list,
            'unread_count' => $unreadCount,
            'type_counts' => $typeCounts,
            'current_type' => $type,
            'notif_stats' => $notifStats,
            'ucenter_active' => 'notification',
        ]);

        } catch (\Throwable $e) {
            return $this->view('/member_notification', [
                'list' => [],
                'unread_count' => 0,
                'type_counts' => [],
                'current_type' => '',
                'notif_stats' => [],
                'ucenter_active' => 'notification',
                'error_msg' => $e->getMessage(),
            ]);
        }
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
        Notification::where('id', $id)
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

        Notification::where('receiver_type', 'member')
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

        $stats = \think\facade\Cache::remember($cacheKey, function () use ($memberId) {
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
