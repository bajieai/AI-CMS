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
use app\common\model\Content;
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

            $this->assign(['cates' => $tree, 'info' => null, 'page_content' => '']);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        $cate = new Cate();
        if ($cate->save($data)) {
            $this->recordLog('添加分类', $data['name'] ?? '', $data);
            // V2.9.42: 单页分类自动创建content记录
            $this->syncPageContent($cate, $data);
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

            // V2.9.42: 单页分类注入关联的内容正文
            $pageContent = '';
            if ($info->type == 6 && $info->content_id > 0) {
                $pageContent = Content::where('id', $info->content_id)->value('content') ?? '';
            }

            $this->assign(['cates' => $tree, 'info' => $info, 'page_content' => $pageContent]);
            return $this->view('/cate_edit');
        }

        $data = $this->request->post();
        if ($info->save($data)) {
            $this->recordLog('编辑分类', $info->name ?? '', $data);
            // V2.9.42: 单页分类自动同步content记录
            $this->syncPageContent($info, $data);
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

    /**
     * V2.9.42: 同步单页内容到content表
     * 当分类type=6时，自动创建/更新关联的content记录
     */
    protected function syncPageContent(Cate $cate, array $data): void
    {
        if ((int)($data['type'] ?? 0) !== 6) {
            return;
        }

        $pageContent = $data['page_content'] ?? '';
        $now = time();

        if ($cate->content_id > 0) {
            // 更新已有content记录
            Content::where('id', $cate->content_id)->update([
                'title'        => $cate->name,
                'content'      => $pageContent,
                'type'         => 6,
                'cate_id'      => $cate->id,
                'status'       => 2,
                'seo_title'    => $data['seo_title'] ?? '',
                'seo_keywords' => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'update_time'  => $now,
            ]);
        } else {
            // 创建新content记录
            $content = new Content();
            $content->save([
                'title'           => $cate->name,
                'content'         => $pageContent,
                'type'            => 6,
                'cate_id'         => $cate->id,
                'status'          => 2,
                'seo_title'       => $data['seo_title'] ?? '',
                'seo_keywords'    => $data['seo_keywords'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'create_time'     => $now,
                'update_time'     => $now,
            ]);
            // 回写content_id到分类
            $cate->content_id = $content->id;
            $cate->save();
        }
    }
}
