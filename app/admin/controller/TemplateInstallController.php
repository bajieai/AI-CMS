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
use app\common\model\TemplateStore;
use app\common\model\TemplateInstall;
use app\common\model\TemplateCategory;
use app\common\service\template\TemplateInstallService;
use app\common\service\template\TemplateSearchService;
use app\common\service\TemplateCategoryService;

/**
 * V2.9.20 B-3: 模板安装控制器（网站主角色）
 */
class TemplateInstallController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 模板市场（增强版搜索+分类筛选）
     */
    public function market()
    {
        $searchService = new TemplateSearchService();
        $params = $this->request->get();
        $data = $searchService->search($params);
        $aggregations = $searchService->getFilterAggregations();
        $hotTags = $searchService->getHotTags(12);
        $featured = $searchService->search(['status' => TemplateStore::STATUS_ONLINE, 'sort' => 'install_count', 'limit' => 6]);

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'pages' => $data['pages'],
            'aggregations' => $aggregations,
            'hot_tags' => $hotTags,
            'featured' => $featured['list'] ?? [],
            'params' => $params,
        ]);

        return $this->view('/template_store/market');
    }

    /**
     * AJAX: 搜索模板（增强版）
     */
    public function search(): \think\Response
    {
        $searchService = new TemplateSearchService();
        $params = $this->request->get();
        $data = $searchService->search($params);
        return $this->success('ok', $data);
    }

    /**
     * AJAX: 获取热门标签
     */
    public function hotTags(): \think\Response
    {
        $searchService = new TemplateSearchService();
        $tags = $searchService->getHotTags(12);
        return $this->success('ok', ['tags' => $tags]);
    }

    /**
     * 我的已安装模板
     */
    public function myTemplates()
    {
        $memberId = (int) session('user_id');
        $service = new TemplateInstallService();
        $list = $service->getInstalled($memberId);

        $this->assign([
            'list' => $list,
        ]);
        return $this->view('/template_store/my_templates');
    }

    /**
     * AJAX: 一键安装模板
     */
    public function doInstall(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateInstallService();

        try {
            $result = $service->install($id, $memberId);
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * AJAX: 卸载模板
     */
    public function doUninstall(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateInstallService();

        try {
            $result = $service->uninstall($id, $memberId);
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * AJAX: 激活/切换模板
     */
    public function doActivate(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $memberId = (int) session('user_id');
        $service = new TemplateInstallService();

        try {
            $result = $service->activate($id, $memberId);
            return $this->success($result['message'], $result);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 模板分类管理（管理员）
     */
    public function categories()
    {
        $service = new TemplateCategoryService();
        $list = $service->getAllGrouped();

        $this->assign([
            'list' => $list,
            'typeMap' => TemplateCategory::$typeMap,
        ]);
        return $this->view('/template_store/categories_v2');
    }

    /**
     * AJAX: 保存分类
     */
    public function saveCategory(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);

        $saveData = [
            'name' => $data['name'] ?? '',
            'type' => $data['type'] ?? 'model',
            'value' => $data['value'] ?? '',
            'description' => $data['description'] ?? '',
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ];

        if (empty($saveData['name']) || empty($saveData['value'])) {
            return $this->error('名称和标识不能为空');
        }

        $service = new TemplateCategoryService();

        if ($id > 0) {
            $service->updateCategory($id, $saveData);
        } else {
            $service->createCategory($saveData);
        }

        return $this->success('保存成功');
    }

    /**
     * AJAX: 删除分类
     */
    public function deleteCategory(int $id): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $service = new TemplateCategoryService();

        try {
            $service->deleteCategory($id);
            return $this->success('删除成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
