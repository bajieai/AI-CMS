<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use app\common\model\ContentQualityScore;
use think\facade\Cache;

/**
 * 内容质量评分引擎 — V2.9.33 AI5-1
 *
 * 5维评分：完整性/可读性/SEO/配图匹配/标签准确
 * 权重：完整性20% + 可读性20% + SEO30% + 配图15% + 标签15%
 */
class ContentQualityScoreService
{
    private const CACHE_TAG = 'content_quality';

    private const WEIGHTS = [
        'completeness'  => 0.20,
        'readability'   => 0.20,
        'seo'           => 0.30,
        'image_match'   => 0.15,
        'tag_accuracy'  => 0.15,
    ];

    /** 完整性检查的6个字段 */
    private const COMPLETENESS_FIELDS = ['title', 'content', 'summary', 'cover_image', 'seo_title', 'seo_description'];

    /**
     * 对单篇内容进行质量评分
     */
    public function score(int $contentId, string $source = 'auto'): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $completeness = $this->scoreCompleteness($content);
        $readability  = $this->scoreReadability($content);
        $seo          = $this->scoreSeo($content);
        $imageMatch   = $this->scoreImageMatch($content);
        $tagAccuracy  = $this->scoreTagAccuracy($content);

        $total = (int) round(
            $completeness * self::WEIGHTS['completeness'] +
            $readability  * self::WEIGHTS['readability'] +
            $seo          * self::WEIGHTS['seo'] +
            $imageMatch   * self::WEIGHTS['image_match'] +
            $tagAccuracy  * self::WEIGHTS['tag_accuracy']
        );

        $level = $this->scoreToLevel($total);
        $suggestions = $this->generateSuggestions([
            'completeness' => $completeness,
            'readability'  => $readability,
            'seo'          => $seo,
            'image_match'  => $imageMatch,
            'tag_accuracy' => $tagAccuracy,
        ]);

        $scores = [
            'completeness_score'  => $completeness,
            'readability_score'   => $readability,
            'seo_score'           => $seo,
            'image_match_score'   => $imageMatch,
            'tag_accuracy_score'  => $tagAccuracy,
            'total_score'         => $total,
            'suggestions'         => json_encode($suggestions, JSON_UNESCAPED_UNICODE),
            'score_source'        => $source,
        ];

        // 持久化评分记录
        $record = ContentQualityScore::where('content_id', $contentId)->find();
        if ($record) {
            $record->save($scores);
        } else {
            $scores['content_id'] = $contentId;
            ContentQualityScore::create($scores);
        }

        // 更新content表的质量评分
        $content->quality_score = $total;
        $content->quality_level = $level;
        $content->save();

        // 缓存评分结果1小时
        Cache::set('quality_score_' . $contentId, array_merge($scores, ['level' => $level, 'suggestions' => $suggestions]), 3600);

        return [
            'success' => true,
            'content_id' => $contentId,
            'scores' => [
                'completeness' => $completeness,
                'readability'  => $readability,
                'seo'          => $seo,
                'image_match'  => $imageMatch,
                'tag_accuracy' => $tagAccuracy,
                'total'        => $total,
                'level'        => $level,
            ],
            'suggestions' => $suggestions,
        ];
    }

    /**
     * 批量评分
     */
    public function batchScore(array $contentIds): array
    {
        $results = [];
        foreach ($contentIds as $id) {
            try {
                $results[$id] = $this->score((int) $id, 'batch');
            } catch (\Throwable $e) {
                $results[$id] = ['success' => false, 'message' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * 获取内容质量评分（优先读缓存）
     */
    public function getScore(int $contentId): ?array
    {
        $cached = Cache::get('quality_score_' . $contentId);
        if ($cached) return $cached;

        $record = ContentQualityScore::where('content_id', $contentId)->find();
        if (!$record) return null;

        $data = $record->toArray();
        $data['suggestions'] = json_decode($record->suggestions ?: '[]', true);
        Cache::set('quality_score_' . $contentId, $data, 3600);
        return $data;
    }

    // ===== 5维评分算法 =====

    /**
     * 完整性评分：检查6个核心字段是否齐全
     */
    private function scoreCompleteness(Content $content): int
    {
        $filled = 0;
        foreach (self::COMPLETENESS_FIELDS as $field) {
            $val = $content->$field ?? '';
            if (!empty($val)) $filled++;
        }
        return (int) round(($filled / count(self::COMPLETENESS_FIELDS)) * 100);
    }

    /**
     * 可读性评分：基于段落结构+文本统计
     */
    private function scoreReadability(Content $content): int
    {
        $text = $content->content ?? '';
        if (empty($text)) return 0;

        // 统计段落数
        $paragraphs = preg_split('/\n{2,}/', trim(strip_tags($text)));
        $paragraphCount = count(array_filter($paragraphs, fn($p) => trim($p) !== ''));

        // 统计字数
        $charCount = mb_strlen(strip_tags($text));

        // 评分规则
        $score = 50; // 基础分
        if ($paragraphCount >= 3) $score += 15;
        if ($paragraphCount >= 5) $score += 10;
        if ($charCount >= 300) $score += 10;
        if ($charCount >= 800) $score += 10;
        if ($charCount > 5000) $score -= 10; // 过长扣分

        // 检查段落平均长度（太长的段落扣分）
        if ($paragraphCount > 0) {
            $avgParaLen = $charCount / $paragraphCount;
            if ($avgParaLen > 500) $score -= 10;
            if ($avgParaLen < 50 && $charCount > 300) $score -= 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * SEO评分：复用SEO诊断服务
     */
    private function scoreSeo(Content $content): int
    {
        $score = 0;
        $count = 0;

        // SEO标题
        if (!empty($content->seo_title)) {
            $score += 25;
            $len = mb_strlen($content->seo_title);
            if ($len >= 10 && $len <= 60) $score += 5;
        }
        $count += 30;

        // SEO描述
        if (!empty($content->seo_description)) {
            $score += 20;
            $len = mb_strlen($content->seo_description);
            if ($len >= 50 && $len <= 160) $score += 5;
        }
        $count += 25;

        // SEO关键词
        if (!empty($content->seo_keywords)) {
            $score += 15;
            $kwCount = count(explode(',', $content->seo_keywords));
            if ($kwCount >= 3 && $kwCount <= 10) $score += 5;
        }
        $count += 20;

        // URL友好度
        if (!empty($content->slug)) $score += 15;
        $count += 15;

        // 标题包含关键词
        if (!empty($content->seo_keywords) && !empty($content->title)) {
            $keywords = explode(',', $content->seo_keywords);
            foreach ($keywords as $kw) {
                if (mb_strpos($content->title, trim($kw)) !== false) {
                    $score += 5;
                    break;
                }
            }
        }
        $count += 10;

        return min(100, $score);
    }

    /**
     * 配图匹配评分：检查是否有图/alt标签/尺寸
     */
    private function scoreImageMatch(Content $content): int
    {
        $score = 0;

        // 有封面图
        if (!empty($content->cover_image)) $score += 40;

        // 正文中是否有图片
        $hasImageInContent = preg_match('/<img\s/i', $content->content ?? '');
        if ($hasImageInContent) $score += 30;

        // 图片是否有alt属性
        if ($hasImageInContent) {
            $hasAlt = preg_match('/<img[^>]+alt=["\'][^"\']+["\']/i', $content->content);
            $totalImgs = preg_match_all('/<img\s/i', $content->content ?? '');
            $altImgs = preg_match_all('/<img[^>]+alt=["\'][^"\']+["\']/i', $content->content ?? '');
            if ($totalImgs > 0) {
                $altRatio = $altImgs / $totalImgs;
                $score += (int) round($altRatio * 30);
            }
        }

        return min(100, $score);
    }

    /**
     * 标签准确评分：基于标签与内容标题的匹配度
     */
    private function scoreTagAccuracy(Content $content): int
    {
        $tags = $content->tags ?? '';
        if (empty($tags)) return 30; // 无标签给基础分

        $tagArr = array_filter(explode(',', $tags));
        if (empty($tagArr)) return 30;

        $title = $content->title ?? '';
        $contentText = mb_substr(strip_tags($content->content ?? ''), 0, 500);

        $matchCount = 0;
        foreach ($tagArr as $tag) {
            $tag = trim($tag);
            if (empty($tag)) continue;
            if (mb_strpos($title, $tag) !== false || mb_strpos($contentText, $tag) !== false) {
                $matchCount++;
            }
        }

        $matchRate = $matchCount / count($tagArr);
        return (int) round($matchRate * 100);
    }

    /**
     * 评分转等级
     */
    private function scoreToLevel(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 60) return 'fair';
        return 'poor';
    }

    /**
     * 生成改进建议
     */
    private function generateSuggestions(array $scores): array
    {
        $suggestions = [];

        if ($scores['completeness'] < 60) {
            $suggestions[] = ['dimension' => 'completeness', 'score' => $scores['completeness'], 'suggestion' => '内容完整性不足，请补充缺失的摘要、封面图或SEO字段'];
        }
        if ($scores['readability'] < 60) {
            $suggestions[] = ['dimension' => 'readability', 'score' => $scores['readability'], 'suggestion' => '可读性较低，建议增加段落分隔、优化语句结构'];
        }
        if ($scores['seo'] < 60) {
            $suggestions[] = ['dimension' => 'seo', 'score' => $scores['seo'], 'suggestion' => 'SEO优化不足，请补充SEO标题、描述或关键词'];
        }
        if ($scores['image_match'] < 60) {
            $suggestions[] = ['dimension' => 'image_match', 'score' => $scores['image_match'], 'suggestion' => '配图匹配度低，建议添加封面图或为图片补充alt属性'];
        }
        if ($scores['tag_accuracy'] < 60) {
            $suggestions[] = ['dimension' => 'tag_accuracy', 'score' => $scores['tag_accuracy'], 'suggestion' => '标签准确度低，建议重新选择与内容更匹配的标签'];
        }

        return $suggestions;
    }
}
