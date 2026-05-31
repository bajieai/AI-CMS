<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\service\ai\AiProviderFactory;

/**
 * AI写作风格服务 - V2.9.12
 *
 * 管理6种写作风格定义，提供风格化文章生成接口
 */
class AiWritingStyleService
{
    /**
     * 6种写作风格System Prompt定义
     */
    protected static array $styles = [
        'formal' => [
            'name'  => '正式',
            'desc'  => '新闻/公告严谨风格',
            'prompt' => '你是一位正式的学术/商业写作专家。请使用正式、严谨的语言风格，避免口语化表达，注重逻辑性和专业性。语言规范，用词准确，句式完整，客观中性。',
        ],
        'relaxed' => [
            'name'  => '轻松',
            'desc'  => '轻松活泼博客风格',
            'prompt' => '你是一位轻松有趣的博客作者。请使用通俗易懂、生动活泼的语言，口语化表达，短句为主，适当使用语气词，有亲和力。让读者感到轻松愉快。',
        ],
        'professional' => [
            'name'  => '专业',
            'desc'  => '技术/产品专业风格',
            'prompt' => '你是一位技术/专业领域的资深作者。术语准确，逻辑严密，数据支撑，论证充分。注重内容的专业性和深度，适合产品介绍、技术文档等。',
        ],
        'news' => [
            'name'  => '资讯',
            'desc'  => '新闻资讯报道风格',
            'prompt' => '你是一位资深新闻记者。请使用倒金字塔结构，导语概括核心信息，正文按重要性递减排列。客观报道，引用事实和数据，避免主观评论。',
        ],
        'marketing' => [
            'name'  => '营销',
            'desc'  => '营销文案推广风格',
            'prompt' => '你是一位资深的营销文案策划师。请使用吸引眼球、富有感染力的语言，突出产品/服务的核心价值，引导读者行动。善用痛点+解决方案+行动号召的结构。',
        ],
        'academic' => [
            'name'  => '学术',
            'desc'  => '学术论文研究风格',
            'prompt' => '你是一位学术研究者。请使用严谨的学术语言，结构清晰（摘要/引言/方法/结果/结论），引用规范，数据详实，论证充分，适合研究报告和论文写作。',
        ],
    ];

    /**
     * 获取所有风格列表
     */
    public static function getStyles(): array
    {
        $result = [];
        foreach (self::$styles as $key => $style) {
            $result[] = array_merge(['key' => $key], $style);
        }
        return $result;
    }

    /**
     * 获取单个风格
     */
    public static function getStyle(string $key): ?array
    {
        return self::$styles[$key] ?? null;
    }

    /**
     * 获取风格System Prompt
     */
    public static function getStylePrompt(string $key): string
    {
        return self::$styles[$key]['prompt'] ?? self::$styles['formal']['prompt'];
    }

    /**
     * 使用指定风格生成文章
     */
    public static function generateWithStyle(string $topic, string $style = 'formal', array $options = []): array
    {
        $systemPrompt = self::getStylePrompt($style);
        $maxTokens = (int) ($options['max_tokens'] ?? 2000);
        $keywords = $options['keywords'] ?? [];

        $provider = AiProviderFactory::getDefault();
        $prompt = "请撰写一篇关于「{$topic}」的文章。\n\n要求：\n- 标题吸引人\n- 结构完整（开头、正文、结尾）\n- 字数800-1500字";

        if (!empty($keywords)) {
            $prompt .= "\n- 包含关键词：" . implode('、', (array) $keywords);
        }

        $content = $provider->write($prompt, [
            'system_prompt' => $systemPrompt,
            'max_tokens'    => $maxTokens,
        ]);

        return self::parseArticle($content, $topic);
    }

    /**
     * 解析AI生成的文章
     */
    protected static function parseArticle(string $content, string $defaultTitle = ''): array
    {
        $title = $defaultTitle;
        if (preg_match('/^#\s+(.+)/m', $content, $matches)) {
            $title = trim($matches[1]);
            $content = preg_replace('/^#\s+.+\n?/m', '', $content);
        } elseif (preg_match('/^(.+)\n{2,}/', $content, $matches)) {
            $firstLine = trim($matches[1]);
            if (mb_strlen($firstLine) <= 100 && !str_contains($firstLine, '。')) {
                $title = $firstLine;
                $content = preg_replace('/^.+\n{2,}/', '', $content);
            }
        }

        return ['title' => $title ?: '未命名文章', 'content' => trim($content)];
    }
}
