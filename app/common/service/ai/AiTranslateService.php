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

use app\common\model\ArticleLang;
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
    protected const CACHE_PREFIX = 'translate_article_';

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
     * @param int    $aid        文章ID
     * @param string $targetLang 目标语言：en/ja/ko
     * @param array  $options    可选参数
     *                           - force: bool 强制重新翻译（忽略缓存和已有版本）
     *                           - context: string 翻译上下文提示
     * @return array ['success'=>bool, 'data'=>ArticleLang|null, 'message'=>string]
     */
    public function translateContent(int $aid, string $targetLang, array $options = []): array
    {
        $force = $options['force'] ?? false;

        // 检查语言是否注册
        if (!TranslateProviderRouter::isLanguageRegistered($targetLang)) {
            return ['success' => false, 'data' => null, 'message' => "不支持的目标语言: {$targetLang}"];
        }

        // 检查是否已有完成版本（非强制模式）
        if (!$force) {
            $existing = ArticleLang::getByAidAndLang($aid, $targetLang);
            if ($existing && $existing->translate_status === ArticleLang::STATUS_COMPLETED) {
                return [
                    'success' => true,
                    'data'    => $existing,
                    'message' => '该语言版本已存在，如需重新翻译请使用force=true',
                ];
            }
        }

        // 获取源文章内容
        $article = \app\common\model\Content::find($aid);
        if (!$article) {
            return ['success' => false, 'data' => null, 'message' => '文章不存在'];
        }

        $startTime = time();

        try {
            // 创建或更新翻译记录为"翻译中"
            $transRecord = $this->ensureTranslationRecord($aid, $targetLang);
            $transRecord->save([
                'translate_status' => ArticleLang::STATUS_PROCESSING,
                'update_time'      => time(),
            ]);

            // 翻译各字段
            $titleResult       = $this->doTranslate((string) $article->title, $targetLang, $options);
            $contentResult     = $this->doTranslate((string) $article->content, $targetLang, array_merge($options, ['preserveHtml' => true]));
            $descResult        = $this->doTranslate((string) $article->description, $targetLang, $options);
            $keywordsResult    = $this->doTranslate((string) $article->keywords, $targetLang, $options);
            $seoTitleResult    = $this->doTranslate((string) $article->seo_title, $targetLang, $options);
            $seoDescResult     = $this->doTranslate((string) $article->seo_desc, $targetLang, $options);

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
                    'translate_status' => ArticleLang::STATUS_FAILED,
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
                'translate_status' => ArticleLang::STATUS_COMPLETED,
                'translate_provider'=> $titleResult['provider'] ?? 'deepseek',
                'translate_time'   => $translateTime,
                'update_time'      => time(),
            ]);

            // 写入缓存
            $this->setCache($aid, $targetLang, $transRecord);

            return [
                'success' => true,
                'data'    => $transRecord,
                'message' => '翻译完成',
            ];
        } catch (\Throwable $e) {
            Log::error("[AiTranslateService] translateContent failed: aid={$aid}, lang={$targetLang}, error=" . $e->getMessage());

            // 更新失败状态
            $transRecord = ArticleLang::getByAidAndLang($aid, $targetLang);
            if ($transRecord) {
                $transRecord->save([
                    'translate_status' => ArticleLang::STATUS_FAILED,
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
     * @param array  $aids       文章ID数组
     * @param string $targetLang 目标语言
     * @return array ['success'=>bool, 'task_ids'=>array, 'message'=>string]
     */
    public function batchTranslate(array $aids, string $targetLang): array
    {
        if (!TranslateProviderRouter::isLanguageRegistered($targetLang)) {
            return ['success' => false, 'task_ids' => [], 'message' => "不支持的目标语言: {$targetLang}"];
        }

        $queueService = new AiTaskQueueService();
        $taskIds = [];

        foreach ($aids as $aid) {
            $taskId = $queueService->enqueue('content_translate', [
                'biz_id'  => (int) $aid,
                'biz_key' => "translate:{$aid}:{$targetLang}",
                'payload' => [
                    'aid'         => (int) $aid,
                    'target_lang' => $targetLang,
                ],
                'priority' => 0,
            ]);
            $taskIds[] = $taskId;
        }

        return [
            'success'  => true,
            'task_ids' => $taskIds,
            'message'  => '已提交 ' . count($taskIds) . ' 篇文章的翻译任务',
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
     * 获取文章的翻译版本（优先读缓存）
     */
    public function getTranslation(int $aid, string $lang): ?ArticleLang
    {
        // 先查缓存
        $cached = $this->getCache($aid, $lang);
        if ($cached !== null) {
            return $cached;
        }

        // 再查数据库
        $record = ArticleLang::getByAidAndLang($aid, $lang);
        if ($record && $record->translate_status === ArticleLang::STATUS_COMPLETED) {
            $this->setCache($aid, $lang, $record);
        }

        return $record;
    }

    /**
     * 删除翻译版本
     */
    public function deleteTranslation(int $aid, string $lang): bool
    {
        $record = ArticleLang::getByAidAndLang($aid, $lang);
        if (!$record) {
            return false;
        }

        $result = $record->delete();
        if ($result) {
            $this->clearCache($aid, $lang);
        }
        return (bool) $result;
    }

    /**
     * 提交单个翻译任务到队列
     */
    public function submitTranslateTask(int $aid, string $targetLang): int
    {
        $queueService = new AiTaskQueueService();
        return $queueService->enqueue('content_translate', [
            'biz_id'  => $aid,
            'biz_key' => "translate:{$aid}:{$targetLang}",
            'payload' => [
                'aid'         => $aid,
                'target_lang' => $targetLang,
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
     * @param int   $aid     文章ID
     * @param array $payload 任务参数
     */
    public function consumerProcess(int $aid, array $payload): array
    {
        $targetLang = $payload['target_lang'] ?? 'en';
        return $this->translateContent($aid, $targetLang);
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
    protected function cacheKey(int $aid, string $lang): string
    {
        return self::CACHE_PREFIX . $aid . '_' . $lang;
    }

    /**
     * 读取缓存
     */
    protected function getCache(int $aid, string $lang): ?ArticleLang
    {
        $key = $this->cacheKey($aid, $lang);
        $data = Cache::get($key);
        if ($data === null) {
            return null;
        }
        // 从数组重建Model实例
        return new ArticleLang($data);
    }

    /**
     * 写入缓存
     */
    protected function setCache(int $aid, string $lang, ArticleLang $record): void
    {
        $key = $this->cacheKey($aid, $lang);
        Cache::set($key, $record->toArray(), $this->cacheTtl);
    }

    /**
     * 清除缓存
     */
    protected function clearCache(int $aid, string $lang): void
    {
        $key = $this->cacheKey($aid, $lang);
        Cache::delete($key);
    }

    // ============================================================
    //  辅助方法
    // ============================================================

    /**
     * 确保存在翻译记录（不存在则创建）
     */
    protected function ensureTranslationRecord(int $aid, string $lang): ArticleLang
    {
        $record = ArticleLang::getByAidAndLang($aid, $lang);
        if ($record) {
            return $record;
        }

        return ArticleLang::create([
            'aid'              => $aid,
            'lang'             => $lang,
            'title'            => '',
            'content'          => '',
            'description'      => '',
            'seo_title'        => '',
            'seo_desc'         => '',
            'keywords'         => '',
            'image_alt'        => '',
            'error_msg'        => '',
            'translate_status' => ArticleLang::STATUS_PENDING,
            'translate_provider'=> '',
            'translate_time'   => 0,
            'create_time'      => time(),
            'update_time'      => time(),
        ]);
    }
}
