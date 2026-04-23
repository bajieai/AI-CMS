<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Log as LogModel;

/**
 * 操作日志控制器
 */
class LogController extends AdminBaseController
{
    /**
     * 日志列表
     */
    public function index()
    {
        $list = LogModel::with('user')->order('id', 'desc')->paginate(20);

        $this->assign(['list' => $list]);
        return $this->view('/log_list');
    }
}
