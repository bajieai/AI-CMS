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
use app\common\service\SigninService;
use app\common\service\PointsService;
use app\common\model\PointsLog;

/**
 * 前台签到控制器
 */
class SigninController extends FrontBaseController
{
    /**
     * 签到页面
     */
    public function index()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = (int) $this->memberInfo['id'];
        $calendar = SigninService::getCalendar($memberId);

        $this->assign('calendar', $calendar);
        $this->assign('member', $this->memberInfo);
        $this->assign('ucenter_active', 'signin');
        return $this->view('/signin');
    }

    /**
     * 执行签到（AJAX）
     */
    public function doSignin()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        try {
            $result = SigninService::signin((int) $this->memberInfo['id']);
            return json(['code' => 0, 'msg' => '签到成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * V2.7: 签到积分记录
     */
    public function pointsLog()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = (int) $this->memberInfo['id'];
        $list = PointsLog::where('member_id', $memberId)
            ->where('type', 'signin')
            ->order('id', 'desc')
            ->paginate(20);

        $this->assign('list', $list);
        $this->assign('member', $this->memberInfo);
        $this->assign('ucenter_active', 'signin');
        return $this->view('/signin_points_log');
    }
}
