<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PointsExchange;

/**
 * 积分兑换记录管理控制器 - V2.6
 */
class PointsExchangeController extends AdminBaseController
{
    /**
     * 兑换记录列表
     */
    public function index()
    {
        $this->app->view->assign('menuActive', 'points_exchange');
        $status = (int) $this->request->get('status', -1);
        $query = PointsExchange::order('id', 'desc');
        if ($status >= 0) {
            $query->where('status', $status);
        }
        $list = $query->paginate(20);
        $this->assign('list', $list);
        return $this->view('/points_exchange_index');
    }

    /**
     * 审核兑换记录
     */
    public function audit()
    {
        $id = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 1);
        $remark = $this->request->post('remark', '');

        $record = PointsExchange::find($id);
        if (!$record) return json(['code' => 1, 'msg' => '记录不存在']);

        $record->status = $status;
        $record->remark = $remark;
        $record->update_time = time();
        $record->save();

        $this->recordLog('审核积分兑换', "ID:{$id}, status:{$status}");
        return json(['code' => 0, 'msg' => '操作成功']);
    }
}
