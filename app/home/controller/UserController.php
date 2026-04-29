<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\User;

/**
 * 前台用户中心控制器
 */
class UserController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 用户中心首页
     */
    public function index()
    {
        $userId = session('user_id');
        if (empty($userId)) {
            return redirect('/admin/login');
        }

        $info = User::find($userId);
        if (empty($info)) {
            return redirect('/admin/login');
        }

        $this->assign(['info' => $info]);
        return $this->view('/user');
    }
}
