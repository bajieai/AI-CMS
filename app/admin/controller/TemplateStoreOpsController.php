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
use app\common\service\template\RecommendationEngine;
use app\common\service\template\RecommendationRuleService;
use app\common\model\TemplateBanner;
use app\common\model\TemplateRecommend;
use app\common\model\TemplateInstallLog;
use app\common\model\TemplateStoreCategory;
use think\facade\Cache;

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
        // V2.9.27 修复: 确保所有模板变量有默认值
        $params['start'] = $params['start'] ?? '';
        $params['end'] = $params['end'] ?? '';
        $params['keyword'] = $params['keyword'] ?? '';
        $params['category_id'] = $params['category_id'] ?? '';
        $stats = $service->getDashboardStats($params['start'], $params['end']);

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
        // V2.9.27 修复: 确保所有模板变量有默认值,避免|default语法编译错误
        $params['status'] = $params['status'] ?? 'all';
        $params['keyword'] = $params['keyword'] ?? '';
        $params['template_id'] = $params['template_id'] ?? '';
        $params['rating'] = $params['rating'] ?? '';
        $params['start_date'] = $params['start_date'] ?? '';
        $params['end_date'] = $params['end_date'] ?? '';
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
        // V2.9.26 P-2: 使用树形结构展示
        $tree = TemplateStoreCategory::getTree(0, false);

        $this->assign([
            'tree'       => $tree,
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
            // V2.9.26 P-2: 获取父分类下拉选项（排除自身）
            $parentOptions = TemplateStoreCategory::getFlatOptions($id);
            $this->assign([
                'info'          => $info,
                'parentOptions' => $parentOptions,
                'menuActive'    => 'template_store_category',
            ]);
            return $this->view('/template_store/category_edit');
        }

        $data = $this->request->post();
        // V2.9.26 P-2: 处理多级分类和SEO字段
        $data['parent_id'] = (int)($data['parent_id'] ?? 0);
        $data['level'] = 1;
        if ($data['parent_id'] > 0) {
            $parent = TemplateStoreCategory::find($data['parent_id']);
            if ($parent) {
                $data['level'] = $parent->level + 1;
            }
        }
        $data['meta_title'] = $data['meta_title'] ?? '';
        $data['meta_description'] = $data['meta_description'] ?? '';
        $data['meta_keywords'] = $data['meta_keywords'] ?? '';

        $result = $service->saveCategory($data, $id);

        if ($result['success']) {
            // V2.9.26 P-2: 清除分类缓存
            Cache::tag(TemplateStoreCategory::CACHE_TAG)->clear();
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

    // ==================== V2.9.26 P-1: AI模板智能推荐系统 ====================

    /**
     * 推荐规则列表
     */
    public function recommendRuleIndex()
    {
        $service = new RecommendationRuleService();
        $params = $this->request->get();
        $data = $service->list(
            (int)($params['page'] ?? 1),
            (int)($params['limit'] ?? 20),
            $params
        );

        $this->assign([
            'list'       => $data['list'],
            'total'      => $data['total'],
            'page'       => $data['page'],
            'limit'      => $data['limit'],
            'params'     => $params,
            'menuActive' => 'template_store_recommend_rules',
            'ruleTypes'  => [
                'manual'       => '手动置顶',
                'ai'           => 'AI推荐',
                'category'     => '分类热门',
                'festival'     => '节日特推',
                'new_release'  => '新品首发',
            ],
        ]);

        return $this->view('/template_store/recommend_rules');
    }

    /**
     * 推荐规则编辑
     */
    public function recommendRuleEdit(int $id = 0)
    {
        $service = new RecommendationRuleService();

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['template_ids'] = json_decode($data['template_ids'] ?? '[]', true) ?: [];
            $data['conditions'] = json_decode($data['conditions'] ?? '[]', true) ?: [];

            if ($id > 0) {
                $result = $service->update($id, $data);
            } else {
                $result = $service->create($data);
            }

            if ($result['code'] === 0) {
                $this->recordLog($id > 0 ? '编辑推荐规则' : '新增推荐规则', "ID: {$id}");
                return $this->success($result['msg'], '/admin/template_store_ops/recommendRuleIndex');
            }
            return $this->error($result['msg']);
        }

        $rule = $id > 0 ? \app\common\model\TemplateRecommendRule::find($id) : null;

        $this->assign([
            'rule'       => $rule,
            'id'         => $id,
            'menuActive' => 'template_store_recommend_rules',
            'categories' => TemplateStoreCategory::where('status', 1)->order('sort', 'asc')->select(),
            'ruleTypes'  => [
                'manual'       => '手动置顶',
                'ai'           => 'AI推荐',
                'category'     => '分类热门',
                'festival'     => '节日特推',
                'new_release'  => '新品首发',
            ],
        ]);

        return $this->view('/template_store/recommend_rule_edit');
    }

    /**
     * 删除推荐规则
     */
    public function recommendRuleDelete(int $id)
    {
        $service = new RecommendationRuleService();
        $result = $service->delete($id);

        if ($result['code'] === 0) {
            $this->recordLog('删除推荐规则', "ID: {$id}");
            return $this->success($result['msg']);
        }
        return $this->error($result['msg']);
    }

    /**
     * 切换推荐规则状态
     */
    public function recommendRuleToggle(int $id)
    {
        $service = new RecommendationRuleService();
        $result = $service->toggleStatus($id);

        if ($result['code'] === 0) {
            $this->recordLog('切换推荐规则状态', "ID: {$id}");
            return $this->success($result['msg']);
        }
        return $this->error($result['msg']);
    }

    /**
     * 推荐效果统计
     */
    public function recommendStats()
    {
        $service = new RecommendationRuleService();
        $days = (int)$this->request->get('days', 30);
        $data = $service->getStats($days);

        $this->assign([
            'stats'      => $data,
            'days'       => $days,
            'menuActive' => 'template_store_recommend_stats',
        ]);

        return $this->view('/template_store/recommend_stats');
    }

    /**
     * 推荐预览（测试推荐引擎效果）
     */
    public function recommendPreview()
    {
        $engine = new RecommendationEngine();
        $position = $this->request->get('position', 'home');
        $categoryId = (int)$this->request->get('category_id', 0);
        $userId = (int)$this->request->get('user_id', 0);
        $limit = (int)$this->request->get('limit', 10);

        $list = $engine->getRecommendations($position, $userId, $categoryId, $limit);

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'data' => $list]);
        }

        $this->assign([
            'list'        => $list,
            'position'    => $position,
            'category_id' => $categoryId,
            'user_id'     => $userId,
            'limit'       => $limit,
            'menuActive'  => 'template_store_recommend_rules',
            'categories'  => TemplateStoreCategory::where('status', 1)->order('sort', 'asc')->select(),
        ]);

        return $this->view('/template_store/recommend_preview');
    }

    // ==================== V2.9.26 P-3: 审核流程 ====================

    public function auditPendingList()
    {
        $service = new \app\common\service\template\AuditService();
        $page = (int)$this->request->get('page', 1);
        $data = $service->getPendingList($page);
        $this->assign([
            'list' => $data['list'], 'total' => $data['total'],
            'page' => $data['page'], 'limit' => $data['limit'],
            'menuActive' => 'template_store_audit',
        ]);
        return $this->view('/template_store/audit_pending');
    }

    public function auditApprove(int $id)
    {
        $service = new \app\common\service\template\AuditService();
        $comment = $this->request->post('comment', '');
        $result = $service->approve($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin', $comment);
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    public function auditReject(int $id)
    {
        $service = new \app\common\service\template\AuditService();
        $reason = $this->request->post('reason', '');
        $reasonId = (int)$this->request->post('reason_id', 0);
        $result = $service->reject($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin', $reason, $reasonId);
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    public function auditHistory(int $id)
    {
        $service = new \app\common\service\template\AuditService();
        $history = $service->getHistory($id);
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'data' => $history]);
        }
        $this->assign(['history' => $history, 'templateId' => $id]);
        return $this->view('/template_store/audit_history');
    }

    public function rejectReasons()
    {
        $service = new \app\common\service\template\AuditService();
        $reasons = $service->getRejectReasons();
        return json(['code' => 0, 'data' => $reasons]);
    }

    // ==================== V2.9.26 P-4: 定价促销 ====================

    public function promotionIndex()
    {
        $service = new \app\common\service\template\PricingService();
        $page = (int)$this->request->get('page', 1);
        $data = $service->getPromotionList($page);
        $this->assign([
            'list' => $data['list'], 'total' => $data['total'],
            'page' => $data['page'], 'limit' => $data['limit'],
            'menuActive' => 'template_store_promotion',
        ]);
        return $this->view('/template_store/promotion_list');
    }

    public function promotionEdit(int $id = 0)
    {
        $service = new \app\common\service\template\PricingService();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['template_ids'] = json_decode($data['template_ids'] ?? '[]', true) ?: [];
            if ($id > 0) {
                $result = $service->createPromotion($data); // update logic
            } else {
                $result = $service->createPromotion($data);
            }
            return $result['success'] ? $this->success($result['message'], '/admin/template_store_ops/promotionIndex') : $this->error($result['message']);
        }
        $promo = $id > 0 ? \app\common\model\TemplatePromotion::find($id) : null;
        $this->assign(['promo' => $promo, 'id' => $id, 'menuActive' => 'template_store_promotion']);
        return $this->view('/template_store/promotion_edit');
    }

    public function promotionDelete(int $id)
    {
        \app\common\model\TemplatePromotion::destroy($id);
        Cache::tag(\app\common\model\TemplatePromotion::CACHE_TAG)->clear();
        return $this->success('删除成功');
    }

    public function couponIndex()
    {
        $service = new \app\common\service\template\PricingService();
        $page = (int)$this->request->get('page', 1);
        $data = $service->getCouponList($page);
        $this->assign([
            'list' => $data['list'], 'total' => $data['total'],
            'page' => $data['page'], 'limit' => $data['limit'],
            'menuActive' => 'template_store_coupon',
        ]);
        return $this->view('/template_store/coupon_list');
    }

    public function couponEdit(int $id = 0)
    {
        $service = new \app\common\service\template\PricingService();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['template_ids'] = json_decode($data['template_ids'] ?? '[]', true) ?: [];
            $result = $service->createCoupon($data);
            return $result['success'] ? $this->success($result['message'], '/admin/template_store_ops/couponIndex') : $this->error($result['message']);
        }
        $coupon = $id > 0 ? \app\common\model\TemplateCoupon::find($id) : null;
        $this->assign(['coupon' => $coupon, 'id' => $id, 'menuActive' => 'template_store_coupon']);
        return $this->view('/template_store/coupon_edit');
    }

    public function couponDelete(int $id)
    {
        \app\common\model\TemplateCoupon::destroy($id);
        return $this->success('删除成功');
    }

    public function priceHistory(int $id)
    {
        $service = new \app\common\service\template\PricingService();
        $history = $service->getPriceHistory($id);
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'data' => $history]);
        }
        $this->assign(['history' => $history, 'templateId' => $id]);
        return $this->view('/template_store/price_history');
    }

    // ==================== V2.9.26 P-5: 质量评分 ====================

    public function qualityIndex()
    {
        $service = new \app\common\service\template\QualityScoreService();
        $page = (int)$this->request->get('page', 1);
        $templates = \app\common\model\TemplateStore::where('status', 1)
            ->order('install_count', 'desc')
            ->page($page, 20)
            ->select();
        $scores = [];
        foreach ($templates as $tpl) {
            $scores[$tpl->id] = $service->getScore((int)$tpl->id);
        }
        $this->assign([
            'templates' => $templates, 'scores' => $scores,
            'page' => $page, 'menuActive' => 'template_store_quality',
        ]);
        return $this->view('/template_store/quality_index');
    }

    public function qualityAutoScore(int $id)
    {
        $service = new \app\common\service\template\QualityScoreService();
        $result = $service->autoCalculate($id);
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    public function qualityAddTag(int $id)
    {
        $service = new \app\common\service\template\QualityScoreService();
        $tagName = $this->request->post('tag_name', '');
        $score = (float)$this->request->post('score', 0);
        $weight = (int)$this->request->post('weight', 100);
        $result = $service->addManualTag($id, $tagName, $score, $weight, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin');
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    // ==================== V2.9.26 P-6: 版本管理 ====================

    public function versionHistoryPage(int $id)
    {
        $service = new \app\common\service\template\VersionManageService();
        $history = $service->getHistory($id);
        $this->assign(['history' => $history, 'templateId' => $id, 'menuActive' => 'template_store_version']);
        return $this->view('/template_store/version_history');
    }

    public function versionCreate(int $id)
    {
        $service = new \app\common\service\template\VersionManageService();
        $version = $this->request->post('version', '');
        $changelog = $this->request->post('changelog', '');
        $result = $service->createSnapshot($id, $version, $changelog, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin');
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    public function versionPublish(int $id)
    {
        $service = new \app\common\service\template\VersionManageService();
        $grayscale = (int)$this->request->post('grayscale_percent', 100);
        $result = $service->publishVersion($id, $grayscale);
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    public function versionRollback(int $id)
    {
        $service = new \app\common\service\template\VersionManageService();
        $result = $service->rollbackVersion($id, $this->adminInfo['id'] ?? 0, $this->adminInfo['name'] ?? 'admin');
        return $result['success'] ? $this->success($result['message']) : $this->error($result['message']);
    }

    // ==================== V2.9.26 P-7: 数据报表 ====================

    public function analyticsDashboard()
    {
        $service = new \app\common\service\template\AnalyticsReportService();
        $days = (int)$this->request->get('days', 30);
        $data = $service->getDashboard($days);
        $this->assign(['data' => $data, 'days' => $days, 'menuActive' => 'template_store_analytics']);
        return $this->view('/template_store/analytics_dashboard');
    }

    public function analyticsCategory()
    {
        $service = new \app\common\service\template\AnalyticsReportService();
        $data = $service->getCategoryReport();
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'data' => $data]);
        }
        $this->assign(['categories' => $data, 'menuActive' => 'template_store_analytics']);
        return $this->view('/template_store/analytics_category');
    }

    public function analyticsExport()
    {
        $type = $this->request->get('type', 'dashboard');
        $service = new \app\common\service\template\AnalyticsReportService();
        $csv = $service->exportCsv($type);
        return download($csv, 'analytics_' . $type . '_' . date('Ymd') . '.csv');
    }
}
