<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\Cate;
use app\common\model\Tag;
use app\common\service\CacheService;
use app\common\service\ContentService;
use think\facade\Config as ThinkConfig;

/**
 * 内容管理控制器
 */
class ContentController extends AdminBaseController
{
    /**
     * 内容列表
     */
    public function index()
    {
        $params = $this->request->param();
        $service = new ContentService();
        $list = $service->getList($params);
        
        // 获取分类树和标签列表用于筛选
        $cates = Cate::where('status', 1)->select();
        $tags = Tag::select();

        $this->assign([
            'list' => $list,
            'cates' => $cates,
            'tags' => $tags,
            'params' => $params,
        ]);

        return $this->view('/content_list');
    }

    /**
     * 添加内容
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $type = (int) $this->request->get('type', 1);
            $cates = Cate::where('status', 1)->where('type', $type)->select();
            $tags = Tag::select();
            $extFields = ThinkConfig::get('info_type_fields.' . $type, []);

            $this->assign([
                'cates' => $cates,
                'tags' => $tags,
                'info' => null,
                'selected_tags' => [],
                'ext_fields' => $extFields,
                'ext_data' => [],
            ]);
            return $this->view('/content_edit');
        }

        $data = $this->request->post();
        $service = new ContentService();
        $result = $service->create($data);

        if ($result) {
            $this->recordLog('添加内容', $data['title'] ?? '', $data);
            return $this->success('添加成功', ['redirect' => '/admin/content/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑内容
     */
    public function edit(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        if ($this->request->isGet()) {
            $cates = Cate::where('status', 1)->where('type', $info->type)->select();
            $tags = Tag::select();
            $selectedTags = $info->tags()->column('tag_id');
            $extFields = ThinkConfig::get('info_type_fields.' . $info->type, []);
            $extData = [];
            if ($info->ext) {
                $extData = $info->ext->data ?? [];
            }

            $this->assign([
                'info' => $info,
                'cates' => $cates,
                'tags' => $tags,
                'selected_tags' => $selectedTags,
                'ext_fields' => $extFields,
                'ext_data' => $extData,
            ]);
            return $this->view('/content_edit');
        }

        $data = $this->request->post();
        $service = new ContentService();
        $result = $service->update($id, $data);

        if ($result) {
            $this->recordLog('编辑内容', $info->title ?? '', $data);
            return $this->success('更新成功');
        }
        return $this->error('更新失败');
    }

    /**
     * 获取扩展字段配置（AJAX）
     */
    public function getExtFields()
    {
        $type = (int) $this->request->get('type', 1);
        $extFields = ThinkConfig::get('info_type_fields.' . $type, []);
        return $this->success('获取成功', ['fields' => $extFields]);
    }

    /**
     * 获取分类列表（按类型过滤，AJAX）
     */
    public function getCates()
    {
        $type = (int) $this->request->get('type', 1);
        $cates = Cate::where('status', 1)->where('type', $type)->column('name', 'id');
        return $this->success('获取成功', ['cates' => $cates]);
    }

    /**
     * 删除内容
     */
    public function delete(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        // 软删除：将status设为-1
        $info->status = -1;
        if ($info->save()) {
            $this->recordLog('删除内容', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 发布内容
     */
    public function publish(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        $info->status = 2;
        if ($info->save()) {
            $this->recordLog('发布内容', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            return $this->success('发布成功');
        }
        return $this->error('发布失败');
    }
}
