<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\utils\ReadabilityHelper;
use app\common\utils\SeoCheckHelper;
use app\common\utils\SensitiveWordHelper;
use think\facade\Config;

/**
 * V2.9.4 内容质量检测服务
 * 三维度：可读性 + SEO友好度 + 敏感词
 */
class ContentQualityService
{
    /**
     * 执行全面质量检测
     */
    public static function check(string $title, string $content, string $keywords = ''): array
    {
        // 检查是否启用
        $enabled = Config::get('content_quality_check_enabled', 1);
        if (!$enabled) {
            return ['success' => false, 'msg' => '质量检测未启用'];
        }

        // 1. 可读性评分
        $readability = ReadabilityHelper::analyze($title, $content);

        // 2. SEO友好度检测
        $seo = SeoCheckHelper::analyze($title, $content, $keywords);

        // 3. 敏感词过滤
        $sensitive = ['score' => 100, 'matched' => [], 'count' => 0, 'suggestions' => []];
        $sensitiveEnabled = Config::get('sensitive_words_check_enabled', 1);
        if ($sensitiveEnabled) {
            $sensitive = SensitiveWordHelper::analyze($content);
        }

        // 计算总评分（加权：可读性40% + SEO35% + 敏感词25%）
        $totalScore = (int) round(
            $readability['score'] * 0.4 +
            $seo['score'] * 0.35 +
            $sensitive['score'] * 0.25
        );

        // 确定等级（绿/黄/红）
        $grade = $totalScore >= 80 ? 'green' : ($totalScore >= 50 ? 'yellow' : 'red');

        // 合并所有建议
        $allSuggestions = array_merge(
            $readability['suggestions'] ?? [],
            $seo['suggestions'] ?? [],
            $sensitive['suggestions'] ?? []
        );

        return [
            'success' => true,
            'total_score' => $totalScore,
            'grade' => $grade,
            'readability' => $readability,
            'seo' => $seo,
            'sensitive' => [
                'score' => $sensitive['score'],
                'matched' => $sensitive['matched'],
                'count' => $sensitive['count'],
                'suggestions' => $sensitive['suggestions'],
            ],
            'suggestions' => $allSuggestions,
        ];
    }

    /**
     * 仅检测可读性
     */
    public static function checkReadability(string $title, string $content): array
    {
        return ReadabilityHelper::analyze($title, $content);
    }

    /**
     * 仅检测SEO
     */
    public static function checkSeo(string $title, string $content, string $keywords = ''): array
    {
        return SeoCheckHelper::analyze($title, $content, $keywords);
    }

    /**
     * 仅检测敏感词
     */
    public static function checkSensitive(string $content): array
    {
        return SensitiveWordHelper::analyze($content);
    }
}
