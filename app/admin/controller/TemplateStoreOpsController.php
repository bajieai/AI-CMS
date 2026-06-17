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
use app\common\service\TemplateStoreOpsService;
use app\common\model\TemplateBanner;
use app\common\model\TemplateRecommend;
use app\common\model\TemplateInstallLog;
use app\common\model\TemplateStoreCategory;

/**
 * 模板商店运营控制器 - V2.9.24 G-1~G-5
 * Banner管理 / 推荐位配置 / 统计看板 / 评论批量管理
 */
class TemplateStoreOpsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    // ==================== G-1: Banner管理 ====================

    /**
     * Banner列表
     */
    public function bannerIndex()
    {
        $service = new TemplateStoreOpsService();
        $params = $this->request->get();
        $data = $service->getBannerList($params);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'targetTypes' => TemplateBanner::$targetTypeMap,
            'params' => $params,
            'menuActive' => 'template_store_banner',
        ]);

        return $this->view('/template_store/banner_list');
    }

    /**
     * Banner添加/编辑
     */
    public function bannerEdit(int $id = 0)
    {
        $service = new TemplateStoreOpsService();

        if ($this->request->isGet()) {
            $info = $id > 0 ? TemplateBanner::find($id) : null;
            $this->assign([
                'info' => $info,
                'targetTypes' => TemplateBanner::$targetTypeMap,
                'menuActive' => 'template_store_banner',
            ]);
            return $this->view('/template_store/banner_edit');
        }

        $data = $this->request->post();
        $result = $service->saveBanner($data, $id);

        if ($result['success']) {
            $this->recordLog($id > 0 ? '编辑Banner' : '添加Banner', $data['title'] ?? '', $data);
            return $this->success($result['message'], ['redirect' => '/admin/template_store_ops/bannerIndex']);
        }
        return $this->error($result['message']);
    }

    /**
     * Banner删除
     */
    public function bannerDelete(int $id)
    {
        $service = new TemplateStoreOpsService();
        $result = $service->deleteBanner($id);

        if ($result['success']) {
            $this->recordLog('删除Banner', "ID: {$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * Banner排序
     */
    public function bannerSort()
    {
        $ids = $this->request->post('ids/a', []);
        if (empty($ids)) {
            return $this->error('参数错误');
        }

        $service = new TemplateStoreOpsService();
        $result = $service->sortBanners($ids);
        return $this->success($result['message']);
    }

    // ==================== G-2: 推荐位配置 ====================

    /**
     * 推荐位列表
     */
    public function recommendIndex()
    {
        $service = new TemplateStoreOpsService();
        $params = $this->request->get();
        $data = $service->getRecommendList($params);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'positions' => TemplateRecommend::$positionMap,
            'recommendTypes' => TemplateRecommend::$recommendTypeMap,
            'params' => $params,
            'menuActive' => 'template_store_recommend',
        ]);

        return $this->view('/template_store/recommend_list');
    }

    /**
     * 推荐位添加/编辑
     */
    public function recommendEdit(int $id = 0)
    {
        $service = new TemplateStoreOpsService();

        if ($this->request->isGet()) {
            $info = $id > 0 ? TemplateRecommend::with('template')->find($id) : null;
            $this->assign([
                'info' => $info,
                'positions' => TemplateRecommend::$positionMap,
                'recommendTypes' => TemplateRecommend::$recommendTypeMap,
                'menuActive' => 'template_store_recommend',
            ]);
            return $this->view('/template_store/recommend_edit');
        }

        $data = $this->request->post();
        $result = $service->saveRecommend($data, $id);

        if ($result['success']) {
            $this->recordLog($id > 0 ? '编辑推荐位' : '添加推荐位', TemplateRecommend::$positionMap[$data['position']] ?? '', $data);
            return $this->success($result['message'], ['redirect' => '/admin/template_store_ops/recommendIndex']);
        }
        return $this->error($result['message']);
    }

    /**
     * 推荐位删除
     */
    public function recommendDelete(int $id)
    {
        $service = new TemplateStoreOpsService();
        $result = $service->deleteRecommend($id);

        if ($result['success']) {
            $this->recordLog('删除推荐位', "ID: {$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    // ==================== G-4: 统计看板 ====================

    /**
     * 统计看板
     */
    public function statsDashboard()
    {
        $service = new TemplateStoreOpsService();
        $params = $this->request->get();
        $stats = $service->getDashboardStats($params['start'] ?? '', $params['end'] ?? '');

        $this->assign([
            'stats' => $stats,
            'params' => $params,
            'menuActive' => 'template_store_stats',
        ]);

        return $this->view('/template_store/stats_dashboard');
    }

    /**
     * 导出CSV
     */
    public function statsExport()
    {
        $params = $this->request->get();
        $service = new TemplateStoreOpsService();
        $csv = $service->exportCsv($params);

        $filename = 'template_stats_' . date('Ymd') . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }

    /**
     * 基线迁移（Q4方案C+A）
     */
    public function migrateBaseline()
    {
        $service = new TemplateStoreOpsService();
        $result = $service->migrateBaseline();

        if ($result['success']) {
            $this->recordLog('基线迁移', $result['message']);
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    // ==================== G-5: 评论批量管理 ====================

    /**
     * 评论批量管理
     */
    public function reviewBatch()
    {
        $params = $this->request->get();
        $query = \app\common\model\TemplateReview::with('template')
            ->order('id', 'desc');

        if (!empty($params['status']) && $params['status'] !== 'all') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['keyword'])) {
            $query->where('content', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['template_id'])) {
            $query->where('store_id', (int)$params['template_id']);
        }
        if (!empty($params['rating'])) {
            $query->where('rating', (int)$params['rating']);
        }
        if (!empty($params['start_date'])) {
            $query->where('create_time', '>=', strtotime($params['start_date'] . ' 00:00:00'));
        }
        if (!empty($params['end_date'])) {
            $query->where('create_time', '<=', strtotime($params['end_date'] . ' 23:59:59'));
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        $this->assign([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
            'params' => $params,
            'menuActive' => 'template_store_review',
        ]);

        return $this->view('/template_store/review_batch');
    }

    /**
     * 评论批量审核
     */
    public function reviewBatchAudit()
    {
        $ids = $this->request->post('ids/a', []);
        $status = (int)$this->request->post('status', 1);
        $reply = $this->request->post('reply', '');

        if (empty($ids)) {
            return $this->error('请选择评论');
        }

        $model = new \app\common\model\TemplateReview();
        $model->whereIn('id', $ids)->update([
            'status' => $status,
            'reply' => $reply,
            'reply_time' => $reply ? time() : 0,
        ]);

        $this->recordLog('批量审核评论', "ID: " . implode(',', $ids) . ", 状态: {$status}");
        return $this->success('操作成功');
    }

    /**
     * 评论批量删除
     */
    public function reviewBatchDelete()
    {
        $ids = $this->request->post('ids/a', []);
        if (empty($ids)) {
            return $this->error('请选择评论');
        }

        \app\common\model\TemplateReview::whereIn('id', $ids)->delete();
        $this->recordLog('批量删除评论', "ID: " . implode(',', $ids));
        return $this->success('删除成功');
    }

    // ==================== G-3: 分类管理 ====================

    /**
     * 分类列表
     */
    public function categoryIndex()
    {
        $service = new TemplateStoreOpsService();
        $params = $this->request->get();
        $data = $service->getCategoryList($params);

        $this->assign([
            'list' => $data['list'],
            'params' => $params,
            'menuActive' => 'template_store_category',
        ]);

        return $this->view('/template_store/category_list');
    }

    /**
     * 分类添加/编辑
     */
    public function categoryEdit(int $id = 0)
    {
        $service = new TemplateStoreOpsService();

        if ($this->request->isGet()) {
            $info = $id > 0 ? TemplateStoreCategory::find($id) : null;
            $this->assign([
                'info' => $info,
                'menuActive' => 'template_store_category',
            ]);
            return $this->view('/template_store/category_edit');
        }

        $data = $this->request->post();
        $result = $service->saveCategory($data, $id);

        if ($result['success']) {
            $this->recordLog($id > 0 ? '编辑商店分类' : '添加商店分类', $data['name'] ?? '', $data);
            return $this->success($result['message'], ['redirect' => '/admin/template_store_ops/categoryIndex']);
        }
        return $this->error($result['message']);
    }

    /**
     * 分类删除
     */
    public function categoryDelete(int $id)
    {
        $service = new TemplateStoreOpsService();
        $result = $service->deleteCategory($id);

        if ($result['success']) {
            $this->recordLog('删除商店分类', "ID: {$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 分类排序
     */
    public function categorySort()
    {
        $ids = $this->request->post('ids/a', []);
        if (empty($ids)) {
            return $this->error('参数错误');
        }

        $service = new TemplateStoreOpsService();
        $result = $service->sortCategories($ids);
        return $this->success($result['message']);
    }

    /**
     * 切换分类可见性
     */
    public function categoryToggleVisible(int $id)
    {
        $service = new TemplateStoreOpsService();
        $result = $service->toggleCategoryVisible($id);

        if ($result['success']) {
            $this->recordLog('切换分类可见性', "ID: {$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 切换分类启用状态
     */
    public function categoryToggleEnabled(int $id)
    {
        $service = new TemplateStoreOpsService();
        $result = $service->toggleCategoryEnabled($id);

        if ($result['success']) {
            $this->recordLog('切换分类启用状态', "ID: {$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }
}
