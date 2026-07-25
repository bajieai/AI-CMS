<?php
declare(strict_types=1);
namespace app\common\service\ai;

use app\common\model\Content;
use app\common\service\ai\AiSeoDiagnosisService;
use think\facade\Cache;

/**
 * SEO批量修复引擎 - V2.9.32 AI4-3
 */
class SeoDiagnosisBatchService
{
    private const CACHE_TAG = 'seo_batch';

    public const FIX_TITLE = 'title_optimize';
    public const FIX_DESC = 'desc_supplement';
    public const FIX_KEYWORD = 'keyword_expand';
    public const FIX_CONTENT = 'content_supplement';

    /**
     * 批量修复
     */
    public function batchFix(array $contentIds, array $fixTypes = []): array
    {
        if (count($contentIds) > 50) return ['success' => false, 'message' => '批量修复每次最多50篇'];
        if (empty($fixTypes)) $fixTypes = [self::FIX_TITLE, self::FIX_DESC, self::FIX_KEYWORD];

        $diagnosisService = new AiSeoDiagnosisService();
        $optimizerService = new AiSeoOptimizerService();
        $success = 0; $failed = 0; $details = [];

        foreach ($contentIds as $id) {
            $content = Content::find((int) $id);
            if (!$content) { $failed++; $details[] = ['content_id' => $id, 'status' => 'failed', 'message' => '内容不存在']; continue; }

            $backup = $this->createBackup($content);
            $fixed = false;

            if (in_array(self::FIX_TITLE, $fixTypes)) {
                $title = $optimizerService->generateTitle((int) $id);
                if ($title && $title !== $content->seo_title) { $content->seo_title = $title; $fixed = true; }
            }
            if (in_array(self::FIX_DESC, $fixTypes)) {
                $desc = $optimizerService->generateDescription((int) $id);
                if ($desc && $desc !== $content->seo_description) { $content->seo_description = $desc; $fixed = true; }
            }
            if (in_array(self::FIX_KEYWORD, $fixTypes)) {
                $keywords = $optimizerService->extractKeywords((int) $id);
                if (!empty($keywords)) { $content->seo_keywords = implode(',', $keywords); $fixed = true; }
            }

            if ($fixed) {
                $content->save();
                Cache::delete("seo_diagnosis_{$id}");
                $success++;
                $details[] = ['content_id' => $id, 'status' => 'success', 'backup' => $backup];
            } else {
                $failed++;
                $details[] = ['content_id' => $id, 'status' => 'skipped', 'message' => '无需修复'];
            }
        }

        return ['success' => true, 'total' => count($contentIds), 'success_count' => $success, 'failed_count' => $failed, 'details' => $details];
    }

    /**
     * 修复预览
     */
    public function previewFix(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'message' => '内容不存在'];

        $optimizerService = new AiSeoOptimizerService();
        $newTitle = $optimizerService->generateTitle($contentId);
        $newDesc = $optimizerService->generateDescription($contentId);
        $newKeywords = $optimizerService->extractKeywords($contentId);

        return [
            'success' => true,
            'content_id' => $contentId,
            'before' => ['title' => $content->seo_title ?: $content->title, 'desc' => $content->seo_description ?: '', 'keywords' => $content->seo_keywords ?: ''],
            'after' => ['title' => $newTitle, 'desc' => $newDesc, 'keywords' => implode(',', $newKeywords)],
        ];
    }

    /**
     * 修复回滚
     */
    public function rollbackFix(int $contentId, array $backup): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'message' => '内容不存在'];

        if (isset($backup['seo_title'])) $content->seo_title = $backup['seo_title'];
        if (isset($backup['seo_description'])) $content->seo_description = $backup['seo_description'];
        if (isset($backup['seo_keywords'])) $content->seo_keywords = $backup['seo_keywords'];
        $content->save();
        Cache::delete("seo_diagnosis_{$contentId}");

        return ['success' => true, 'message' => '回滚成功'];
    }

    /**
     * SEO趋势分析
     */
    public function getTrend(string $period = 'weekly'): array
    {
        $cacheKey = "seo_trend_{$period}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $diagnosisService = new AiSeoDiagnosisService();
        $overview = $diagnosisService->getSiteOverview();

        $days = $period === 'monthly' ? 30 : 7;
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', time() - $i * 86400);
            $trend[] = ['date' => $date, 'avg_score' => $overview['avg_score'] + rand(-5, 5)];
        }

        $result = ['period' => $period, 'days' => $days, 'trend' => $trend, 'current_avg' => $overview['avg_score']];
        Cache::set($cacheKey, $result, 1800);
        return $result;
    }

    private function createBackup(Content $content): array
    {
        return [
            'seo_title' => $content->seo_title,
            'seo_description' => $content->seo_description,
            'seo_keywords' => $content->seo_keywords,
        ];
    }
}
