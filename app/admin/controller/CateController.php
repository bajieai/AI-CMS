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
use app\common\model\Cate;
use app\common\service\CacheService;
use app\common\service\CateService;
use think\facade\Config as ThinkConfig;

/**
 * 分类管理控制器
 */
class CateController extends AdminBaseController
{
    /**
     * 分类列表
     */
    public function index()
    {
        $list = Cate::order('sort', 'asc')->order('id', 'asc')->select();
        $service = new CateService();
        $tree = $service->getTree($list->toArray());

        $this->assign(['list' => $tree]);
        return $this->view('/cate_list');
    }

    /**
     * 添加分类
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $cates = Cate::where('status', 1)->select();
            $service = new CateService();
            $tree = $service->getTree($cates->toArray());

            $this->assign(['cates' => $tree, 'info' => null]);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        $cate = new Cate();
        if ($cate->save($data)) {
            $this->recordLog('添加分类', $data['name'] ?? '', $data);
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('添加成功', ['redirect' => '/admin/cate/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑分类
     */
    public function edit(int $id)
    {
        $info = Cate::find($id);
        if (empty($info)) {
            return $this->error('分类不存在');
        }

        if ($this->request->isGet()) {
            $cates = Cate::where('status', 1)->where('id', '<>', $id)->select();
            $service = new CateService();
            $tree = $service->getTree($cates->toArray());

            $this->assign(['cates' => $tree, 'info' => $info]);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        if ($info->save($data)) {
            $this->recordLog('编辑分类', $info->name ?? '', $data);
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('更新成功');
        }
        return $this->error('更新失败');
    }

    /**
     * 删除分类
     */
    public function delete(int $id)
    {
        $info = Cate::find($id);
        if (empty($info)) {
            return $this->error('分类不存在');
        }

        // 检查是否有子分类
        $childCount = Cate::where('parent_id', $id)->count();
        if ($childCount > 0) {
            return $this->error('该分类下有子分类，无法删除');
        }

        if ($info->delete()) {
            $this->recordLog('删除分类', $info->name ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.cate', 'cms_cate'));
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'cms_content'));
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }
}
