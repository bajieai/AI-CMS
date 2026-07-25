<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-2: 灰度管理后台控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\system\GrayscaleReleaseService;
use think\App;

/**
 * 灰度管理后台控制器 - V2.9.39 SYS-ROBUST-2
 */
class GrayscaleController extends AdminBaseController
{
    protected GrayscaleReleaseService $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new GrayscaleReleaseService();
    }

    /**
     * 灰度计划列表
     */
    public function index()
    {
        $params = $this->request->get();
        $result = $this->service->getList($params);

        return $this->view('/grayscale/index', $result);
    }

    /**
     * 创建灰度计划
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['creator_id'] = (int) session('user_id');
            $result = $this->service->create($data);

            if (!empty($result['id'])) {
                $this->recordLog('grayscale_create', "创建灰度计划 #{$result['id']}");
                return $this->success('创建成功', $result);
            }
            return $this->error('创建失败');
        }

        return $this->view('/grayscale/create');
    }

    /**
     * 编辑灰度计划
     */
    public function edit()
    {
        $id = (int) $this->request->get('id', 0);

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->service->update($id, $data);

            if ($result) {
                $this->recordLog('grayscale_edit', "编辑灰度计划 #{$id}");
                return $this->success('更新成功');
            }
            return $this->error('更新失败');
        }

        $plan = $this->service->getDetail($id);
        return $this->view('/grayscale/edit', ['plan' => $plan]);
    }

    /**
     * 启动灰度
     */
    public function activate()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->service->activate($id);

        if ($result['success']) {
            $this->recordLog('grayscale_activate', "启动灰度计划 #{$id}");
            return $this->success('已启动');
        }
        return $this->error($result['msg'] ?? '启动失败');
    }

    /**
     * 暂停灰度
     */
    public function pause()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->service->pause($id);

        if ($result['success']) {
            $this->recordLog('grayscale_pause', "暂停灰度计划 #{$id}");
            return $this->success('已暂停');
        }
        return $this->error('暂停失败');
    }

    /**
     * 回滚灰度
     */
    public function rollback()
    {
        $id = (int) $this->request->post('id', 0);
        $reason = $this->request->post('reason', '');
        $result = $this->service->rollback($id, $reason);

        if ($result['success']) {
            $this->recordLog('grayscale_rollback', "回滚灰度计划 #{$id}");
            return $this->success('已回滚');
        }
        return $this->error($result['msg'] ?? '回滚失败');
    }

    /**
     * 完成灰度（全量上线）
     */
    public function complete()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->service->complete($id);

        if ($result['success']) {
            $this->recordLog('grayscale_complete', "灰度全量上线 #{$id}");
            return $this->success('已全量上线');
        }
        return $this->error('操作失败');
    }

    /**
     * 删除灰度计划
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->service->delete($id);

        if ($result['success']) {
            $this->recordLog('grayscale_delete', "删除灰度计划 #{$id}");
            return $this->success('已删除');
        }
        return $this->error($result['msg'] ?? '删除失败');
    }

    /**
     * 灰度监控
     */
    public function metrics()
    {
        $id = (int) $this->request->get('id', 0);
        $detail = $this->service->getDetail($id);

        return $this->view('/grayscale/metrics', ['plan' => $detail]);
    }
}
