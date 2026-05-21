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
use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentTag;
use app\common\model\ContentVersion;
use app\common\model\Cate;
use app\common\model\Tag;
use app\common\model\Config as ConfigModel;
use app\common\service\CacheService;
use app\common\service\ContentService;
use think\facade\Config as ThinkConfig;

/**
 * 内容管理控制器
 */
class ContentController extends AdminBaseController
{
    /**
     * V2.9.1 M18: 批量内容操作
     */
    public function batchAction()
    {
        $action = $this->request->post('action', '');
        $ids = $this->request->post('ids', []);
        $cateId = $this->request->post('cate_id', 0);

        $extra = [];
        if ($action === 'move') {
            $extra['cate_id'] = (int) $cateId;
        }

        $result = ContentService::batchOperate($action, $ids, $extra);

        if ($this->request->isAjax()) {
            return json($result);
        }

        if ($result['success']) {
            $this->success($result['msg']);
        } else {
            $this->error($result['msg']);
        }
    }

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

            // V2.9.9-R4: 注入AI配图默认配置
            $aiImageDefaultSize = ConfigModel::getValue('ai_image_default_size', '1024x1024');
            $aiImageDefaultStyle = ConfigModel::getValue('ai_image_default_style', 'realistic');
            $aiImageCandidateCount = (int) ConfigModel::getValue('ai_image_candidate_count', '4');

            // V2.9.9: 注入AI模板列表
            $aiTemplates = \app\common\model\AiTemplate::where('status', 1)->order('sort desc, id desc')->column('name', 'id');

            // V2.9.9: AI-GEO评分（添加模式无内容，设为null）
            $geoScore = null;

            // V2.9.9: 会员等级列表
            $memberLevels = \app\common\model\MemberLevel::order('sort', 'asc')->column('name', 'id');

            $this->assign([
                'cates' => $cates,
                'tags' => $tags,
                'info' => null,
                'selected_tags' => [],
                'ext_fields' => $extFields,
                'ext_data' => [],
                'ai_image_default_size' => $aiImageDefaultSize,
                'ai_image_default_style' => $aiImageDefaultStyle,
                'ai_image_candidate_count' => $aiImageCandidateCount,
                'ai_templates' => $aiTemplates,
                'geo_score' => $geoScore,
                'member_levels' => $memberLevels,
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

            // V2.9.9-R4: 注入AI配图默认配置
            $aiImageDefaultSize = ConfigModel::getValue('ai_image_default_size', '1024x1024');
            $aiImageDefaultStyle = ConfigModel::getValue('ai_image_default_style', 'realistic');
            $aiImageCandidateCount = (int) ConfigModel::getValue('ai_image_candidate_count', '4');

            // V2.9.9: 注入AI模板列表
            $aiTemplates = \app\common\model\AiTemplate::where('status', 1)->order('sort desc, id desc')->column('name', 'id');

            // V2.9.9: AI-GEO评分
            $geoScore = null;
            if ($info && !empty($info->content)) {
                try {
                    $geoScore = self::formatGeoScore(\app\common\service\AiGeoService::score($info));
                } catch (\Throwable) {
                    // 降级：不显示评分
                }
            }

            // V2.9.9: 会员等级列表
            $memberLevels = \app\common\model\MemberLevel::order('sort', 'asc')->column('name', 'id');

            $this->assign([
                'info' => $info,
                'cates' => $cates,
                'tags' => $tags,
                'selected_tags' => $selectedTags,
                'ext_fields' => $extFields,
                'ext_data' => $extData,
                'ai_image_default_size' => $aiImageDefaultSize,
                'ai_image_default_style' => $aiImageDefaultStyle,
                'ai_image_candidate_count' => $aiImageCandidateCount,
                'ai_templates' => $aiTemplates,
                'geo_score' => $geoScore,
                'member_levels' => $memberLevels,
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
     * V2.9.9: AI-GEO评分AJAX
     */
    public function geoScore(int $id)
    {
        $info = Content::find($id);
        if (!$info) {
            return $this->error('内容不存在');
        }
        try {
            $score = self::formatGeoScore(\app\common\service\AiGeoService::score($info));
            return $this->success('评分完成', $score);
        } catch (\Throwable $e) {
            return $this->error('评分失败: ' . $e->getMessage());
        }
    }

    /**
     * V2.9.9: 格式化GEO评分为前端结构
     */
    protected static function formatGeoScore(array $raw): array
    {
        $dims = $raw['dimensions'] ?? [];
        $suggestions = [];
        foreach ($dims as $dim) {
            if (($dim['score'] ?? 0) < 20) {
                $suggestions[] = $dim['suggestion'] ?? '';
            }
        }
        return [
            'total'       => $raw['total'] ?? 0,
            'dimensions'  => [
                'structure' => $dims[0]['score'] ?? 0,
                'citations' => $dims[1]['score'] ?? 0,
                'authority' => $dims[2]['score'] ?? 0,
                'entities'  => $dims[3]['score'] ?? 0,
            ],
            'suggestions' => array_values(array_filter($suggestions)),
        ];
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
     * V2.7: 获取章节列表（AJAX）
     */
    public function getChapters(int $parentId)
    {
        $list = Content::where('parent_id', $parentId)
            ->where('is_chapter', 1)
            ->where('status', '>=', 0)
            ->order('chapter_sort', 'asc')
            ->select();
        return $this->success('获取成功', ['list' => $list]);
    }

    /**
     * V2.7: 保存章节（新增/编辑）
     */
    public function saveChapter()
    {
        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);
        $parentId = (int) ($data['parent_id'] ?? 0);

        if ($parentId <= 0) {
            return $this->error('parent_id参数错误');
        }

        $saveData = [
            'parent_id'       => $parentId,
            'is_chapter'      => 1,
            'title'           => $data['title'] ?? '',
            'chapter_title'   => $data['chapter_title'] ?? '',
            'content'         => $data['content'] ?? '',
            'chapter_sort'    => (int) ($data['chapter_sort'] ?? 0),
            'is_free_chapter' => (int) ($data['is_free_chapter'] ?? 0),
            'chapter_price'   => (float) ($data['chapter_price'] ?? 0),
            'status'          => (int) ($data['status'] ?? 2),
            'type'            => 6, // 单页类型
        ];

        if (empty($saveData['title'])) {
            return $this->error('章节标题不能为空');
        }

        try {
            if ($id > 0) {
                $chapter = Content::find($id);
                if (!$chapter || $chapter->parent_id != $parentId) {
                    return $this->error('章节不存在');
                }
                $chapter->save($saveData);
            } else {
                $chapter = Content::create($saveData);
            }
            return $this->success('保存成功', ['id' => $chapter->id]);
        } catch (\Exception $e) {
            return $this->error('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * V2.7: 删除章节
     */
    public function deleteChapter(int $id)
    {
        $chapter = Content::find($id);
        if (!$chapter || empty($chapter->is_chapter)) {
            return $this->error('章节不存在');
        }
        $chapter->status = -1;
        $chapter->save();
        return $this->success('删除成功');
    }

    /**
     * V2.7: 批量更新章节排序
     */
    public function sortChapters()
    {
        $orders = $this->request->post('orders', []);
        if (empty($orders) || !is_array($orders)) {
            return $this->error('参数错误');
        }

        try {
            foreach ($orders as $item) {
                $id = (int) ($item['id'] ?? 0);
                $sort = (int) ($item['sort'] ?? 0);
                if ($id > 0) {
                    Content::where('id', $id)->update(['chapter_sort' => $sort]);
                }
            }
            return $this->success('排序更新成功');
        } catch (\Exception $e) {
            return $this->error('排序更新失败: ' . $e->getMessage());
        }
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
            // V2.6: 从搜索索引删除
            \app\common\service\MeilisearchService::deleteDocument((int) $info->id);
            return $this->success('已移入回收站');
        }
        return $this->error('操作失败');
    }

    /**
     * 发布内容（V3.1: 自动配图增强）
     */
    public function publish(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        // V3.1: 发布自动配图 — 如果无封面图则尝试AI生成
        if (empty($info->cover) && ThinkConfig::get('ai.image.auto_on_publish', false)) {
            try {
                $aiService = new \app\common\service\AiService();
                $prompt = $aiService->buildImagePrompt($info->title ?? '', $info->content ?? '');
                $imageResult = $aiService->generateImage($prompt, ['style' => 'realistic']);
                if (!empty($imageResult['url'])) {
                    $info->cover = $imageResult['url'];
                }
            } catch (\Throwable $e) {
                \think\facade\Log::warning("发布自动配图失败: " . $e->getMessage());
            }
        }

        $info->status = 2;
        if ($info->save()) {
            $this->recordLog('发布内容', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            // V2.9.5 通知作者
            if (!empty($info->user_id)) {
                try {
                    (new \app\common\service\NotificationService())->notifyContentApprove($info->user_id, $info->id);
                } catch (\Throwable $e) {
                    \think\facade\Log::warning("发布通知发送失败: " . $e->getMessage());
                }
            }
            return $this->success('发布成功');
        }
        return $this->error('发布失败');
    }

    /**
     * V2.9.5 通过审核
     */
    public function audit(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        $info->status = 2;
        if ($info->save()) {
            $this->recordLog('通过审核', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            // V2.9.5 通知作者
            if (!empty($info->user_id)) {
                try {
                    (new \app\common\service\NotificationService())->notifyContentApprove($info->user_id, $info->id);
                } catch (\Throwable $e) {
                    \think\facade\Log::warning("审核通知发送失败: " . $e->getMessage());
                }
            }
            return $this->success('审核通过');
        }
        return $this->error('操作失败');
    }

    /**
     * V2.9.5 驳回内容（退回草稿）
     */
    public function reject(int $id)
    {
        $info = Content::find($id);
        if (empty($info)) {
            return $this->error('内容不存在');
        }

        $info->status = 0;
        if ($info->save()) {
            $this->recordLog('驳回内容', $info->title ?? '');
            $cacheService = new CacheService();
            $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
            // V2.9.5 通知作者
            if (!empty($info->user_id)) {
                try {
                    (new \app\common\service\NotificationService())->notifyContentReject($info->user_id, $info->id);
                } catch (\Throwable $e) {
                    \think\facade\Log::warning("驳回通知发送失败: " . $e->getMessage());
                }
            }
            return $this->success('已驳回，内容退回草稿状态');
        }
        return $this->error('操作失败');
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
     * 自动保存草稿（AJAX）
     */
    public function autoSave(int $id)
    {
        $data = $this->request->post();
        $service = new ContentService();
        $result = $service->autoSave($id, $data);
        if ($result['success']) {
            return $this->success($result['msg'], ['time' => $result['time'] ?? '']);
        }
        return $this->error($result['msg'] ?? '自动保存失败');
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

    /**
     * V2.8+V3.1: 批量SEO优化（AI自动填充空SEO字段，3篇并发控制+间隔2秒）
     */
    public function batchSeoOptimize()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要操作的内容');
        }

        // V3.1: 限制并发数量，避免AI API限流
        $ids = array_slice($ids, 0, 10); // 单次最多10篇
        $concurrency = 3; // 3篇并发
        $interval = 2;    // 间隔2秒

        $service = new ContentService();
        $success = 0;
        $fail = 0;
        $batch = 0;

        foreach ($ids as $id) {
            $batch++;
            $result = $service->autoFillSeo((int) $id);
            if ($result['success']) {
                $success++;
            } else {
                $fail++;
            }

            // V3.1: 每处理concurrency篇后暂停interval秒
            if ($batch % $concurrency === 0 && $batch < count($ids)) {
                sleep($interval);
            }
        }

        // V3.1 Phase 3.5L: 批量SEO优化后缓存评分（统一调用ContentService）
        foreach ($ids as $id) {
            try {
                $content = Content::find($id);
                if ($content) {
                    ContentService::cacheSeoScore($content);
                }
            } catch (\Throwable $e) {
                \think\facade\Log::warning("批量SEO评分缓存失败 content_id={$id}: " . $e->getMessage());
            }
        }

        $cacheService = new CacheService();
        $cacheService->clearByTag(ThinkConfig::get('cache.tag.content', 'i8j_content'));
        $this->recordLog('批量SEO优化', "成功:{$success}, 失败:{$fail}, 并发控制:{$concurrency}篇/批");
        return $this->success("批量SEO优化完成，成功 {$success} 条，失败 {$fail} 条");
    }
}
