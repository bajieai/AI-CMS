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

namespace app\common\utils;

/**
 * V2.9.4 可读性评分工具
 * 中文统计模型：基于平均句长+长句占比+难词密度
 */
class ReadabilityHelper
{
    /**
     * 计算可读性评分
     * @return array ['score'=>0-100, 'level'=>难度等级, 'min_read'=>阅读分钟, 'suggestions'=>[]]
     */
    public static function analyze(string $title, string $content): array
    {
        // 清除HTML标签
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        if (mb_strlen(trim($text)) < 10) {
            return [
                'score' => 0,
                'level' => '无法评估',
                'min_read' => 0,
                'avg_sentence_len' => 0,
                'long_sentence_ratio' => 0,
                'suggestions' => ['内容太短，无法进行可读性评估'],
            ];
        }

        // 1. 分句（按中英文标点）
        $sentences = preg_split('/[。！？；.!?;]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences, fn($s) => mb_strlen($s) > 0);

        $sentenceCount = count($sentences);
        if ($sentenceCount === 0) {
            return ['score' => 50, 'level' => '一般', 'min_read' => 1, 'avg_sentence_len' => 0, 'long_sentence_ratio' => 0, 'suggestions' => []];
        }

        // 2. 计算总字数（中文按字计，英文按空格分词）
        $totalChars = mb_strlen(preg_replace('/\s+/', '', $text));

        // 3. 平均句长
        $totalSentenceLen = 0;
        $longSentenceCount = 0;
        foreach ($sentences as $s) {
            $len = mb_strlen(preg_replace('/\s+/', '', $s));
            $totalSentenceLen += $len;
            if ($len > 30) $longSentenceCount++;
        }
        $avgSentenceLen = round($totalSentenceLen / $sentenceCount, 1);
        $longSentenceRatio = round($longSentenceCount / $sentenceCount * 100, 1);

        // 4. 难词密度（四字以上专业术语/成语估算）
        $hardWordCount = preg_match_all('/[\x{4e00}-\x{9fff}]{4,}/u', $text);
        $hardWordDensity = $totalChars > 0 ? round($hardWordCount / $totalChars * 100, 2) : 0;

        // 5. 阅读时长（中文约200字/分钟）
        $minRead = max(1, (int) ceil($totalChars / 200));

        // 6. 难度等级判定
        $level = self::determineLevel($avgSentenceLen, $longSentenceRatio, $hardWordDensity);

        // 7. 计算评分（0-100）
        $score = self::calculateScore($avgSentenceLen, $longSentenceRatio, $hardWordDensity);

        // 8. 生成建议
        $suggestions = self::generateSuggestions($avgSentenceLen, $longSentenceRatio, $hardWordDensity, $totalChars);

        return [
            'score' => $score,
            'level' => $level,
            'min_read' => $minRead,
            'avg_sentence_len' => $avgSentenceLen,
            'long_sentence_ratio' => $longSentenceRatio,
            'hard_word_density' => $hardWordDensity,
            'total_chars' => $totalChars,
            'sentence_count' => $sentenceCount,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * 判定难度等级
     */
    protected static function determineLevel(float $avgLen, float $longRatio, float $hardDensity): string
    {
        if ($avgLen < 15 && $longRatio < 10) return '小学';
        if ($avgLen < 20 && $longRatio < 20) return '初中';
        if ($avgLen < 25 && $longRatio < 30) return '高中';
        if ($avgLen < 35 && $longRatio < 40) return '大学';
        return '专业';
    }

    /**
     * 计算可读性评分（0-100）
     * 最优：平均句长15-25，长句比<20%，难词密度1-3%
     */
    protected static function calculateScore(float $avgLen, float $longRatio, float $hardDensity): int
    {
        $score = 100;

        // 平均句长偏差扣分
        if ($avgLen < 10) $score -= 15;
        elseif ($avgLen < 15) $score -= 5;
        elseif ($avgLen <= 25) $score -= 0;
        elseif ($avgLen <= 35) $score -= 10;
        elseif ($avgLen <= 50) $score -= 20;
        else $score -= 30;

        // 长句占比扣分
        if ($longRatio > 50) $score -= 25;
        elseif ($longRatio > 40) $score -= 20;
        elseif ($longRatio > 30) $score -= 15;
        elseif ($longRatio > 20) $score -= 10;
        elseif ($longRatio > 10) $score -= 5;

        // 难词密度扣分
        if ($hardDensity > 8) $score -= 15;
        elseif ($hardDensity > 5) $score -= 10;
        elseif ($hardDensity > 3) $score -= 5;

        return max(0, min(100, $score));
    }

    /**
     * 生成优化建议
     */
    protected static function generateSuggestions(float $avgLen, float $longRatio, float $hardDensity, int $totalChars): array
    {
        $suggestions = [];

        if ($avgLen > 30) {
            $suggestions[] = ['type' => 'warning', 'msg' => '平均句长较长（' . $avgLen . '字/句），建议拆分长句，控制在15-25字'];
        }
        if ($longSentenceRatio > 30) {
            $suggestions[] = ['type' => 'warning', 'msg' => '长句占比' . $longSentenceRatio . '%，建议减少超30字的长句'];
        }
        if ($hardWordDensity > 5) {
            $suggestions[] = ['type' => 'info', 'msg' => '难词密度较高（' . $hardDensity . '%），可适当添加解释说明'];
        }
        if ($totalChars < 300) {
            $suggestions[] = ['type' => 'info', 'msg' => '文章较短（' . $totalChars . '字），建议丰富内容'];
        }
        if ($avgLen >= 15 && $avgLen <= 25 && $longSentenceRatio < 20) {
            $suggestions[] = ['type' => 'success', 'msg' => '句子长度适中，可读性良好'];
        }

        return $suggestions;
    }
}
