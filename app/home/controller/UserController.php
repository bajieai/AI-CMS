<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
