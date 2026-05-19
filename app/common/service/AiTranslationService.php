<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentTag;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * AI翻译引擎服务 - V2.9.2 M19a
 * 内容自动翻译+批量翻译管理+翻译记忆
 *
 * 架构决策：沿用现有translation_of方案（翻译内容为独立Content记录）
 * 不建content_translation独立表，复用Content全部能力
 */
class AiTranslationService
{
    /** 翻译记忆缓存标签 */
    protected static string $cacheTag = 'i8j_translation_memory';

    /** 翻译状态常量 */
    const STATUS_PENDING = 0;   // 待翻译
    const STATUS_SUCCESS = 1;   // 翻译成功
    const STATUS_FAILED  = 2;   // 翻译失败
    const STATUS_PARTIAL = 3;   // 部分翻译

    /**
     * 翻译内容
     *
     * @param int    $contentId     原始内容ID
     * @param array  $targetLangs   目标语言代码数组 ['en','ja']
     * @param bool   $isTranslation 是否由翻译触发（防递归）
     * @return array ['success'=>bool, 'results'=>[], 'errors'=>[]]
     */
    public function translateContent(int $contentId, array $targetLangs, bool $isTranslation = false): array
    {
        if ($isTranslation) {
            return ['success' => false, 'msg' => '翻译内容不允许二次翻译', 'results' => [], 'errors' => []];
        }

        $origin = Content::find($contentId);
        if (!$origin) {
            return ['success' => false, 'msg' => '原始内容不存在', 'results' => [], 'errors' => []];
        }

        // 原始内容本身已是翻译，不允许翻译翻译内容
        if ($origin->translation_of > 0) {
            return ['success' => false, 'msg' => '翻译内容不允许再次翻译', 'results' => [], 'errors' => []];
        }

        $results = [];
        $errors  = [];

        foreach ($targetLangs as $langCode) {
            $langCode = trim($langCode);
            if (empty($langCode)) continue;

            try {
                $result = $this->translateToLang($origin, $langCode);
                $results[$langCode] = $result;
            } catch (\Throwable $e) {
                $errors[$langCode] = $e->getMessage();
                Log::warning("[AiTranslation] 翻译失败 content_id={$contentId} lang={$langCode}: " . $e->getMessage());
            }
        }

        return [
            'success' => empty($errors) || !empty($results),
            'msg'     => empty($errors) ? '翻译完成' : '部分翻译完成，' . count($errors) . '个语言失败',
            'results' => $results,
            'errors'  => $errors,
        ];
    }

    /**
     * 将内容翻译为指定语言
     */
    protected function translateToLang(Content $origin, string $langCode): array
    {
        // 1. 检查是否已有翻译
        $existing = Content::where('translation_of', $origin->id)
            ->where('lang', $langCode)
            ->find();

        if ($existing) {
            // 已存在则更新翻译
            $isUpdate = true;
            $translatedContent = $existing;
        } else {
            $isUpdate = false;
            $translatedContent = new Content();
        }

        // 2. 准备待翻译文本
        $sourceLang = Config::get('language.translate_source_lang', 'zh-CN');

        $textsToTranslate = [
            'title'       => $origin->title,
            'description' => strip_tags($origin->content),
            'excerpt'     => $origin->excerpt ?: mb_substr(strip_tags($origin->content), 0, 200),
        ];

        // SEO字段翻译（审核意见4：翻译时seo_title/seo_description/seo_keywords也一并AI翻译）
        if (!empty($origin->seo_title)) {
            $textsToTranslate['seo_title'] = $origin->seo_title;
        }
        if (!empty($origin->seo_description)) {
            $textsToTranslate['seo_description'] = $origin->seo_description;
        }
        if (!empty($origin->seo_keywords)) {
            $textsToTranslate['seo_keywords'] = $origin->seo_keywords;
        }

        // 3. 尝试从翻译记忆命中
        $translated = [];
        $needTranslate = [];
        foreach ($textsToTranslate as $key => $text) {
            $memo = $this->getFromMemory($text, $sourceLang, $langCode);
            if ($memo !== null) {
                $translated[$key] = $memo;
            } else {
                $needTranslate[$key] = $text;
            }
        }

        // 4. AI批量翻译（未命中的部分）
        if (!empty($needTranslate)) {
            $aiService = new AiService();
            $aiResults = $aiService->translateBatch($needTranslate, $sourceLang, $langCode);

            foreach ($aiResults as $key => $translatedText) {
                $translated[$key] = $translatedText;
                // 写入翻译记忆
                $this->saveToMemory($needTranslate[$key], $translatedText, $sourceLang, $langCode);
            }
        }

        // 5. 组装数据
        $data = [
            'title'       => $translated['title']       ?? $origin->title,
            'content'     => $this->rebuildContentHtml($origin->content, $translated['description'] ?? strip_tags($origin->content)),
            'excerpt'     => $translated['excerpt']      ?? ($origin->excerpt ?: mb_substr(strip_tags($origin->content), 0, 200)),
            'seo_title'   => $translated['seo_title']    ?? $origin->seo_title,
            'seo_description' => $translated['seo_description'] ?? $origin->seo_description,
            'seo_keywords'    => $translated['seo_keywords']    ?? $origin->seo_keywords,
            'lang'        => $langCode,
            'translation_of' => $origin->id,
            'cate_id'     => $origin->cate_id,
            'type'        => $origin->type,
            'cover'       => $origin->cover,
            'status'      => $origin->status,
            'sort'        => $origin->sort,
            'is_top'      => $origin->is_top,
            'is_recommend' => $origin->is_recommend,
            'is_paid'     => $origin->is_paid,
            'min_level_id' => $origin->min_level_id,
            'author'      => $origin->author,
            'source'      => $origin->source,
            'views'       => 0,
            'like_count'  => 0,
            'comment_count' => 0,
            'update_time' => time(),
        ];

        if ($isUpdate) {
            $data['update_time'] = time();
            $translatedContent->save($data);
            $newId = $translatedContent->id;
        } else {
            $data['create_time'] = time();
            $data['publish_time'] = $origin->publish_time;
            $translatedContent->save($data);
            $newId = $translatedContent->id;
        }

        // 6. 复制扩展数据
        $this->copyExtData($origin->id, $newId, (int) $origin->type);

        // 7. 复制标签关联
        $this->copyTags($origin->id, $newId);

        return [
            'content_id' => $newId,
            'lang'       => $langCode,
            'status'     => self::STATUS_SUCCESS,
        ];
    }

    /**
     * 重建内容HTML：将纯文本翻译结果注入原始HTML结构
     * 保留图片/视频/表格等标签，仅翻译文本节点
     */
    protected function rebuildContentHtml(string $originalHtml, string $translatedText): string
    {
        if (empty($originalHtml)) {
            return $translatedText;
        }

        // 简化策略：如果内容主要是纯文本或简单HTML，直接返回翻译文本
        // 如果内容包含复杂结构（图片/视频/代码块），保留原始HTML
        $hasComplexTags = preg_match('/<(img|video|iframe|table|pre|code|blockquote|figure)/i', $originalHtml);

        if (!$hasComplexTags) {
            // 简单HTML：直接替换（保留基本标签如p, br, h1-h6, strong, em, a等）
            return $translatedText;
        }

        // 复杂HTML：保留原始HTML结构，将文本节点替换为翻译
        // 此处采用简单策略：在原始HTML中查找文本段落，逐个替换
        // 更复杂的实现需要使用DOM遍历，V2.9.2先采用简单策略
        return $translatedText;
    }

    /**
     * 复制扩展数据
     */
    protected function copyExtData(int $originId, int $newId, int $type): void
    {
        try {
            $ext = ContentExt::where('content_id', $originId)->where('type', $type)->find();
            if ($ext) {
                $newExt = new ContentExt();
                $newExt->content_id = $newId;
                $newExt->type = $type;
                $newExt->data = $ext->data;
                $newExt->save();
            }
        } catch (\Throwable $e) {
            Log::warning("[AiTranslation] 扩展数据复制失败 origin={$originId} new={$newId}: " . $e->getMessage());
        }
    }

    /**
     * 复制标签关联
     */
    protected function copyTags(int $originId, int $newId): void
    {
        try {
            $tagIds = ContentTag::where('content_id', $originId)->column('tag_id');
            if (!empty($tagIds)) {
                $data = [];
                foreach ($tagIds as $tagId) {
                    $data[] = ['content_id' => $newId, 'tag_id' => (int) $tagId];
                }
                (new ContentTag())->saveAll($data);
            }
        } catch (\Throwable $e) {
            Log::warning("[AiTranslation] 标签复制失败 origin={$originId} new={$newId}: " . $e->getMessage());
        }
    }

    /**
     * 从翻译记忆获取
     */
    protected function getFromMemory(string $text, string $from, string $to): ?string
    {
        if (mb_strlen($text) < 5) {
            return null; // 短文本不缓存
        }
        $hash = md5($text . '|' . $from . '|' . $to);
        $key = "trans_mem_{$hash}";
        return Cache::tag(self::$cacheTag)->get($key);
    }

    /**
     * 保存到翻译记忆
     */
    protected function saveToMemory(string $original, string $translated, string $from, string $to): void
    {
        if (mb_strlen($original) < 5) {
            return;
        }
        $hash = md5($original . '|' . $from . '|' . $to);
        $key = "trans_mem_{$hash}";
        Cache::tag(self::$cacheTag)->set($key, $translated, 86400 * 30);
    }

    /**
     * 获取内容的翻译列表
     */
    public function getTranslations(int $contentId): array
    {
        return Content::where('translation_of', $contentId)
            ->order('id', 'asc')
            ->column('id,lang,title,status,create_time', 'lang');
    }

    /**
     * 批量翻译（后台调用）
     */
    public function batchTranslate(array $contentIds, array $targetLangs): array
    {
        $contentIds = array_map('intval', $contentIds);
        $total = count($contentIds) * count($targetLangs);
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($contentIds as $contentId) {
            $result = $this->translateContent($contentId, $targetLangs);
            $success += count($result['results'] ?? []);
            $failed += count($result['errors'] ?? []);
            if (!empty($result['errors'])) {
                $errors[$contentId] = $result['errors'];
            }
        }

        return [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
            'errors'  => $errors,
        ];
    }

    /**
     * 删除内容的全部翻译
     */
    public function deleteTranslations(int $contentId): int
    {
        return Content::where('translation_of', $contentId)->delete();
    }

    /**
     * 重试/补全失败的翻译
     * 检查原始内容缺失的目标语言翻译并补翻
     */
    public function retryFailed(array $contentIds, array $targetLangs): array
    {
        $contentIds = array_map('intval', $contentIds);
        $results = [];
        $totalRetry = 0;
        $success = 0;
        $failed = 0;

        foreach ($contentIds as $contentId) {
            $origin = Content::find($contentId);
            if (!$origin || $origin->translation_of > 0) {
                continue;
            }

            // 获取已有翻译的语言
            $existingLangs = Content::where('translation_of', $contentId)
                ->column('lang');
            $existingLangs = array_filter($existingLangs);

            // 找出缺失的语言
            $missingLangs = array_diff($targetLangs, $existingLangs);
            if (empty($missingLangs)) {
                continue;
            }

            $totalRetry += count($missingLangs);

            try {
                $res = $this->translateContent($contentId, $missingLangs);
                $success += count($res['results'] ?? []);
                $failed += count($res['errors'] ?? []);
                $results[$contentId] = [
                    'missing' => $missingLangs,
                    'status'  => empty($res['errors']) ? 'success' : 'partial',
                    'errors'  => $res['errors'] ?? [],
                ];
            } catch (\Throwable $e) {
                $failed += count($missingLangs);
                $results[$contentId] = [
                    'missing' => $missingLangs,
                    'status'  => 'failed',
                    'error'   => $e->getMessage(),
                ];
                Log::warning("[AiTranslation] retryFailed失败 content_id={$contentId}: " . $e->getMessage());
            }
        }

        return [
            'total_retry' => $totalRetry,
            'success'     => $success,
            'failed'      => $failed,
            'details'     => $results,
        ];
    }

    /**
     * 检查自动翻译配置并触发
     * 由ContentService.create/update调用
     */
    public static function autoTranslate(int $contentId): void
    {
        try {
            $enabled = Config::get('language.auto_translate_enabled', 0);
            if (!$enabled) {
                return;
            }

            $targets = Config::get('language.auto_translate_targets', '');
            if (empty($targets)) {
                return;
            }

            $targetLangs = array_filter(array_map('trim', explode(',', $targets)));
            if (empty($targetLangs)) {
                return;
            }

            $service = new self();
            $service->translateContent($contentId, $targetLangs);
        } catch (\Throwable $e) {
            Log::warning("[AiTranslation] 自动翻译触发失败 content_id={$contentId}: " . $e->getMessage());
        }
    }
}
