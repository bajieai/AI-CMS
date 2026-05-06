<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Db;

/**
 * 访问日志归档管理后台控制器
 */
class VisitArchiveController extends AdminBaseController
{
    /**
     * 归档管理页面
     */
    public function index()
    {
        $list = Db::name('visit_log_archive')
            ->order('period', 'desc')
            ->select();
        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        $this->assign('list', $list);
        return $this->view('/visit_archive_index');
    }

    /**
     * 手动触发归档
     */
    public function runArchive()
    {
        try {
            $monthsAgo = (int) $this->request->post('months', 6);
            $command = new \app\common\command\VisitArchive();
            // 通过Artisan调用
            \think\facade\Console::call('visit:archive', ['--months' => $monthsAgo]);
            return json(['code' => 0, 'msg' => '归档任务已执行']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '归档失败: ' . $e->getMessage()]);
        }
    }
}
