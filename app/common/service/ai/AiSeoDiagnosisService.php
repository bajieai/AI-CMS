<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint AI3: AI SEO诊断服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use think\facade\Cache;
use think\facade\Db;

/**
 * AI SEO诊断服务 - V2.9.31 AI3-1
 * 提供内容SEO深度诊断、问题发现、修复建议
 */
class AiSeoDiagnosisService
{
    private const string CACHE_TAG = 'seo_diagnosis';

    /**
     * 对单条内容进行SEO诊断
     */
    public function diagnose(int $contentId): array
    {
        $cacheKey = "seo_diagnosis_{$contentId}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $issues = [];
        $score = 100;

        // 1. 标题诊断
        $title = $content->seo_title ?: $content->title;
        $titleLen = mb_strlen($title);
        if ($titleLen < 10) {
            $issues[] = ['type' => 'title', 'severity' => 'high', 'message' => '标题过短（' . $titleLen . '字符），建议10-60字符'];
            $score -= 15;
        } elseif ($titleLen > 60) {
            $issues[] = ['type' => 'title', 'severity' => 'medium', 'message' => '标题过长（' . $titleLen . '字符），建议不超过60字符'];
            $score -= 10;
        }
        if (!preg_match('/[\x{4e00}-\x{9fff}]/u', $title)) {
            $issues[] = ['type' => 'title', 'severity' => 'low', 'message' => '标题不含中文，可能影响中文搜索排名'];
            $score -= 5;
        }

        // 2. 描述诊断
        $desc = $content->seo_description ?: $content->description ?: '';
        $descLen = mb_strlen($desc);
        if ($descLen < 50) {
            $issues[] = ['type' => 'description', 'severity' => 'high', 'message' => '描述过短（' . $descLen . '字符），建议50-160字符'];
            $score -= 15;
        } elseif ($descLen > 160) {
            $issues[] = ['type' => 'description', 'severity' => 'medium', 'message' => '描述过长（' . $descLen . '字符），建议不超过160字符'];
            $score -= 10;
        }

        // 3. 关键词诊断
        $keywords = $content->seo_keywords ? explode(',', $content->seo_keywords) : [];
        $kwCount = count(array_filter($keywords, fn($k) => trim($k) !== ''));
        if ($kwCount < 2) {
            $issues[] = ['type' => 'keywords', 'severity' => 'medium', 'message' => '关键词数量不足（' . $kwCount . '个），建议2-5个'];
            $score -= 10;
        } elseif ($kwCount > 8) {
            $issues[] = ['type' => 'keywords', 'severity' => 'low', 'message' => '关键词过多（' . $kwCount . '个），可能被视为关键词堆砌'];
            $score -= 5;
        }

        // 4. 内容质量诊断
        $contentText = strip_tags($content->content ?? '');
        $contentLen = mb_strlen($contentText);
        if ($contentLen < 300) {
            $issues[] = ['type' => 'content', 'severity' => 'high', 'message' => '内容过短（' . $contentLen . '字符），建议不少于300字符'];
            $score -= 20;
        } elseif ($contentLen < 800) {
            $issues[] = ['type' => 'content', 'severity' => 'medium', 'message' => '内容偏短（' . $contentLen . '字符），建议800字符以上'];
            $score -= 10;
        }

        // 5. 图片ALT诊断
        $imgCount = substr_count($content->content ?? '', '<img');
        $altCount = substr_count($content->content ?? '', 'alt=');
        if ($imgCount > 0 && $altCount < $imgCount) {
            $issues[] = ['type' => 'images', 'severity' => 'medium', 'message' => '图片缺少ALT属性（' . ($imgCount - $altCount) . '/' . $imgCount . '张），影响图片搜索'];
            $score -= 10;
        }

        // 6. 内链诊断
        $internalLinkCount = substr_count($content->content ?? '', '<a href="');
        if ($internalLinkCount < 1 && $contentLen > 500) {
            $issues[] = ['type' => 'links', 'severity' => 'low', 'message' => '长内容缺少内链，建议适当添加相关文章链接'];
            $score -= 5;
        }

        // 7. URL友好度诊断
        $slug = $content->slug ?? '';
        if (!empty($slug) && preg_match('/[^a-z0-9\-_]/', $slug)) {
            $issues[] = ['type' => 'url', 'severity' => 'low', 'message' => 'URL包含非标准字符，建议使用纯英文/数字/连字符'];
            $score -= 5;
        }

        $result = [
            'success' => true,
            'content_id' => $contentId,
            'score' => max(0, $score),
            'issues' => $issues,
            'stats' => [
                'title_length' => $titleLen,
                'desc_length' => $descLen,
                'keyword_count' => $kwCount,
                'content_length' => $contentLen,
                'image_count' => $imgCount,
                'alt_count' => $altCount,
                'internal_links' => $internalLinkCount,
            ],
            'suggestions' => $this->generateSuggestions($issues),
        ];

        Cache::set($cacheKey, $result, 3600);
        return $result;
    }

    /**
     * 批量诊断
     */
    public function batchDiagnose(array $contentIds): array
    {
        $results = [];
        foreach ($contentIds as $id) {
            $results[$id] = $this->diagnose((int) $id);
        }
        return $results;
    }

    /**
     * 获取全站SEO概况
     */
    public function getSiteOverview(): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $total = Content::count();
        $withTitle = Content::where('seo_title', '<>', '')->count();
        $withDesc = Content::where('seo_description', '<>', '')->count();
        $withKeywords = Content::where('seo_keywords', '<>', '')->count();

        // 随机抽样10条进行深度诊断
        $sampleIds = Content::orderRaw('RAND()')->limit(10)->column('id');
        $sampleScores = [];
        foreach ($sampleIds as $sid) {
            $diag = $this->diagnose((int) $sid);
            if ($diag['success'] ?? false) {
                $sampleScores[] = $diag['score'];
            }
        }

        $avgScore = !empty($sampleScores) ? round(array_sum($sampleScores) / count($sampleScores), 1) : 0;

        return [
            'total_content' => $total,
            'with_title' => $withTitle,
            'with_desc' => $withDesc,
            'with_keywords' => $withKeywords,
            'title_rate' => $total > 0 ? round($withTitle / $total * 100, 1) : 0,
            'desc_rate' => $total > 0 ? round($withDesc / $total * 100, 1) : 0,
            'keyword_rate' => $total > 0 ? round($withKeywords / $total * 100, 1) : 0,
            'avg_score' => $avgScore,
            'sample_count' => count($sampleScores),
        ];
    }

    /**
     * 生成修复建议
     */
    private function generateSuggestions(array $issues): array
    {
        $suggestions = [];
        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'title':
                    $suggestions[] = '优化标题：包含核心关键词，控制在10-60字符';
                    break;
                case 'description':
                    $suggestions[] = '完善描述：概括内容核心，控制在50-160字符';
                    break;
                case 'keywords':
                    $suggestions[] = '设置关键词：2-5个与内容高度相关的关键词';
                    break;
                case 'content':
                    $suggestions[] = '扩充内容：增加深度和细节，建议800字符以上';
                    break;
                case 'images':
                    $suggestions[] = '添加图片ALT：描述图片内容，提升图片搜索可见性';
                    break;
                case 'links':
                    $suggestions[] = '增加内链：链接到相关文章，提升页面权重';
                    break;
                case 'url':
                    $suggestions[] = '优化URL：使用英文/数字/连字符，保持简洁';
                    break;
            }
        }
        return array_values(array_unique($suggestions));
    }

    /**
     * 清除诊断缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
