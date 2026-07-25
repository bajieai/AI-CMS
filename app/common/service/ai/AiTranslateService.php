<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\ContentLang;
use app\common\service\ai\translate\TranslateProviderRouter;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.15: AI翻译服务
 *
 * 核心能力：
 * 1. 文章整篇翻译（含HTML正文分段处理）
 * 2. 批量翻译（通过任务队列异步执行）
 * 3. 翻译版本缓存（TTL 3600s）
 * 4. 翻译状态管理
 */
class AiTranslateService
{
    /** 缓存key前缀 */
    protected const CACHE_PREFIX = 'translate_content_';

    /** 分段翻译阈值（字符数） */
    protected int $segmentThreshold;

    /** 缓存TTL（秒） */
    protected int $cacheTtl;

    public function __construct()
    {
        $config = Config::get('ai.translate', []);
        $this->segmentThreshold = $config['segment_threshold'] ?? 1500;
        $this->cacheTtl = $config['cache_ttl'] ?? 3600;
    }

    // ============================================================
    //  对外接口
    // ============================================================

    /**
     * 翻译整篇文章
     *
     * @param int    $contentId  内容ID
     * @param string $targetLang 目标语言：en/ja/ko
     * @param array  $options    可选参数
     *                           - force: bool 强制重新翻译（忽略缓存和已有版本）
     *                           - context: string 翻译上下文提示
     * @return array ['success'=>bool, 'data'=>ContentLang|null, 'message'=>string]
     */
    public function translateContent(int $contentId, string $targetLang, array $options = []): array
    {
        $force = $options['force'] ?? false;

        // 检查语言是否注册
        if (!TranslateProviderRouter::isLanguageRegistered($targetLang)) {
            return ['success' => false, 'data' => null, 'message' => "不支持的目标语言: {$targetLang}"];
        }

        // 检查是否已有完成版本（非强制模式）
        if (!$force) {
            $existing = ContentLang::getByContentIdAndLang($contentId, $targetLang);
            if ($existing && $existing->translate_status === ContentLang::STATUS_COMPLETED) {
                return [
                    'success' => true,
                    'data'    => $existing,
                    'message' => '该语言版本已存在，如需重新翻译请使用force=true',
                ];
            }
        }

        // 获取源内容
        $content = \app\common\model\Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'data' => null, 'message' => '内容不存在'];
        }

        $startTime = time();

        try {
            // 创建或更新翻译记录为"翻译中"
            $transRecord = $this->ensureTranslationRecord($contentId, $targetLang);
            $transRecord->save([
                'translate_status' => ContentLang::STATUS_PROCESSING,
                'update_time'      => time(),
            ]);

            // 翻译各字段
            $titleResult       = $this->doTranslate((string) $content->title, $targetLang, $options);
            $contentResult     = $this->doTranslate((string) $content->content, $targetLang, array_merge($options, ['preserveHtml' => true]));
            $descResult        = $this->doTranslate((string) $content->description, $targetLang, $options);
            $keywordsResult    = $this->doTranslate((string) $content->keywords, $targetLang, $options);
            $seoTitleResult    = $this->doTranslate((string) $content->seo_title, $targetLang, $options);
            $seoDescResult     = $this->doTranslate((string) $content->seo_desc, $targetLang, $options);

            $translateTime = time() - $startTime;

            // 检查是否有字段翻译失败
            $failedFields = [];
            foreach (['title', 'content', 'description', 'keywords', 'seo_title', 'seo_desc'] as $field) {
                $resultVar = $field . 'Result';
                if (!${$resultVar}['success']) {
                    $failedFields[] = $field;
                }
            }

            if (!empty($failedFields)) {
                $transRecord->save([
                    'translate_status' => ContentLang::STATUS_FAILED,
                    'error_msg'        => '以下字段翻译失败: ' . implode(', ', $failedFields),
                    'translate_time'   => $translateTime,
                    'update_time'      => time(),
                ]);
                return [
                    'success' => false,
                    'data'    => $transRecord,
                    'message' => '部分字段翻译失败: ' . implode(', ', $failedFields),
                ];
            }

            // 保存翻译结果
            $transRecord->save([
                'title'            => $titleResult['text'],
                'content'          => $contentResult['text'],
                'description'      => $descResult['text'],
                'keywords'         => $keywordsResult['text'],
                'seo_title'        => $seoTitleResult['text'],
                'seo_desc'         => $seoDescResult['text'],
                'translate_status' => ContentLang::STATUS_COMPLETED,
                'translate_provider'=> $titleResult['provider'] ?? 'deepseek',
                'translate_time'   => $translateTime,
                'update_time'      => time(),
            ]);

            // 写入缓存
            $this->setCache($contentId, $targetLang, $transRecord);

            return [
                'success' => true,
                'data'    => $transRecord,
                'message' => '翻译完成',
            ];
        } catch (\Throwable $e) {
            Log::error("[AiTranslateService] translateContent failed: content_id={$contentId}, lang={$targetLang}, error=" . $e->getMessage());

            // 更新失败状态
            $transRecord = ContentLang::getByContentIdAndLang($contentId, $targetLang);
            if ($transRecord) {
                $transRecord->save([
                    'translate_status' => ContentLang::STATUS_FAILED,
                    'error_msg'        => $e->getMessage(),
                    'update_time'      => time(),
                ]);
            }

            return [
                'success' => false,
                'data'    => $transRecord,
                'message' => '翻译异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 批量翻译（异步入队）
     *
     * @param array  $contentIds  内容ID数组
     * @param string $targetLang 目标语言
     * @return array ['success'=>bool, 'task_ids'=>array, 'message'=>string]
     */
    public function batchTranslate(array $contentIds, string $targetLang): array
    {
        if (!TranslateProviderRouter::isLanguageRegistered($targetLang)) {
            return ['success' => false, 'task_ids' => [], 'message' => "不支持的目标语言: {$targetLang}"];
        }

        $queueService = new AiTaskQueueService();
        $taskIds = [];

        foreach ($contentIds as $contentId) {
            $taskId = $queueService->enqueue('content_translate', [
                'biz_id'  => (int) $contentId,
                'biz_key' => "translate:{$contentId}:{$targetLang}",
                'payload' => [
                    'content_id'   => (int) $contentId,
                    'target_lang'  => $targetLang,
                ],
                'priority' => 0,
            ]);
            $taskIds[] = $taskId;
        }

        return [
            'success'  => true,
            'task_ids' => $taskIds,
            'message'  => '已提交 ' . count($taskIds) . ' 篇内容的翻译任务',
        ];
    }

    /**
     * 翻译单个字段
     *
     * @param string $text       待翻译文本
     * @param string $targetLang 目标语言
     * @param array  $options    可选参数
     * @return array ['success'=>bool, 'text'=>string, 'provider'=>string, 'message'=>string]
     */
    public function translateField(string $text, string $targetLang, array $options = []): array
    {
        if (empty($text)) {
            return ['success' => true, 'text' => '', 'provider' => '', 'message' => '原文为空，跳过翻译'];
        }
        return $this->doTranslate($text, $targetLang, $options);
    }

    /**
     * 获取内容的翻译版本（优先读缓存）
     */
    public function getTranslation(int $contentId, string $lang): ?ContentLang
    {
        // 先查缓存
        $cached = $this->getCache($contentId, $lang);
        if ($cached !== null) {
            return $cached;
        }

        // 再查数据库
        $record = ContentLang::getByContentIdAndLang($contentId, $lang);
        if ($record && $record->translate_status === ContentLang::STATUS_COMPLETED) {
            $this->setCache($contentId, $lang, $record);
        }

        return $record;
    }

    /**
     * 删除翻译版本
     */
    public function deleteTranslation(int $contentId, string $lang): bool
    {
        $record = ContentLang::getByContentIdAndLang($contentId, $lang);
        if (!$record) {
            return false;
        }

        $result = $record->delete();
        if ($result) {
            $this->clearCache($contentId, $lang);
        }
        return (bool) $result;
    }

    /**
     * 提交单个翻译任务到队列
     */
    public function submitTranslateTask(int $contentId, string $targetLang): int
    {
        $queueService = new AiTaskQueueService();
        return $queueService->enqueue('content_translate', [
            'biz_id'  => $contentId,
            'biz_key' => "translate:{$contentId}:{$targetLang}",
            'payload' => [
                'content_id'   => $contentId,
                'target_lang'  => $targetLang,
            ],
            'priority' => 0,
        ]);
    }

    // ============================================================
    //  消费者接口（AiQueueConsume调用）
    // ============================================================

    /**
     * 队列消费者入口
     *
     * @param int   $contentId 内容ID
     * @param array $payload   任务参数
     */
    public function consumerProcess(int $contentId, array $payload): array
    {
        $targetLang = $payload['target_lang'] ?? 'en';
        return $this->translateContent($contentId, $targetLang);
    }

    // ============================================================
    //  核心翻译逻辑（含分段处理）
    // ============================================================

    /**
     * 执行翻译（底层）
     *
     * V2.9.15 建议1：对超过阈值的HTML正文按标签分段翻译，再拼接还原。
     *
     * @param string $text       待翻译文本
     * @param string $targetLang 目标语言
     * @param array  $options    可选参数
     * @return array ['success'=>bool, 'text'=>string, 'provider'=>string, 'message'=>string]
     */
    protected function doTranslate(string $text, string $targetLang, array $options = []): array
    {
        if (empty($text)) {
            return ['success' => true, 'text' => '', 'provider' => '', 'message' => '原文为空'];
        }

        $preserveHtml = $options['preserveHtml'] ?? false;

        // 判断是否需分段翻译
        if ($preserveHtml && mb_strlen($text) > $this->segmentThreshold) {
            return $this->translateSegmented($text, $targetLang, $options);
        }

        // 短文本直接翻译
        return TranslateProviderRouter::translate($text, $targetLang, $options);
    }

    /**
     * 分段翻译（HTML正文超过阈值时使用）
     *
     * 按</p>/</h2>/</h3>/</div>/</li>等闭合标签分割，
     * 每段控制在segment_threshold字符以内，翻译后拼接还原。
     */
    protected function translateSegmented(string $html, string $targetLang, array $options = []): array
    {
        // 按常见HTML闭合标签分段
        $segments = $this->splitHtmlByTags($html);
        $translatedSegments = [];
        $provider = '';

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (empty($segment)) {
                $translatedSegments[] = '';
                continue;
            }

            // 纯HTML标签（无文本内容）直接保留
            if ($this->isPureHtmlTag($segment)) {
                $translatedSegments[] = $segment;
                continue;
            }

            $result = TranslateProviderRouter::translate($segment, $targetLang, $options);
            if (!$result['success']) {
                return $result; // 任一segment失败则整体失败
            }

            $translatedSegments[] = $result['text'];
            $provider = $result['provider'];
        }

        return [
            'success'  => true,
            'text'     => implode("\n", $translatedSegments),
            'provider' => $provider,
            'message'  => '分段翻译完成（共' . count($segments) . '段）',
        ];
    }

    /**
     * 按HTML标签分割文本
     */
    protected function splitHtmlByTags(string $html): array
    {
        // 按常见块级标签闭合处分割
        $pattern = '/(<\/(?:p|h[1-6]|div|li|section|article|blockquote|pre)>)/i';
        $parts = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $segments = [];
        $buffer = '';

        foreach ($parts as $part) {
            $buffer .= $part;
            // 当buffer长度超过阈值或遇到闭合标签时，生成一个segment
            if (mb_strlen($buffer) >= $this->segmentThreshold || preg_match('/<\/(?:p|h[1-6]|div|li|section|article|blockquote|pre)>/i', $part)) {
                if (!empty(trim($buffer))) {
                    $segments[] = $buffer;
                }
                $buffer = '';
            }
        }

        // 处理剩余buffer
        if (!empty(trim($buffer))) {
            $segments[] = $buffer;
        }

        // 兜底：如果没有任何分段，整个文本作为一个segment
        if (empty($segments)) {
            $segments[] = $html;
        }

        return $segments;
    }

    /**
     * 判断segment是否为纯HTML标签（无文本内容）
     */
    protected function isPureHtmlTag(string $text): bool
    {
        $trimmed = trim($text);
        // 只有空白字符和HTML标签
        return preg_match('/^(\s|<[^>]+>|\s)*$/s', $trimmed) === 1;
    }

    // ============================================================
    //  缓存操作（建议2）
    // ============================================================

    /**
     * 生成缓存key
     */
    protected function cacheKey(int $contentId, string $lang): string
    {
        return self::CACHE_PREFIX . $contentId . '_' . $lang;
    }

    /**
     * 读取缓存
     */
    protected function getCache(int $contentId, string $lang): ?ContentLang
    {
        $key = $this->cacheKey($contentId, $lang);
        $data = Cache::get($key);
        if ($data === null) {
            return null;
        }
        // 从数组重建Model实例
        return new ContentLang($data);
    }

    /**
     * 写入缓存
     */
    protected function setCache(int $contentId, string $lang, ContentLang $record): void
    {
        $key = $this->cacheKey($contentId, $lang);
        Cache::set($key, $record->toArray(), $this->cacheTtl);
    }

    /**
     * 清除缓存
     */
    protected function clearCache(int $contentId, string $lang): void
    {
        $key = $this->cacheKey($contentId, $lang);
        Cache::delete($key);
    }

    // ============================================================
    //  辅助方法
    // ============================================================

    /**
     * 确保存在翻译记录（不存在则创建）
     */
    protected function ensureTranslationRecord(int $contentId, string $lang): ContentLang
    {
        $record = ContentLang::getByContentIdAndLang($contentId, $lang);
        if ($record) {
            return $record;
        }

        return ContentLang::create([
            'content_id'       => $contentId,
            'lang'             => $lang,
            'title'            => '',
            'content'          => '',
            'description'      => '',
            'seo_title'        => '',
            'seo_desc'         => '',
            'keywords'         => '',
            'image_alt'        => '',
            'error_msg'        => '',
            'translate_status' => ContentLang::STATUS_PENDING,
            'translate_provider'=> '',
            'translate_time'   => 0,
            'create_time'      => time(),
            'update_time'      => time(),
        ]);
    }
}
