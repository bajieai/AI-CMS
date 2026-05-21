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
            $archiveDate = date('Y-m-d', strtotime("-{$monthsAgo} months"));
            $startTime = strtotime($archiveDate . ' 00:00:00');
            $endTime = strtotime($archiveDate . ' 23:59:59');

            $pv = Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->count();
            $uv = Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->group('ip')->count();

            if ($pv > 0) {
                $contentStats = Db::name('visit_log')
                    ->field('content_id, COUNT(*) as pv, COUNT(DISTINCT ip) as uv')
                    ->whereBetween('visit_time', [$startTime, $endTime])
                    ->group('content_id')
                    ->select();

                foreach ($contentStats as $stat) {
                    Db::name('visit_log_archive')->insert([
                        'content_id'  => $stat['content_id'],
                        'stat_date'   => $archiveDate,
                        'pv'          => $stat['pv'],
                        'uv'          => $stat['uv'],
                        'create_time' => time(),
                    ]);
                }

                Db::name('visit_log')->whereBetween('visit_time', [$startTime, $endTime])->delete();
            }

            return json(['code' => 0, 'msg' => "归档完成：{$archiveDate} PV={$pv} UV={$uv}"]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '归档失败: ' . $e->getMessage()]);
        }
    }
}
