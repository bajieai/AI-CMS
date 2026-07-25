<?php
declare(strict_types=1);

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\controller\AdminBaseController;

/**
 * 统计报表控制器
 */
class StatsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $this->assign('menuActive', 'stats');
        return $this->view('/stats/index');
    }
}
