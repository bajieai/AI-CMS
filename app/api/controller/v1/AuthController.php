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

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\common\service\AuthService;
use app\common\service\CaptchaService;

/**
 * 注册登录 API - V2.9.18 U-2
 */
class AuthController extends BaseController
{
    protected AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * 邮箱注册
     */
    public function register()
    {
        $result = $this->authService->registerByEmail($this->request->post());
        return json($result);
    }

    /**
     * 获取图形验证码
     */
    public function getCaptcha()
    {
        $data = CaptchaService::generate();
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 发送邮箱验证码
     */
    public function sendVerifyCode()
    {
        $email = $this->request->post('email', '');
        $result = $this->authService->sendEmailVerifyCode($email);
        return json($result);
    }

    /**
     * 登录（支持用户名或邮箱）
     */
    public function login()
    {
        $account  = $this->request->post('account', '');
        $password = $this->request->post('password', '');

        if (empty($account) || empty($password)) {
            return json(['code' => 1, 'msg' => '请输入账号和密码']);
        }

        $memberService = new \app\common\service\MemberService();
        $result = $memberService->login($account, $password);
        return json($result);
    }

    /**
     * 发送密码重置链接
     */
    public function forgotPassword()
    {
        $email = $this->request->post('email', '');
        $result = $this->authService->sendPasswordResetEmail($email);
        return json($result);
    }

    /**
     * 重置密码
     */
    public function resetPassword()
    {
        $token   = $this->request->post('token', '');
        $password = $this->request->post('password', '');
        $result = $this->authService->resetPassword($token, $password);
        return json($result);
    }
}
