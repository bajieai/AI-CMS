<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;

/**
 * 前台首页控制器
 */
class IndexController extends FrontBaseController
{
    /**
     * 首页
     * 数据获取已迁移至模板 I8j 标签，控制器保持轻量
     */
    public function index()
    {
        return $this->view('/index');
    }
}
