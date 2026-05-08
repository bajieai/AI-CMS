<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentTag;
use app\common\service\CacheService;
use think\facade\Cache;
use think\facade\Config;

/**
 * 内容服务
 * TagLib标签编译后委托此类进行数据查询
 */
class ContentService
{
    /**
     * 获取内容列表（后台分页）
     */
    public function getList(array $params = [], int $pageSize = 20)
    {
        // 默认只查询正常内容（status >= 0），回收站模式查询 status = -1
        if (!empty($params['recycle'])) {
            $query = Content::with('cate')->where('status', -1);
        } else {
            $query = Content::with('cate')->where('status', '>=', 0);
        }

        if (!empty($params['type'])) {
            $query->where('type', (int) $params['type']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }
        if (!empty($params['cate_id'])) {
            $query->where('cate_id', (int) $params['cate_id']);
        }
        if (!empty($params['keyword'])) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }

        return $query->order('id', 'desc')->paginate($pageSize);
    }

    /**
     * 获取信息列表（前台模板标签使用，TagLib委托调用）
     * @param string $type 信息类型标识（product/case/news/download/job/page）
     * @param int $limit 查询数量（不分页时使用）
     * @param string $order 排序规则
     * @param int $page 当前页码（>0 则启用分页）
     * @param int $pageSize 每页数量（分页时使用）
     * @return \think\Collection|\think\Paginator
     */
    public function getInfolist(string $type = '', int $limit = 10, string $order = 'id desc', int $page = 0, int $pageSize = 10)
    {
        $cacheKey = 'info_list_' . md5($type . '_' . $limit . '_' . $order . '_' . $page . '_' . $pageSize);
        $cacheTag = Config::get('cache.tag.content', 'i8j_content');

        $result = Cache::get($cacheKey);
        if ($result !== null) {
            return $result;
        }

        $typeMap = [
            'product' => 1,
            'case' => 2,
            'news' => 3,
            'download' => 4,
            'job' => 5,
            'page' => 6,
        ];

        $query = Content::where('status', 2); // 仅已发布

        if (!empty($type) && isset($typeMap[$type])) {
            $query->where('type', $typeMap[$type]);
        }

        if ($page > 0) {
            $result = $query->order($order)->paginate($pageSize, false, ['page' => $page]);
        } else {
            $result = $query->order($order)->limit($limit)->select();
        }

        Cache::tag($cacheTag)->set($cacheKey, $result, 3600);
        return $result;
    }

    /**
     * 创建内容
     */
    public function create(array $data): bool
    {
        $content = new Content();
        
        // 处理扩展字段
        $extData = $data['ext'] ?? [];
        unset($data['ext']);
        
        // 处理标签
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        // 处理SEO字段
        $data['seo_title'] = $data['seo_title'] ?? '';
        $data['seo_keywords'] = $data['seo_keywords'] ?? '';
        $data['seo_description'] = $data['seo_description'] ?? '';

        // 处理定时发布
        $data['publish_time'] = isset($data['publish_time']) && $data['publish_time'] ? strtotime($data['publish_time']) : 0;

        // 设置时间戳
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['user_id'] = session('user_id') ?: 0;

        if (!$content->save($data)) {
            return false;
        }

        // 保存扩展数据
        if (!empty($extData)) {
            $ext = new ContentExt();
            $ext->content_id = $content->id;
            $ext->type = $content->type;
            $ext->data = $extData;
            $ext->save();
        }

        // 保存标签关联
        if (!empty($tagIds)) {
            $this->syncTags($content->id, $tagIds);
        }

        // 清除内容相关缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        // V2.6: 同步到Meilisearch
        if ($content->status == 2) {
            MeilisearchService::syncDocument($content);
        }

        return true;
    }

    /**
     * 更新内容
     */
    public function update(int $id, array $data): bool
    {
        $content = Content::find($id);
        if (empty($content)) {
            return false;
        }

        // 保存版本历史（编辑前自动备份）
        $this->saveVersion($content);

        // 处理扩展字段
        $extData = $data['ext'] ?? [];
        unset($data['ext']);
        
        // 处理标签
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        // 处理SEO字段
        if (isset($data['seo_title'])) {
            $data['seo_title'] = trim($data['seo_title']);
        }
        if (isset($data['seo_keywords'])) {
            $data['seo_keywords'] = trim($data['seo_keywords']);
        }
        if (isset($data['seo_description'])) {
            $data['seo_description'] = trim($data['seo_description']);
        }

        // 处理定时发布
        if (isset($data['publish_time'])) {
            $data['publish_time'] = $data['publish_time'] ? strtotime($data['publish_time']) : 0;
        }

        $data['update_time'] = time();

        if (!$content->save($data)) {
            return false;
        }

        // 更新扩展数据
        if (!empty($extData)) {
            $ext = ContentExt::where('content_id', $id)->where('type', $content->type)->find();
            if ($ext) {
                $ext->data = $extData;
                $ext->save();
            } else {
                $ext = new ContentExt();
                $ext->content_id = $id;
                $ext->type = $content->type;
                $ext->data = $extData;
                $ext->save();
            }
        }

        // 同步标签
        if (!empty($tagIds)) {
            $this->syncTags($id, $tagIds);
        }

        // 清除内容相关缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        // V2.6: 同步到Meilisearch
        if ($content->status == 2) {
            MeilisearchService::syncDocument($content);
        } else {
            MeilisearchService::deleteDocument($content->id);
        }

        return true;
    }

    /**
     * 保存内容版本历史
     */
    protected function saveVersion(Content $content): void
    {
        try {
            $ext = ContentExt::where('content_id', $content->id)->where('type', $content->type)->find();
            $tagIds = ContentTag::where('content_id', $content->id)->column('tag_id');

            $version = new \app\common\model\ContentVersion();
            $version->content_id = $content->id;
            $version->title = $content->title;
            $version->content = $content->content;
            $version->excerpt = $content->excerpt;
            $version->cover = $content->cover;
            $version->cate_id = $content->cate_id;
            $version->status = $content->status;
            $version->ext_data = $ext ? json_encode($ext->data, JSON_UNESCAPED_UNICODE) : '';
            $version->tag_ids = implode(',', $tagIds);
            $version->user_id = session('user_id') ?: 0;
            $version->create_time = time();
            $version->save();

            // 保留策略：每个内容最多保留20个版本
            $this->pruneVersions($content->id);
        } catch (\Throwable $e) {
            // 版本保存失败不应阻断主流程
            error_log('[VERSION_SAVE_FAIL] content_id=' . $content->id . ' error=' . $e->getMessage());
        }
    }

    /**
     * 清理旧版本，保留最近20个
     */
    protected function pruneVersions(int $contentId): void
    {
        $keep = 20;
        $versions = \app\common\model\ContentVersion::where('content_id', $contentId)
            ->order('id', 'desc')
            ->column('id');

        if (count($versions) > $keep) {
            $deleteIds = array_slice($versions, $keep);
            \app\common\model\ContentVersion::whereIn('id', $deleteIds)->delete();
        }
    }

    /**
     * 复制内容
     */
    public function copy(int $id): ?int
    {
        $origin = Content::find($id);
        if (empty($origin)) {
            return null;
        }

        // 复制主记录
        $data = $origin->toArray();
        unset($data['id'], $data['create_time'], $data['update_time']);
        $data['title'] = $data['title'] . ' (副本)';
        $data['status'] = 0; // 副本默认为草稿
        $data['views'] = 0;
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['user_id'] = session('user_id') ?: 0;

        $newContent = new Content();
        if (!$newContent->save($data)) {
            return null;
        }

        $newId = $newContent->id;

        // 复制扩展数据
        $ext = ContentExt::where('content_id', $id)->where('type', $origin->type)->find();
        if ($ext) {
            $newExt = new ContentExt();
            $newExt->content_id = $newId;
            $newExt->type = $origin->type;
            $newExt->data = $ext->data;
            $newExt->save();
        }

        // 复制标签关联
        $tagIds = ContentTag::where('content_id', $id)->column('tag_id');
        if (!empty($tagIds)) {
            $this->syncTags($newId, $tagIds);
        }

        // 清除缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        return $newId;
    }

    /**
     * 同步标签关联
     */
    protected function syncTags(int $contentId, array $tagIds): void
    {
        // 删除旧关联
        ContentTag::where('content_id', $contentId)->delete();
        
        // 创建新关联
        $data = [];
        foreach ($tagIds as $tagId) {
            $data[] = ['content_id' => $contentId, 'tag_id' => (int) $tagId];
        }
        if (!empty($data)) {
            (new ContentTag())->saveAll($data);
        }
    }

    /**
     * V2.8: AI自动生成配图并更新内容封面
     */
    public function autoGenerateCover(int $contentId, string $style = 'realistic'): ?string
    {
        $content = Content::find($contentId);
        if (!$content) return null;

        try {
            $aiService = new AiService();
            $prompt = $this->buildImagePrompt($content);
            $result = $aiService->generateImage($prompt, ['style' => $style, 'count' => 1]);
            if (!empty($result['url'])) {
                $content->cover = $result['url'];
                $content->save();
                return $result['url'];
            }
        } catch (\Throwable $e) {
            \think\facade\Log::warning("AI配图失败 content_id={$contentId}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * V2.8: 构建AI配图Prompt
     */
    protected function buildImagePrompt(Content $content): string
    {
        $title = $content->title;
        $excerpt = mb_substr(strip_tags($content->excerpt ?: $content->content), 0, 200);
        return "为以下内容生成一张高质量配图，主题：{$title}，内容描述：{$excerpt}";
    }

    /**
     * V2.8: AI自动填充SEO元数据
     */
    public function autoFillSeo(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'msg' => '内容不存在'];

        try {
            $aiService = new AiService();
            $result = $aiService->seoOptimize($content->title, $content->content);

            $updated = false;
            if (empty($content->seo_title) && !empty($result['title'])) {
                $content->seo_title = $result['title'];
                $updated = true;
            }
            if (empty($content->seo_keywords) && !empty($result['keywords'])) {
                $content->seo_keywords = $result['keywords'];
                $updated = true;
            }
            if (empty($content->seo_description) && !empty($result['description'])) {
                $content->seo_description = $result['description'];
                $updated = true;
            }

            if ($updated) {
                $content->save();
            }

            return [
                'success' => true,
                'msg'     => 'SEO优化完成',
                'data'    => [
                    'seo_title'       => $content->seo_title,
                    'seo_keywords'    => $content->seo_keywords,
                    'seo_description' => $content->seo_description,
                ],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'msg' => 'SEO优化失败: ' . $e->getMessage()];
        }
    }

    /**
     * 自动保存草稿（AJAX调用）
     */
    public function autoSave(int $id, array $data): array
    {
        $content = Content::find($id);
        if (empty($content)) {
            return ['success' => false, 'msg' => '内容不存在'];
        }

        $content->title = $data['title'] ?? $content->title;
        $content->content = $data['content'] ?? $content->content;
        $content->excerpt = $data['excerpt'] ?? $content->excerpt;
        $content->update_time = time();
        $content->save();

        return ['success' => true, 'msg' => '自动保存成功', 'time' => date('H:i:s')];
    }

    /**
     * 解析AI生成的 form_data 并填充到内容表单字段（V2.9 M2新增）
     *
     * @param array $formData AI生成的form_data {field_name: value}
     * @param array $fieldsConfig 模板字段配置（含type信息）
     * @return array 可直接写入Content模型的字段键值对
     */
    public function parseFormData(array $formData, array $fieldsConfig = []): array
    {
        if (empty($formData)) {
            return [];
        }

        $result = [];

        foreach ($fieldsConfig as $field) {
            $name = $field['name'] ?? '';
            if (empty($name) || !isset($formData[$name])) {
                continue;
            }

            $type  = $field['type'] ?? 'text';
            $value = $formData[$name];
            $processed = $this->processFormFieldValue($value, $type, $field);

            if ($processed !== null) {
                // 映射到Content模型字段（扩展字段存ContentExt）
                $contentFields = ['title', 'content', 'excerpt', 'seo_title', 'seo_keywords', 'seo_description', 'source', 'author'];
                if (in_array($name, $contentFields)) {
                    $result[$name] = $processed;
                } else {
                    // 自定义字段：存到 ext_data 供前台显示
                    $result['ext_data'][$name] = $processed;
                }
            }
        }

        return $result;
    }

    /**
     * 按字段类型处理值（类型感知）
     */
    private function processFormFieldValue($value, string $type, array $field)
    {
        switch ($type) {
            case 'number':
                // 数值类型：提取数字，校验范围
                $num = (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $min = $field['min'] ?? null;
                $max = $field['max'] ?? null;
                if ($min !== null && $num < $min) return $min;
                if ($max !== null && $num > $max) return $max;
                return $num;

            case 'select':
                // 单选择：校验值是否在可选项中
                $options = $field['options'] ?? [];
                if (in_array($value, $options)) {
                    return $value;
                }
                return $options[0] ?? $value;

            case 'date':
                // 日期类型：标准化格式
                $timestamp = strtotime($value);
                return $timestamp ? date('Y-m-d', $timestamp) : $value;

            case 'text':
            default:
                return is_string($value) ? strip_tags($value) : (string) $value;
        }
    }
}
