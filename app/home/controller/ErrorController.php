<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint SEC: 404/500错误页
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;

/**
 * 错误页面控制器 - V2.9.31 SEC-2
 */
class ErrorController extends FrontBaseController
{
    /**
     * 404页面
     */
    public function notFound()
    {
        return $this->view('/error/404');
    }

    /**
     * 500页面
     */
    public function serverError()
    {
        return $this->view('/error/500');
    }

    /**
     * 403页面
     */
    public function forbidden()
    {
        return $this->view('/error/403');
    }
}
