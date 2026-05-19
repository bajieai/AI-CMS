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
use app\common\model\EmailLog;

/**
 * 邮件日志后台控制器 - V2.5新增
 */
class EmailLogController extends AdminBaseController
{
    public function index()
    {
        $status = $this->request->param('status', '');
        $query = EmailLog::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate(['list_rows' => 20, 'path' => '/admin/email_log/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/email_log_index');
    }
}
