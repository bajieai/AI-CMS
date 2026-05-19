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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\User;
use think\facade\Config;

/**
 * 后台登录控制器
 * 注意：此控制器不经过认证中间件
 */
class LoginController extends AdminBaseController
{
    protected array $noNeedLogin = ['index'];
    protected array $noNeedPermission = ['index', 'logout'];

    /**
     * 登录页面/处理登录
     */
    public function index()
    {
        if ($this->request->isGet()) {
            // 已登录则跳转后台首页
            if (!empty(session('user_id'))) {
                return redirect('/admin');
            }
            
            return $this->view('/login');
        }

        // POST处理登录
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        if (empty($username) || empty($password)) {
            return $this->error('请输入用户名和密码');
        }

        $user = User::where('username', $username)->find();
        if (empty($user)) {
            return $this->error('用户不存在');
        }

        if ($user->status !== 1) {
            return $this->error('账号已被禁用');
        }

        if (!password_verify($password, $user->password)) {
            return $this->error('密码错误');
        }

        // 记录登录信息
        session('user_id', $user->id);
        session('username', $user->username);
        session('role_id', $user->role_id);
        session('nickname', $user->nickname ?: $user->username);

        // 生成初始CSRF Token（会话级持久化，登录时生成一次）
        session('__token__', md5(uniqid((string) mt_rand(), true)));

        // 更新最后登录信息
        $user->last_login_time = time();
        $user->last_login_ip = $this->request->ip();
        $user->save();

        return $this->success('登录成功', ['redirect' => '/admin']);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session(null);
        return redirect('/admin/login');
    }
}
