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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\MailLog;

/**
 * 邮件发送日志控制器 - V2.9.18 D-3
 */
class MailLogController extends AdminBaseController
{
    public function index()
    {
        return $this->view('/mail_log');
    }

    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);

        $query = MailLog::order('id', 'desc');
        $total = $query->count();
        $data  = $query->page($page, $pageSize)->select();

        return $this->success('ok', ['data' => $data, 'total' => $total]);
    }
}
