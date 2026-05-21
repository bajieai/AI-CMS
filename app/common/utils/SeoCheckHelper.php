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

namespace app\common\utils;

/**
 * V2.9.4 SEO友好度检测工具
 * 6维度：标题长度/关键词密度/H标签结构/图片alt覆盖/内链数/外链数
 */
class SeoCheckHelper
{
    /**
     * SEO检测
     * @return array ['score'=>0-100, 'dimensions'=>[], 'suggestions'=>[]]
     */
    public static function analyze(string $title, string $content, string $keywords = ''): array
    {
        $dimensions = [];
        $suggestions = [];

        // 1. 标题长度（权重20%）理想值：10-30字
        $titleLen = mb_strlen($title);
        $titleScore = self::scoreTitleLength($titleLen);
        $dimensions['title_length'] = [
            'label' => '标题长度',
            'value' => $titleLen . '字',
            'score' => $titleScore,
            'ideal' => '10-30字',
            'weight' => 20,
        ];
        if ($titleLen < 10) {
            $suggestions[] = ['type' => 'warning', 'msg' => '标题过短（' . $titleLen . '字），建议10-30字以提升搜索可见度'];
        } elseif ($titleLen > 30) {
            $suggestions[] = ['type' => 'warning', 'msg' => '标题过长（' . $titleLen . '字），建议30字以内避免截断'];
        }

        // 2. 关键词密度（权重20%）理想值：2%-8%
        $keywordScore = 0;
        $keywordDensity = '0%';
        if (!empty($keywords)) {
            $kwList = array_filter(array_map('trim', explode(',', $keywords)));
            $textForDensity = strip_tags($content) . $title;
            $totalChars = mb_strlen($textForDensity);
            if ($totalChars > 0) {
                $matchCount = 0;
                foreach ($kwList as $kw) {
                    if (empty($kw)) continue;
                    $matchCount += substr_count($textForDensity, $kw);
                }
                $keywordDensity = $totalChars > 0 ? round($matchCount / $totalChars * 100, 2) . '%' : '0%';
                $densityVal = $totalChars > 0 ? $matchCount / $totalChars * 100 : 0;
                $keywordScore = ($densityVal >= 2 && $densityVal <= 8) ? 100 :
                    ($densityVal < 2 ? max(0, (int) ($densityVal / 2 * 100)) : max(0, 100 - (int) (($densityVal - 8) * 10)));

                if ($densityVal < 2) {
                    $suggestions[] = ['type' => 'warning', 'msg' => '关键词密度过低（' . $keywordDensity . '），建议2%-8%以利于SEO'];
                } elseif ($densityVal > 8) {
                    $suggestions[] = ['type' => 'warning', 'msg' => '关键词密度过高（' . $keywordDensity . '），可能被视为关键词堆砌'];
                }
            }
        } else {
            $suggestions[] = ['type' => 'info', 'msg' => '未设置SEO关键词，建议填写以优化搜索排名'];
        }
        $dimensions['keyword_density'] = [
            'label' => '关键词密度',
            'value' => $keywordDensity,
            'score' => $keywordScore,
            'ideal' => '2%-8%',
            'weight' => 20,
        ];

        // 3. H标签结构（权重20%）
        $hScore = 0;
        $hasH1 = preg_match('/<h1[^>]*>/i', $content);
        $hasH2 = preg_match('/<h2[^>]*>/i', $content);
        $hTags = [];
        if ($hasH1) { $hScore += 50; $hTags[] = 'H1'; }
        if ($hasH2) { $hScore += 30; $hTags[] = 'H2'; }
        if (preg_match('/<h3[^>]*>/i', $content)) { $hScore += 20; $hTags[] = 'H3'; }
        if (!$hasH1) {
            $suggestions[] = ['type' => 'warning', 'msg' => '缺少H1标签，建议添加主标题H1'];
        }
        if (!$hasH2 && mb_strlen(strip_tags($content)) > 500) {
            $suggestions[] = ['type' => 'info', 'msg' => '内容较长但缺少H2子标题，建议分段添加'];
        }
        $dimensions['h_tag_structure'] = [
            'label' => 'H标签结构',
            'value' => !empty($hTags) ? implode('+', $hTags) : '无',
            'score' => $hScore,
            'ideal' => 'H1+H2+H3',
            'weight' => 20,
        ];

        // 4. 图片alt覆盖（权重15%）
        $imgScore = 100;
        $imgTotal = preg_match_all('/<img[^>]+>/i', $content);
        $imgWithAlt = preg_match_all('/<img[^>]+alt=["\'][^"\']+["\']/i', $content);
        $altCoverage = $imgTotal > 0 ? round($imgWithAlt / $imgTotal * 100, 1) . '%' : 'N/A';
        if ($imgTotal > 0 && $imgWithAlt < $imgTotal) {
            $imgScore = (int) round($imgWithAlt / $imgTotal * 100);
            $suggestions[] = ['type' => 'warning', 'msg' => '图片alt覆盖率' . $altCoverage . '（' . $imgWithAlt . '/' . $imgTotal . '），建议所有图片添加alt属性'];
        }
        $dimensions['img_alt_coverage'] = [
            'label' => '图片alt覆盖',
            'value' => $altCoverage,
            'score' => $imgScore,
            'ideal' => '>80%',
            'weight' => 15,
        ];

        // 5. 内链数量（权重10%）
        $internalLinks = 0;
        preg_match_all('/<a[^>]+href=["\'][^"\']+["\']/i', $content, $allLinks);
        $siteHost = $_SERVER['HTTP_HOST'] ?? '';
        foreach ($allLinks[0] as $link) {
            if (str_contains($link, $siteHost) || preg_match('/href=["\']\/[^\/]/', $link) || preg_match('/href=["\']\/admin/', $link)) {
                // 不算后台链接
                if (!str_contains($link, '/admin/')) $internalLinks++;
            }
        }
        $internalScore = ($internalLinks >= 2 && $internalLinks <= 5) ? 100 : ($internalLinks < 2 ? 50 : 70);
        if ($internalLinks < 2) {
            $suggestions[] = ['type' => 'info', 'msg' => '内链数量较少（' . $internalLinks . '个），建议2-5个内链提升SEO'];
        }
        $dimensions['internal_links'] = [
            'label' => '内链数量',
            'value' => $internalLinks . '个',
            'score' => $internalScore,
            'ideal' => '2-5个',
            'weight' => 10,
        ];

        // 6. 外链数量（权重15%）
        $externalLinks = count($allLinks[0]) - $internalLinks;
        $externalScore = ($externalLinks >= 0 && $externalLinks <= 2) ? 100 : ($externalLinks <= 5 ? 70 : 40);
        if ($externalLinks > 3) {
            $suggestions[] = ['type' => 'info', 'msg' => '外链较多（' . $externalLinks . '个），过多的外链可能分散权重'];
        }
        $dimensions['external_links'] = [
            'label' => '外链数量',
            'value' => $externalLinks . '个',
            'score' => $externalScore,
            'ideal' => '0-2个',
            'weight' => 15,
        ];

        // 计算加权总分
        $totalScore = 0;
        foreach ($dimensions as $dim) {
            $totalScore += $dim['score'] * $dim['weight'] / 100;
        }
        $totalScore = (int) round($totalScore);

        return [
            'score' => $totalScore,
            'dimensions' => $dimensions,
            'suggestions' => $suggestions,
        ];
    }

    protected static function scoreTitleLength(int $len): int
    {
        if ($len >= 10 && $len <= 30) return 100;
        if ($len < 10) return max(0, $len * 10);
        return max(0, 100 - ($len - 30) * 5);
    }
}
