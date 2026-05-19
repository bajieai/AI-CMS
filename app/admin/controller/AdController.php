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
use app\common\model\Ad as AdModel;
use app\common\model\AdPosition as AdPositionModel;
use think\Request;

class AdController extends AdminBaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $list = AdModel::with('position')->order('create_time', 'desc')->paginate(15, false, ['page' => $page]);
        return $this->view('/ad_list', ['list' => $list]);
    }

    public function positionIndex(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $list = AdPositionModel::order('create_time', 'desc')->paginate(15, false, ['page' => $page]);
        return $this->view('/ad_position_list', ['list' => $list]);
    }

    /**
     * 添加广告位
     */
    public function positionAdd(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            $validate = $this->validatePosition($data);
            if ($validate !== true) {
                return $this->error($validate);
            }

            $model = new AdPositionModel();
            if ($model->save($data)) {
                $this->recordLog('添加广告位', $data['name'] ?? '', $data);
                return $this->success('添加成功', ['redirect' => '/admin/ad_position/index']);
            }
            return $this->error('添加失败');
        }

        return $this->view('/ad_position_edit', ['info' => null]);
    }

    /**
     * 编辑广告位
     */
    public function positionEdit(int $id, Request $request)
    {
        $info = AdPositionModel::find($id);
        if (empty($info)) {
            return $this->error('广告位不存在');
        }

        if ($request->isPost()) {
            $data = $request->post();
            $validate = $this->validatePosition($data);
            if ($validate !== true) {
                return $this->error($validate);
            }

            if ($info->save($data)) {
                $this->recordLog('编辑广告位', $data['name'] ?? '', $data);
                return $this->success('更新成功', ['redirect' => '/admin/ad_position/index']);
            }
            return $this->error('更新失败');
        }

        return $this->view('/ad_position_edit', ['info' => $info]);
    }

    /**
     * 删除广告位
     */
    public function positionDelete(int $id)
    {
        $info = AdPositionModel::find($id);
        if (empty($info)) {
            return $this->error('广告位不存在');
        }

        // 检查广告位下是否有广告
        if ($info->ads()->count() > 0) {
            return $this->error('该广告位下存在广告，请先移出或删除广告');
        }

        if ($info->delete()) {
            $this->recordLog('删除广告位', $info['name'] ?? '', []);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 添加广告
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            $validate = $this->validateAd($data);
            if ($validate !== true) {
                return $this->error($validate);
            }

            $model = new AdModel();
            if ($model->save($data)) {
                $this->recordLog('添加广告', $data['title'] ?? '', $data);
                return $this->success('添加成功', ['redirect' => '/admin/ad/index']);
            }
            return $this->error('添加失败');
        }

        $positions = AdPositionModel::where('status', 1)->order('id', 'asc')->select();
        return $this->view('/ad_edit', ['info' => null, 'positions' => $positions]);
    }

    /**
     * 编辑广告
     */
    public function edit(int $id, Request $request)
    {
        $info = AdModel::find($id);
        if (empty($info)) {
            return $this->error('广告不存在');
        }

        if ($request->isPost()) {
            $data = $request->post();
            $validate = $this->validateAd($data);
            if ($validate !== true) {
                return $this->error($validate);
            }

            if ($info->save($data)) {
                $this->recordLog('编辑广告', $data['title'] ?? '', $data);
                return $this->success('更新成功', ['redirect' => '/admin/ad/index']);
            }
            return $this->error('更新失败');
        }

        $positions = AdPositionModel::where('status', 1)->order('id', 'asc')->select();
        return $this->view('/ad_edit', ['info' => $info, 'positions' => $positions]);
    }

    /**
     * 删除广告
     */
    public function delete(int $id)
    {
        $info = AdModel::find($id);
        if (empty($info)) {
            return $this->error('广告不存在');
        }

        if ($info->delete()) {
            $this->recordLog('删除广告', $info['title'] ?? '', []);
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 广告统计
     */
    public function stat(Request $request)
    {
        $adId = (int) $request->get('ad_id', 0);
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $service = new \app\common\service\AdService();
        $stats = $service->getStats($adId, $startDate, $endDate);

        return $this->view('/ad_stat', [
            'stats' => $stats,
            'ad_id' => $adId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * 验证广告位数据
     */
    protected function validatePosition(array $data): bool|string
    {
        if (empty($data['name'])) {
            return '广告位名称不能为空';
        }
        if (empty($data['code'])) {
            return '广告位标识不能为空';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['code'])) {
            return '广告位标识只能包含字母、数字和下划线';
        }
        if (mb_strlen($data['name']) > 100) {
            return '广告位名称不能超过100个字符';
        }
        return true;
    }

    /**
     * 验证广告数据
     */
    protected function validateAd(array $data): bool|string
    {
        if (empty($data['title'])) {
            return '广告标题不能为空';
        }
        if (empty($data['position_id'])) {
            return '请选择广告位';
        }
        if (mb_strlen($data['title']) > 100) {
            return '广告标题不能超过100个字符';
        }
        return true;
    }
}
