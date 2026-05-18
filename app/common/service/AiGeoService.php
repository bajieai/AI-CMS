<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;

/**
 * AI-GEO 深度优化服务 - V2.9.9
 * AI友好度评分 + 实体标记 + 结构化输出增强
 */
class AiGeoService
{
    /**
     * 计算内容的AI友好度评分（4维度）
     *
     * @param Content|array $content
     * @return array {total, dimensions: [{name,score,max,suggestion},...]}
     */
    public static function score(Content|array $content): array
    {
        $isArray = is_array($content);
        $title = $isArray ? ($content['title'] ?? '') : ($content->title ?? '');
        $body = $isArray ? strip_tags($content['content'] ?? '') : strip_tags($content->content ?? '');
        $wordCount = mb_strlen($body);

        // 维度1: 段落结构 (0-25)
        $structureScore = self::scoreStructure($body);

        // 维度2: 事实引用密度 (0-25)
        $citationScore = self::scoreCitation($body);

        // 维度3: 权威性与完整性 (0-25)
        $authorityScore = self::scoreAuthority($title, $body, $wordCount);

        // 维度4: 实体密度 (0-25)
        $entityScore = self::scoreEntityDensity($body);

        $total = $structureScore + $citationScore + $authorityScore + $entityScore;

        return [
            'total' => $total,
            'grade' => $total >= 85 ? 'A' : ($total >= 70 ? 'B' : ($total >= 50 ? 'C' : 'D')),
            'dimensions' => [
                [
                    'name' => '段落结构',
                    'score' => $structureScore,
                    'max' => 25,
                    'suggestion' => $structureScore >= 20 ? '结构良好' : '建议增加小标题和列表，使内容层次更清晰',
                ],
                [
                    'name' => '事实引用',
                    'score' => $citationScore,
                    'max' => 25,
                    'suggestion' => $citationScore >= 20 ? '引用充分' : '建议增加数据、来源或案例引用，提升可信度',
                ],
                [
                    'name' => '权威完整',
                    'score' => $authorityScore,
                    'max' => 25,
                    'suggestion' => $authorityScore >= 20 ? '内容完整' : '建议扩展内容深度，增加专业术语和背景说明',
                ],
                [
                    'name' => '实体密度',
                    'score' => $entityScore,
                    'max' => 25,
                    'suggestion' => $entityScore >= 20 ? '实体丰富' : '建议增加关键词、人名、地名、机构名等实体',
                ],
            ],
        ];
    }

    /**
     * 评分：段落结构质量
     */
    protected static function scoreStructure(string $body): int
    {
        $score = 10;

        // 有小标题（H2/H3）加分
        if (preg_match('/<h[2-6][^>]*>/i', $body) || preg_match('/^#{2,6}\s/m', $body)) {
            $score += 5;
        }

        // 有列表项加分
        if (preg_match('/<(?:ul|ol)[^>]*>/i', $body) || preg_match('/^[\-\*•]\s/m', $body)) {
            $score += 5;
        }

        // 段落数适中（3-15段）加分
        $paragraphs = preg_split('/\n\s*\n/', $body, -1, PREG_SPLIT_NO_EMPTY);
        $paraCount = count($paragraphs);
        if ($paraCount >= 3 && $paraCount <= 15) {
            $score += 5;
        }

        return min(25, $score);
    }

    /**
     * 评分：事实引用密度
     */
    protected static function scoreCitation(string $body): int
    {
        $score = 10;

        // 数字引用（年份、百分比、统计数据）
        if (preg_match('/\d{4}年|\d+%|\d+\.\d+%/', $body)) {
            $score += 5;
        }

        // 来源引用（据...、来自...、引用...）
        if (preg_match('/(?:据|根据|引用|来自|研究表明|数据显示)/u', $body)) {
            $score += 5;
        }

        // 链接引用
        if (preg_match('/https?:\/\//', $body)) {
            $score += 5;
        }

        return min(25, $score);
    }

    /**
     * 评分：权威性与完整性
     */
    protected static function scoreAuthority(string $title, string $body, int $wordCount): int
    {
        $score = 10;

        // 字数达标（>800字）
        if ($wordCount >= 800) {
            $score += 5;
        }
        if ($wordCount >= 1500) {
            $score += 3;
        }

        // 包含专业术语（2字以上英文或特定中文术语）
        if (preg_match('/[A-Za-z]{3,}/', $body)) {
            $score += 3;
        }

        // 标题完整（不含疑问词可能更权威）
        if (mb_strlen($title) >= 10 && mb_strlen($title) <= 40) {
            $score += 4;
        }

        return min(25, $score);
    }

    /**
     * 评分：实体密度
     */
    protected static function scoreEntityDensity(string $body): int
    {
        $score = 10;

        // 人名检测（常见中文姓名模式）
        if (preg_match('/[\x{4e00}-\x{9fa5}]{2,4}(?:先生|女士|博士|教授|院士)/u', $body)) {
            $score += 5;
        }

        // 地名/机构名检测
        if (preg_match('/(?:公司|集团|大学|研究院|中心|局|部|省|市|区)/u', $body)) {
            $score += 5;
        }

        // 专有名词大写英文
        if (preg_match('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b/', $body)) {
            $score += 5;
        }

        return min(25, $score);
    }

    /**
     * 生成AI搜索友好的摘要（增强版）
     *
     * @param string $title
     * @param string $body
     * @return string
     */
    public static function generateAiSummary(string $title, string $body): string
    {
        $text = strip_tags($body);
        $firstPara = '';
        if (preg_match('/^(.{50,300})(?:\n|$)/u', $text, $m)) {
            $firstPara = $m[1];
        }

        $summary = $title . '。';
        if ($firstPara) {
            $summary .= $firstPara;
        }

        return mb_substr($summary, 0, 300);
    }

    /**
     * 提取内容中的关键实体（预留：后续接入NER）
     *
     * @param string $body
     * @return array {persons, organizations, locations, keywords}
     */
    public static function extractEntities(string $body): array
    {
        $text = strip_tags($body);

        // 轻量级规则提取（预留AI NER接口）
        $persons = [];
        $orgs = [];
        $locations = [];

        // 人名：XX先生/女士/博士
        if (preg_match_all('/([\x{4e00}-\x{9fa5}]{2,4})(?:先生|女士|博士|教授)/u', $text, $m)) {
            $persons = array_unique($m[1]);
        }

        // 机构：XX公司/集团/大学
        if (preg_match_all('/([\x{4e00}-\x{9fa5}]{2,8}(?:公司|集团|大学|研究院|中心))/u', $text, $m)) {
            $orgs = array_unique($m[1]);
        }

        // 地点：XX省/市/区
        if (preg_match_all('/([\x{4e00}-\x{9fa5}]{2,6}(?:省|市|自治区|区|县))/u', $text, $m)) {
            $locations = array_unique($m[1]);
        }

        return [
            'persons'       => array_values($persons),
            'organizations' => array_values($orgs),
            'locations'     => array_values($locations),
            'keywords'      => [], // 预留：后续用TF-IDF或AI提取
        ];
    }

    /**
     * 生成AI搜索Sitemap（答案引擎专用）
     *
     * @param array $contents Content模型数组
     * @return string XML
     */
    public static function generateAiSitemap(array $contents): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($contents as $content) {
            $url = $content->url ?? '';
            if (empty($url)) continue;

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars((string) url($url), ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d', $content->update_time ?: time()) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
