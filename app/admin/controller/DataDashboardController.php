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
use app\common\service\data\DataDashboardService;
use app\admin\model\DataDashboard as DataDashboardModel;

/**
 * 数据大屏控制器 - V2.9.39 DATA-DEEP-1
 */
class DataDashboardController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 大屏首页
     */
    public function index()
    {
        try {
            $service = new DataDashboardService();
            $data = $service->getAllData();
        } catch (\Throwable $e) {
            $data = [];
        }
        $this->assign('data', $data);
        return $this->view('/dashboard/data/index');
    }

    /**
     * API: 全量数据
     */
    public function all()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getAllData());
    }

    /**
     * API: 实时访客数据
     */
    public function realtime()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getRealtimeVisitors());
    }

    /**
     * API: 内容总览
     */
    public function content()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getContentOverview());
    }

    /**
     * API: 用户分析
     */
    public function users()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getUserAnalysis());
    }

    /**
     * API: AI能力统计
     */
    public function ai()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getAiCapability());
    }

    /**
     * API: 收入统计
     */
    public function revenue()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getRevenueStats());
    }

    /**
     * API: 内容质量
     */
    public function quality()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getContentQuality());
    }

    /**
     * API: 性能监控
     */
    public function performance()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getPerformanceMonitor());
    }

    /**
     * API: 移动端数据
     */
    public function mobile()
    {
        $service = new DataDashboardService();
        return $this->success('ok', $service->getMobileData());
    }

    // ========================================================================
    // 大屏配置管理
    // ========================================================================

    /**
     * 大屏配置列表
     */
    public function configList()
    {
        $list = DataDashboardModel::order('id', 'desc')->select()->toArray();
        return $this->success('ok', $list);
    }

    /**
     * 保存大屏配置
     */
    public function saveConfig()
    {
        $data = $this->request->param();
        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {
            $model = DataDashboardModel::find($id);
            if (!$model) {
                return $this->error('大屏配置不存在');
            }
            $model->save($data);
        } else {
            if (!empty($data['is_public']) && empty($data['share_token'])) {
                $data['share_token'] = DataDashboardModel::generateShareToken();
            }
            $model = new DataDashboardModel($data);
            $model->save();
            $id = $model->id;
        }

        DataDashboardService::clearCache();
        return $this->success('保存成功', ['id' => $id]);
    }

    /**
     * 删除大屏配置
     */
    public function deleteConfig()
    {
        $id = (int) $this->request->param('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $model = DataDashboardModel::find($id);
        if (!$model) {
            return $this->error('大屏配置不存在');
        }

        $model->delete();
        DataDashboardService::clearCache();
        return $this->success('删除成功');
    }

    /**
     * 公开大屏（通过分享Token访问）
     */
    public function publicView()
    {
        $token = $this->request->param('token', '');
        $model = DataDashboardModel::getByShareToken($token);
        if (!$model) {
            return $this->error('无效的分享链接');
        }

        $service = new DataDashboardService();
        $data = $service->getAllData();
        $this->assign('data', $data);
        $this->assign('dashboard', $model);
        return $this->view('/dashboard/data/public');
    }

    /**
     * 清除大屏缓存
     */
    public function clearCache()
    {
        DataDashboardService::clearCache();
        return $this->success('缓存已清除');
    }
}
