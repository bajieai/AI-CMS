<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentTag;
use app\common\model\ContentVersion;
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
     * 删除内容（移入回收站）
     */
    public function delete(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        // 记录原始状态，用于还原
        $originalStatus = $info->status;

        // 软删除：将status设为-1
        $info->status = -1;
        if ($info->save()) {
            $this->recordLog('移入回收站', $info->title ?? '', ['original_status' => $originalStatus]);
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            return $this->success('已移入回收站');
        }
        return $this->error('操作失败');
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

    /**
     * 回收站列表
     */
    public function recycleBin()
    {
        $params = $this->request->param();
        $params['recycle'] = 1;
        $service = new ContentService();
        $list = $service->getList($params);

        $cates = Cate::where('status', 1)->select();

        $this->app->view->assign('menuActive', 'recycle');
        $this->assign([
            'list' => $list,
            'cates' => $cates,
            'params' => $params,
        ]);

        return $this->view('/recycle_list');
    }

    /**
     * 还原内容
     */
    public function restore(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        if ($info->status != -1) {
            return $this->error('该内容不在回收站中');
        }

        // 还原为草稿状态（status=0），避免直接发布未审核内容
        $info->status = 0;
        if ($info->save()) {
            $this->recordLog('还原内容', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            return $this->success('还原成功');
        }
        return $this->error('还原失败');
    }

    /**
     * 彻底删除内容
     */
    public function forceDelete(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        if ($info->status != -1) {
            return $this->error('只能彻底删除回收站中的内容');
        }

        $title = $info->title ?? '';

        // 删除扩展数据
        ContentExt::where('content_id', $id)->delete();
        // 删除标签关联
        ContentTag::where('content_id', $id)->delete();
        // 删除主记录
        $info->delete();

        $this->recordLog('彻底删除', $title);
        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        return $this->success('彻底删除成功');
    }

    /**
     * 复制内容
     */
    public function copy(int $id)
    {
        $service = new ContentService();
        $newId = $service->copy($id);

        if ($newId) {
            $this->recordLog('复制内容', '原ID:' . $id . ' => 新ID:' . $newId);
            return $this->success('复制成功', ['redirect' => '/admin/content/edit/' . $newId]);
        }
        return $this->error('复制失败');
    }

    /**
     * 批量发布
     */
    public function batchPublish()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要操作的内容');
        }

        $count = Content::whereIn('id', $ids)->where('status', '<>', 2)->update(['status' => 2, 'update_time' => time()]);
        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        $this->recordLog('批量发布', '共' . $count . '条');
        return $this->success('批量发布成功，共 ' . $count . ' 条');
    }

    /**
     * 批量移入回收站
     */
    public function batchDelete()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要操作的内容');
        }

        $count = Content::whereIn('id', $ids)->where('status', '>=', 0)->update(['status' => -1, 'update_time' => time()]);
        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        $this->recordLog('批量移入回收站', '共' . $count . '条');
        return $this->success('批量移入回收站成功，共 ' . $count . ' 条');
    }

    /**
     * 批量移动分类
     */
    public function batchMoveCate()
    {
        $ids = $this->request->post('ids', []);
        $cateId = (int) $this->request->post('cate_id', 0);
        if (empty($ids)) {
            return $this->error('请选择要操作的内容');
        }

        $count = Content::whereIn('id', $ids)->update(['cate_id' => $cateId, 'update_time' => time()]);
        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        $this->recordLog('批量移动分类', '分类ID:' . $cateId . ', 共' . $count . '条');
        return $this->success('批量移动分类成功，共 ' . $count . ' 条');
    }

    /**
     * 版本历史列表
     */
    public function versions(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        $list = ContentVersion::where('content_id', $id)->order('id', 'desc')->paginate(20);

        $this->assign([
            'info' => $info,
            'list' => $list,
        ]);
        return $this->view('/content_versions');
    }

    /**
     * 回滚到指定版本
     */
    public function rollback(int $versionId)
    {
        $version = ContentVersion::find($versionId);
        if (empty($version)) {
            return $this->error('版本不存在');
        }

        $content = Content::find($version->content_id);
        if (empty($content)) {
            return $this->error('内容不存在');
        }

        // 先保存当前状态为一个新版本
        $service = new ContentService();
        $service->update($content->id, [
            'title' => $content->title,
            'content' => $content->content,
            'excerpt' => $content->excerpt,
            'cover' => $content->cover,
            'cate_id' => $content->cate_id,
            'status' => $content->status,
        ]);

        // 回滚到指定版本
        $content->title = $version->title;
        $content->content = $version->content;
        $content->excerpt = $version->excerpt;
        $content->cover = $version->cover;
        $content->cate_id = $version->cate_id;
        $content->status = $version->status;
        $content->update_time = time();
        $content->save();

        // 恢复扩展数据
        if (!empty($version->ext_data)) {
            $extData = json_decode($version->ext_data, true);
            $ext = ContentExt::where('content_id', $content->id)->where('type', $content->type)->find();
            if ($ext) {
                $ext->data = $extData;
                $ext->save();
            } else {
                $ext = new ContentExt();
                $ext->content_id = $content->id;
                $ext->type = $content->type;
                $ext->data = $extData;
                $ext->save();
            }
        }

        // 恢复标签关联
        if (!empty($version->tag_ids)) {
            $tagIds = array_filter(explode(',', $version->tag_ids));
            ContentTag::where('content_id', $content->id)->delete();
            $data = [];
            foreach ($tagIds as $tagId) {
                $data[] = ['content_id' => $content->id, 'tag_id' => (int) $tagId];
            }
            if (!empty($data)) {
                (new ContentTag())->saveAll($data);
            }
        }

        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        $this->recordLog('版本回滚', $content->title . ' => 版本#' . $versionId);
        return $this->success('回滚成功', ['redirect' => '/admin/content/edit/' . $content->id]);
    }
}
