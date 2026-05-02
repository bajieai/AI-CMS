<?php
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
            return $this->redirect(url('home/member/login'));
        }

        $memberId = $this->memberInfo['id'];
        $calendar = SigninService::getCalendar($memberId);

        $this->assign('calendar', $calendar);
        $this->assign('member', $this->memberInfo);
        return $this->view->fetch();
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
            $result = SigninService::signin($this->memberInfo['id']);
            return json(['code' => 0, 'msg' => '签到成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 积分记录
     */
    public function pointsLog()
    {
        if (!$this->isMemberLogin) {
            return $this->redirect(url('home/member/login'));
        }

        $memberId = $this->memberInfo['id'];
        $page = (int) $this->request->get('page', 1);
        $list = PointsLog::where('member_id', $memberId)
            ->order('id', 'desc')
            ->page($page, 20)
            ->select();

        $this->assign('list', $list);
        $this->assign('member', $this->memberInfo);
        return $this->view->fetch();
    }
}
