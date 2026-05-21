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

namespace app\common\service;

use app\common\model\Content;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * AI-GEO 生成式引擎优化服务 - V2.9.9
 * 为内容生成AI友好的摘要、FAQ、HowTo结构化数据
 */
class GeoService
{
    protected static string $cacheTag = 'i8j_geo';

    /**
     * 生成内容的AI友好摘要（用于答案引擎）
     *
     * @param Content $content
     * @return array {summary, key_points, faq}
     */
    public static function generate(Content $content): array
    {
        $cacheKey = 'geo_' . $content->id;
        return Cache::tag(self::$cacheTag)->remember($cacheKey, function () use ($content) {
            return self::buildGeoData($content);
        }, 86400);
    }

    /**
     * 清除内容的GEO缓存
     */
    public static function clearCache(int $contentId): void
    {
        Cache::delete('geo_' . $contentId);
    }

    /**
     * 构建GEO数据结构
     */
    protected static function buildGeoData(Content $content): array
    {
        $title = $content->title ?? '';
        $body = strip_tags($content->content ?? '');

        // 1. 提取关键要点（基于段落首句）
        $keyPoints = self::extractKeyPoints($body);

        // 2. 生成FAQ（基于常见问答模式）
        $faq = self::extractFaq($title, $body);

        // 3. 生成AI友好摘要（150字以内）
        $summary = self::generateSummary($title, $body);

        return [
            'summary'    => $summary,
            'key_points' => $keyPoints,
            'faq'        => $faq,
            'content_id' => $content->id,
            'generated_at' => time(),
        ];
    }

    /**
     * 从内容中提取关键要点
     */
    protected static function extractKeyPoints(string $text): array
    {
        $points = [];
        $lines = preg_split('/\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 匹配列表项（-、*、数字.）
            if (preg_match('/^([\-\*•]|\d+\.)\s+(.+)$/u', $line, $matches)) {
                $points[] = $matches[2];
                continue;
            }

            // 匹配加粗强调
            if (preg_match('/\*\*(.+?)\*\*/u', $line, $matches)) {
                $points[] = $matches[1];
            }
        }

        // 如果没有提取到，取前3段的首句
        if (empty($points)) {
            $sentences = preg_split('/(?<=[。！？.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            $points = array_slice(array_filter(array_map('trim', $sentences)), 0, 3);
        }

        return array_slice($points, 0, 5);
    }

    /**
     * 从内容中提取FAQ
     */
    protected static function extractFaq(string $title, string $text): array
    {
        $faq = [];

        // 模式1: 问题标记（以?或？结尾的句子）
        if (preg_match_all('/(.{10,60}[?？])/u', $text, $matches)) {
            foreach ($matches[1] as $idx => $question) {
                if ($idx >= 3) break;
                // 取问题后的第一句作为答案
                $pos = mb_strpos($text, $question);
                $after = mb_substr($text, $pos + mb_strlen($question), 200);
                $answer = mb_substr(trim($after), 0, 150);
                if (empty($answer)) {
                    $answer = '详情请参考正文内容。';
                }
                $faq[] = [
                    'question' => trim($question),
                    'answer'   => $answer,
                ];
            }
        }

        // 模式2: 如果没有提取到，基于标题生成一个通用FAQ
        if (empty($faq)) {
            $faq[] = [
                'question' => $title . '的核心要点是什么？',
                'answer'   => mb_substr($text, 0, 200) ?: '详情请参考正文内容。',
            ];
        }

        return $faq;
    }

    /**
     * 生成AI友好摘要
     */
    protected static function generateSummary(string $title, string $text): string
    {
        // 取前150字，优先从第一段获取
        $firstPara = '';
        $lines = preg_split('/\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($lines as $line) {
            $line = trim(strip_tags($line));
            if (mb_strlen($line) > 30) {
                $firstPara = $line;
                break;
            }
        }

        if (empty($firstPara)) {
            $firstPara = $text;
        }

        $summary = mb_substr($firstPara, 0, 150);
        if (mb_strlen($firstPara) > 150) {
            $summary .= '...';
        }

        return $summary;
    }

    /**
     * 渲染FAQ JSON-LD结构化数据
     */
    public static function renderFaqJsonLd(array $faq): string
    {
        if (empty($faq) || !Config::get('seo.schema_enabled', 1)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($faq as $item) {
            $schema['mainEntity'][] = [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'],
                ],
            ];
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    /**
     * 渲染HowTo JSON-LD（如果内容包含步骤）
     */
    public static function renderHowToJsonLd(string $title, array $steps): string
    {
        if (empty($steps) || !Config::get('seo.schema_enabled', 1)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'HowTo',
            'name'     => $title,
            'step'     => [],
        ];

        foreach ($steps as $idx => $step) {
            $schema['step'][] = [
                '@type' => 'HowToStep',
                'position' => $idx + 1,
                'name'   => '步骤 ' . ($idx + 1),
                'text'   => $step,
            ];
        }

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }
}
