<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\CollectLog;

/**
 * 采集日志后台控制器 - V2.5新增
 */
class CollectLogController extends AdminBaseController
{
    public function index()
    {
        $sourceId = (int) $this->request->param('source_id', 0);
        $query = CollectLog::order('id', 'desc');
        if ($sourceId > 0) {
            $query->where('source_id', $sourceId);
        }

        $list = $query->paginate(['list_rows' => 20, 'path' => '/admin/collect_log/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/collect_log_index');
    }

    public function detail(int $id)
    {
        $log = CollectLog::find($id);
        if (!$log) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        $this->assign('info', $log);
        return $this->view('/collect_log_detail');
    }
}
