<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use app\common\service\ai\AiProviderFactory;
use think\facade\Log;

/**
 * AI SEO优化服务 - V2.9.12
 *
 * 提供标题/描述/关键词的单条优化和批量优化能力
 */
class AiSeoOptimizerService
{
    /**
     * 优化标题
     */
    public function optimizeTitle(string $content, string $currentTitle = ''): string
    {
        $prompt = "请根据以下文章内容，生成一个SEO友好的标题（不超过30字）：\n\n{$content}\n\n";
        if ($currentTitle) {
            $prompt .= "当前标题：{$currentTitle}\n要求：保留核心关键词，提升吸引力和搜索匹配度。";
        }
        return $this->callAi($prompt, 100);
    }

    /**
     * 优化描述
     */
    public function optimizeDescription(string $content, string $currentDesc = ''): string
    {
        $prompt = "请为以下文章生成一段SEO描述（80-160字），概括核心内容并包含关键词：\n\n{$content}\n\n";
        if ($currentDesc) {
            $prompt .= "当前描述：{$currentDesc}\n要求：优化为更吸引点击的搜索摘要。";
        }
        return $this->callAi($prompt, 300);
    }

    /**
     * 优化关键词
     */
    public function optimizeKeywords(string $content, string $currentKeywords = ''): string
    {
        $prompt = "请从以下文章中提取5-8个核心SEO关键词，用逗号分隔：\n\n{$content}\n\n";
        if ($currentKeywords) {
            $prompt .= "当前关键词：{$currentKeywords}\n要求：补充遗漏的重要关键词，去除无效词。";
        }
        return $this->callAi($prompt, 150);
    }

    /**
     * 单条内容全量SEO优化
     */
    public function optimizeContent(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $text = strip_tags($content->content ?? '');
        if (mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 2000);
        }

        try {
            $result = [
                'seo_title'       => $this->optimizeTitle($text, $content->seo_title ?? ''),
                'seo_description' => $this->optimizeDescription($text, $content->seo_description ?? ''),
                'seo_keywords'    => $this->optimizeKeywords($text, $content->seo_keywords ?? ''),
            ];

            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            Log::error("[AiSeoOptimizer] 优化失败 contentId={$contentId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'AI优化失败: ' . $e->getMessage()];
        }
    }

    /**
     * 批量优化
     */
    public function batchOptimize(array $contentIds): array
    {
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($contentIds as $id) {
            $result = $this->optimizeContent((int) $id);
            if ($result['success']) {
                // 自动保存优化结果到内容
                $content = Content::find($id);
                if ($content) {
                    $content->seo_title = $result['data']['seo_title'];
                    $content->seo_description = $result['data']['seo_description'];
                    $content->seo_keywords = $result['data']['seo_keywords'];
                    $content->save();
                }
                $success++;
            } else {
                $failed++;
            }
            $results[$id] = $result;
        }

        return [
            'success' => true,
            'summary' => ['total' => count($contentIds), 'success' => $success, 'failed' => $failed],
            'details' => $results,
        ];
    }

    /**
     * 生成对比数据（优化前后）
     */
    public function getOptimizeDiff(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $optimized = $this->optimizeContent($contentId);
        if (!$optimized['success']) {
            return $optimized;
        }

        return [
            'success' => true,
            'data' => [
                'before' => [
                    'seo_title'       => $content->seo_title ?? '',
                    'seo_description' => $content->seo_description ?? '',
                    'seo_keywords'    => $content->seo_keywords ?? '',
                ],
                'after' => $optimized['data'],
            ],
        ];
    }

    /**
     * 调用AI
     */
    protected function callAi(string $prompt, int $maxTokens = 300): string
    {
        $provider = AiProviderFactory::getDefault();
        $result = $provider->write($prompt, [
            'system_prompt' => '你是一位资深SEO专家，擅长优化网页标题、描述和关键词，提升搜索引擎排名。',
            'max_tokens'    => $maxTokens,
        ]);
        return trim($result);
    }
}
