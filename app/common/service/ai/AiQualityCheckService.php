<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI内容质量检查服务 — V2.9.40 Sprint AI-DEEP2-2
 *
 * 8维度质量评分（完整性15%/连贯性15%/语法10%/准确性15%/原创性15%/可读性10%/SEO10%/相关性10%）
 * 支持问题收集、改进建议生成、历史记录
 */
class AiQualityCheckService
{
    private const CACHE_TAG  = 'ai_quality';
    private const CACHE_TTL  = 300;
    private const TABLE_NAME = 'ai_quality_check';

    /** 8维度权重 */
    private const WEIGHTS = [
        'completeness' => 0.15,  // 完整性 15%
        'coherence'    => 0.15,  // 连贯性 15%
        'grammar'      => 0.10,  // 语法   10%
        'accuracy'     => 0.15,  // 准确性 15%
        'originality'  => 0.15,  // 原创性 15%
        'readability'  => 0.10,  // 可读性 10%
        'seo'          => 0.10,  // SEO    10%
        'relevance'    => 0.10,  // 相关性 10%
    ];

    /** @var array 临时存储问题列表 */
    protected array $issues = [];

    /**
     * 执行完整质量检查
     *
     * @param array $content 内容数据(title/content/summary/keywords/cate_id等)
     * @return array 检查结果
     */
    public function check(array $content): array
    {
        $this->issues = [];

        $scores = [
            'completeness' => $this->checkCompleteness($content),
            'coherence'     => $this->checkCoherence($content),
            'grammar'       => $this->checkGrammar($content),
            'accuracy'      => $this->checkAccuracy($content),
            'originality'   => $this->checkOriginality($content),
            'readability'   => $this->checkReadability($content),
            'seo'           => $this->checkSeo($content),
            'relevance'     => $this->checkRelevance($content),
        ];

        // 加权总分
        $totalScore = 0.0;
        foreach ($scores as $dim => $score) {
            $totalScore += $score * (self::WEIGHTS[$dim] ?? 0);
        }
        $totalScore = round($totalScore, 2);

        $grade = $this->scoreToGrade($totalScore);
        $issues = $this->collectIssues();
        $suggestions = $this->generateSuggestions($scores, $issues);

        return [
            'total_score'      => $totalScore,
            'grade'            => $grade,
            'dimension_scores' => $scores,
            'weights'          => self::WEIGHTS,
            'issues'           => $issues,
            'suggestions'      => $suggestions,
            'check_time'       => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 完整性检查 — 标题/内容/摘要/封面/SEO标题/SEO描述是否齐全
     */
    public function checkCompleteness(array $content): float
    {
        $fields = ['title', 'content', 'summary', 'cover_image', 'seo_title', 'seo_description'];
        $filled = 0;
        $total = count($fields);

        foreach ($fields as $field) {
            $value = $content[$field] ?? '';
            if (!empty($value) && trim((string) $value) !== '') {
                $filled++;
            } else {
                $this->issues[] = ['dimension' => 'completeness', 'field' => $field, 'message' => "字段 {$field} 为空"];
            }
        }

        $score = ($filled / $total) * 100;

        // 内容长度加分：>500字满分，<100字扣分
        $contentLength = mb_strlen($content['content'] ?? '');
        if ($contentLength < 100) {
            $score *= 0.5;
            $this->issues[] = ['dimension' => 'completeness', 'message' => '内容过短(<100字)'];
        } elseif ($contentLength < 300) {
            $score *= 0.8;
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 连贯性检查 — 段落结构/过渡词/逻辑衔接
     */
    public function checkCoherence(array $content): float
    {
        $text = $content['content'] ?? '';
        if (empty($text)) {
            $this->issues[] = ['dimension' => 'coherence', 'message' => '内容为空，无法评估连贯性'];
            return 0.0;
        }

        $score = 60.0; // 基础分

        // 段落数量
        $paragraphs = array_filter(explode("\n", $text), fn($p) => trim($p) !== '');
        $paraCount = count($paragraphs);
        if ($paraCount >= 3) {
            $score += 15;
        } elseif ($paraCount >= 2) {
            $score += 8;
        } else {
            $score -= 10;
            $this->issues[] = ['dimension' => 'coherence', 'message' => '段落过少，建议分段'];
        }

        // 过渡词检测
        $transitions = ['首先', '其次', '然后', '接着', '最后', '此外', '另外', '因此', '所以', '然而', '但是', '总之', '综上', '与此', '同时', '然而'];
        $transitionCount = 0;
        foreach ($transitions as $word) {
            $transitionCount += substr_count($text, $word);
        }
        if ($transitionCount >= 2) {
            $score += 15;
        } elseif ($transitionCount >= 1) {
            $score += 8;
        }

        // 标题层级
        $hasH2 = preg_match('/<h2|##\s/', $text);
        $hasH3 = preg_match('/<h3|###\s/', $text);
        if ($hasH2 || $hasH3) {
            $score += 10;
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 语法检查 — 基本语法规则
     */
    public function checkGrammar(array $content): float
    {
        $text = $content['content'] ?? '';
        if (empty($text)) {
            return 0.0;
        }

        $score = 85.0;
        $penalty = 0;

        // 检测常见语法问题
        // 1. 重复标点
        if (preg_match('/[，。！？]{2,}/u', $text)) {
            $penalty += 5;
            $this->issues[] = ['dimension' => 'grammar', 'message' => '存在重复标点符号'];
        }

        // 2. 连续空格
        if (preg_match('/  +/', $text)) {
            $penalty += 3;
            $this->issues[] = ['dimension' => 'grammar', 'message' => '存在连续空格'];
        }

        // 3. 中英文混排空格缺失（粗略检测）
        if (preg_match('/[a-zA-Z][\x{4e00}-\x{9fa5}]|[\x{4e00}-\x{9fa5}][a-zA-Z]/u', $text)) {
            $penalty += 2;
        }

        // 4. HTML标签未闭合（简单检测）
        if (preg_match('/<([a-z]+)[^>]*>.*(?<!<\/\1>)(?!<\1)/i', $text) && substr_count($text, '<') !== substr_count($text, '>')) {
            $penalty += 5;
            $this->issues[] = ['dimension' => 'grammar', 'message' => '可能存在未闭合的HTML标签'];
        }

        // 5. 标题长度检查
        $title = $content['title'] ?? '';
        if (!empty($title) && mb_strlen($title) > 60) {
            $penalty += 3;
            $this->issues[] = ['dimension' => 'grammar', 'message' => '标题过长(>60字)'];
        }

        $score -= $penalty;

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 准确性检查 — 事实性/数据引用
     */
    public function checkAccuracy(array $content): float
    {
        $text = $content['content'] ?? '';
        if (empty($text)) {
            return 0.0;
        }

        $score = 70.0;

        // 引用来源检测
        $hasCitation = preg_match('/(来源|引用|参考|据|根据)/u', $text);
        if ($hasCitation) {
            $score += 15;
        }

        // 数据/数字检测
        $hasData = preg_match('/\d+[%％]|\d+\.\d+|\d{4}年/', $text);
        if ($hasData) {
            $score += 10;
        }

        // 链接检测
        $hasLink = preg_match('/https?:\/\/|www\./i', $text);
        if ($hasLink) {
            $score += 5;
        }

        // 如果内容极短且无引用，降低准确性评分
        if (mb_strlen($text) < 200 && !$hasCitation) {
            $score -= 10;
            $this->issues[] = ['dimension' => 'accuracy', 'message' => '内容简短且无引用来源'];
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 原创性检查 — 查重/独特性
     */
    public function checkOriginality(array $content): float
    {
        $text = $content['content'] ?? '';
        if (empty($text)) {
            return 0.0;
        }

        $score = 75.0;

        // 1. 重复句子检测
        $sentences = preg_split('/[。！？\n]/u', $text);
        $sentences = array_filter(array_map('trim', $sentences), fn($s) => mb_strlen($s) > 5);
        $unique = array_unique($sentences);

        if (count($sentences) > 0) {
            $dupRate = 1 - (count($unique) / count($sentences));
            if ($dupRate > 0.3) {
                $score -= 20;
                $this->issues[] = ['dimension' => 'originality', 'message' => '存在大量重复句子(' . round($dupRate * 100) . '%)'];
            } elseif ($dupRate > 0.1) {
                $score -= 10;
            }
        }

        // 2. 模板化短语检测
        $cliches = ['众所周知', '随着时代的发展', '在当今社会', '总而言之', '通过以上分析'];
        $clicheCount = 0;
        foreach ($cliches as $cliche) {
            $clicheCount += substr_count($text, $cliche);
        }
        if ($clicheCount > 0) {
            $score -= $clicheCount * 5;
            $this->issues[] = ['dimension' => 'originality', 'message' => "检测到{$clicheCount}处模板化短语"];
        }

        // 3. 内容独特性（通过字符多样性）
        $charCount = count(array_unique(mb_str_split($text)));
        if ($charCount > 200) {
            $score += 15;
        } elseif ($charCount > 100) {
            $score += 8;
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 可读性检查 — 文本复杂度/句子长度
     */
    public function checkReadability(array $content): float
    {
        $text = $content['content'] ?? '';
        if (empty($text)) {
            return 0.0;
        }

        $score = 70.0;

        // 平均句长
        $sentences = preg_split('/[。！？\n]/u', $text);
        $sentences = array_filter(array_map('trim', $sentences), fn($s) => !empty($s));
        $sentenceCount = count($sentences);

        if ($sentenceCount > 0) {
            $totalChars = mb_strlen(implode('', $sentences));
            $avgLen = $totalChars / $sentenceCount;

            if ($avgLen <= 30) {
                $score += 20; // 短句易读
            } elseif ($avgLen <= 50) {
                $score += 10;
            } elseif ($avgLen <= 80) {
                $score -= 5;
            } else {
                $score -= 15;
                $this->issues[] = ['dimension' => 'readability', 'message' => '平均句长过长(' . round($avgLen) . '字/句)'];
            }
        }

        // 段落均衡度
        $paragraphs = array_filter(explode("\n", $text), fn($p) => trim($p) !== '');
        if (count($paragraphs) >= 3) {
            $paraLengths = array_map(fn($p) => mb_strlen(trim($p)), $paragraphs);
            $avgPara = array_sum($paraLengths) / count($paraLengths);
            if ($avgPara > 50 && $avgPara < 300) {
                $score += 10;
            }
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * SEO检查 — 关键词/标题/描述/标签
     */
    public function checkSeo(array $content): float
    {
        $score = 50.0;

        $title = $content['title'] ?? '';
        $seoTitle = $content['seo_title'] ?? '';
        $seoDesc = $content['seo_description'] ?? '';
        $keywords = $content['keywords'] ?? $content['tags'] ?? '';
        $text = $content['content'] ?? '';

        // SEO标题
        if (!empty($seoTitle)) {
            $score += 15;
        } else {
            $this->issues[] = ['dimension' => 'seo', 'message' => '缺少SEO标题'];
        }

        // SEO描述
        if (!empty($seoDesc)) {
            $score += 10;
            if (mb_strlen($seoDesc) >= 50 && mb_strlen($seoDesc) <= 160) {
                $score += 5; // 长度适中
            }
        } else {
            $this->issues[] = ['dimension' => 'seo', 'message' => '缺少SEO描述'];
        }

        // 关键词
        if (!empty($keywords)) {
            $score += 10;
            // 关键词在内容中的密度
            $kwList = is_array($keywords) ? $keywords : explode(',', (string) $keywords);
            $kwList = array_filter(array_map('trim', $kwList));
            if (!empty($kwList) && !empty($text)) {
                $density = 0;
                foreach ($kwList as $kw) {
                    if (!empty($kw)) {
                        $count = substr_count($text, $kw);
                        $density += $count;
                    }
                }
                if ($density >= count($kwList)) {
                    $score += 5;
                }
            }
        } else {
            $this->issues[] = ['dimension' => 'seo', 'message' => '缺少关键词'];
        }

        // 标题长度
        if (!empty($title)) {
            $titleLen = mb_strlen($title);
            if ($titleLen >= 15 && $titleLen <= 60) {
                $score += 5;
            }
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 相关性检查 — 标题与内容的关联度
     */
    public function checkRelevance(array $content): float
    {
        $title = $content['title'] ?? '';
        $text = $content['content'] ?? '';
        $keywords = $content['keywords'] ?? $content['tags'] ?? '';

        if (empty($title) || empty($text)) {
            return 0.0;
        }

        $score = 60.0;

        // 标题关键词在内容中的覆盖率
        $titleWords = $this->extractKeywords($title);
        if (!empty($titleWords)) {
            $matched = 0;
            foreach ($titleWords as $word) {
                if (mb_strlen($word) >= 2 && mb_strpos($text, $word) !== false) {
                    $matched++;
                }
            }
            $coverage = $matched / count($titleWords);
            $score += $coverage * 25;
        }

        // 关键词与内容匹配
        if (!empty($keywords)) {
            $kwList = is_array($keywords) ? $keywords : explode(',', (string) $keywords);
            $kwMatched = 0;
            foreach ($kwList as $kw) {
                $kw = trim($kw);
                if (!empty($kw) && mb_strpos($text, $kw) !== false) {
                    $kwMatched++;
                }
            }
            if (count($kwList) > 0) {
                $kwCoverage = $kwMatched / count($kwList);
                $score += $kwCoverage * 15;
            }
        }

        if ($score < 50) {
            $this->issues[] = ['dimension' => 'relevance', 'message' => '标题与内容关联度较低'];
        }

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * 评分转等级
     */
    public function scoreToGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'C-';
        return 'D';
    }

    /**
     * 收集所有问题
     */
    public function collectIssues(): array
    {
        return $this->issues;
    }

    /**
     * 生成改进建议
     */
    public function generateSuggestions(array $scores = [], array $issues = []): string
    {
        if (empty($scores)) {
            $scores = [];
        }

        $suggestions = [];

        // 按维度生成建议
        $dimLabels = [
            'completeness' => '完整性',
            'coherence'    => '连贯性',
            'grammar'       => '语法',
            'accuracy'      => '准确性',
            'originality'   => '原创性',
            'readability'   => '可读性',
            'seo'           => 'SEO优化',
            'relevance'     => '相关性',
        ];

        foreach ($scores as $dim => $score) {
            if ($score < 60) {
                $label = $dimLabels[$dim] ?? $dim;
                $suggestions[] = "【{$label}】评分较低({$score})，需重点改进。";
            }
        }

        // 按问题生成具体建议
        foreach ($issues as $issue) {
            $dim = $issue['dimension'] ?? '';
            $msg = $issue['message'] ?? '';
            switch ($dim) {
                case 'completeness':
                    $suggestions[] = "补充完善 {$issue['field'] ?? '缺失'} 字段内容。";
                    break;
                case 'coherence':
                    $suggestions[] = "增加过渡词和段落分隔，提升内容结构连贯性。";
                    break;
                case 'originality':
                    $suggestions[] = "减少模板化表达和重复内容，增加独特观点。";
                    break;
                case 'seo':
                    $suggestions[] = "完善SEO标题、描述和关键词设置。";
                    break;
                case 'relevance':
                    $suggestions[] = "确保标题关键词在内容中充分体现。";
                    break;
            }
        }

        if (empty($suggestions)) {
            return '内容质量良好，各维度评分均在合格线以上，建议继续保持。';
        }

        return implode("\n", $suggestions);
    }

    /**
     * 保存检查结果到数据库
     *
     * @param int   $contentId 内容ID
     * @param array $result     检查结果
     * @return int 检查记录ID
     */
    public function saveCheckResult(int $contentId, array $result): int
    {
        $now = date('Y-m-d H:i:s');

        $id = (int) Db::name(self::TABLE_NAME)->insertGetId([
            'content_id'       => $contentId,
            'content_type'     => $result['content_type'] ?? 'article',
            'ai_generated'     => $result['ai_generated'] ?? 1,
            'quality_score'    => $result['total_score'] ?? 0,
            'dimension_scores' => json_encode($result['dimension_scores'] ?? [], JSON_UNESCAPED_UNICODE),
            'check_rules'      => json_encode($result['check_rules'] ?? [], JSON_UNESCAPED_UNICODE),
            'issues'           => json_encode($result['issues'] ?? [], JSON_UNESCAPED_UNICODE),
            'suggestions'      => $result['suggestions'] ?? '',
            'auto_optimized'   => 0,
            'optimized_content'=> '',
            'check_time'       => $now,
            'create_time'      => $now,
        ]);

        Cache::clear();

        return $id;
    }

    /**
     * 获取内容的检查历史
     *
     * @param int $contentId 内容ID
     * @return array 检查历史列表
     */
    public function getCheckHistory(int $contentId): array
    {
        return Cache::remember(
            'ai_quality_history_' . $contentId,
            function () use ($contentId) {
                return Db::name(self::TABLE_NAME)
                    ->where('content_id', $contentId)
                    ->order('id', 'desc')
                    ->select()
                    ->toArray();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 从文本中提取关键词（简单分词）
     */
    protected function extractKeywords(string $text): array
    {
        // 移除标点符号
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $words = preg_split('/\s+/u', $cleaned);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= 2);

        // 去重并取前10个
        return array_slice(array_unique($words), 0, 10);
    }
}
