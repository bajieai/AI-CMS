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
        $query = PointsExchange::with(['member', 'product'])->order('id', 'desc');
        if ($status >= 0) {
            $query->where('status', $status);
        }
        $list = $query->paginate(20);
        $this->assign('list', $list);
        return $this->view('/points_exchange_index');
    }

    /**
     * 兑换记录详情 - V2.7新增
     */
    public function detail(int $id = 0)
    {
        $record = PointsExchange::with(['member', 'product'])->find($id);
        if (!$record) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }
        return json(['code' => 0, 'data' => $record]);
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

    /**
     * V2.7: 发货管理（虚拟商品发放/实物快递单号）
     */
    public function deliver()
    {
        $id = (int) $this->request->post('id', 0);
        $deliveryType = $this->request->post('delivery_type', ''); // virtual|express
        $deliveryInfo = $this->request->post('delivery_info', '');

        $record = PointsExchange::find($id);
        if (!$record) return json(['code' => 1, 'msg' => '记录不存在']);
        if ($record->status != 1) {
            return json(['code' => 1, 'msg' => '仅已审核的记录可发货']);
        }

        $info = $record->delivery_info ?: [];
        $info['delivery_type'] = $deliveryType;
        $info['delivery_info'] = $deliveryInfo;
        $info['deliver_time'] = time();

        $record->delivery_info = $info;
        $record->status = 2; // 已发货
        $record->update_time = time();
        $record->save();

        $this->recordLog('积分兑换发货', "ID:{$id}, type:{$deliveryType}");
        return json(['code' => 0, 'msg' => '发货成功']);
    }
}
