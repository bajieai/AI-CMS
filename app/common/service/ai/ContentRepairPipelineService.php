<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use app\common\model\ContentQualityScore;
use think\facade\Cache;

/**
 * AI内容修复管线 — V2.9.33 AI5-2
 * 检测→修复→验证闭环，最多修复3次
 */
class ContentRepairPipelineService
{
    private const MAX_REPAIR_CYCLES = 3;
    private const CACHE_TAG = 'content_quality';

    /**
     * 修复单篇内容
     * @param string $mode auto(无人值守)/suggested(建议确认)/batch(批量)
     */
    public function repair(int $contentId, string $mode = 'suggested'): array
    {
        $scoreService = new ContentQualityScoreService();
        $report = ['content_id' => $contentId, 'mode' => $mode, 'cycles' => [], 'final_status' => 'failed'];

        // 初始评分
        $score = $scoreService->score($contentId, 'repair');
        $report['initial_score'] = $score['scores']['total'] ?? 0;

        $cycle = 0;
        while (($score['scores']['total'] ?? 0) < 60 && $cycle < self::MAX_REPAIR_CYCLES) {
            $cycle++;
            $cycleReport = ['cycle' => $cycle, 'before_score' => $score['scores']['total'] ?? 0, 'actions' => []];

            foreach ($score['suggestions'] ?? [] as $suggestion) {
                if (($suggestion['score'] ?? 100) >= 60) continue;
                $action = $this->repairByDimension($contentId, $suggestion['dimension'], $mode);
                $cycleReport['actions'][] = $action;
            }

            // 重新评分
            $score = $scoreService->score($contentId, 'repair');
            $cycleReport['after_score'] = $score['scores']['total'] ?? 0;
            $cycleReport['improved'] = $cycleReport['after_score'] > $cycleReport['before_score'];
            $report['cycles'][] = $cycleReport;
        }

        $finalScore = $score['scores']['total'] ?? 0;
        $report['final_score'] = $finalScore;
        $report['final_status'] = $finalScore >= 60 ? 'auto' : 'needs_manual';
        $report['cycles_count'] = $cycle;

        // 更新修复状态
        $record = ContentQualityScore::where('content_id', $contentId)->find();
        if ($record) {
            $record->repair_count = $cycle;
            $record->last_repair_time = time();
            $record->repair_status = $finalScore >= 60 ? 'auto' : 'needs_manual';
            $record->save();
        }

        Cache::clear();
        return $report;
    }

    /**
     * 批量修复（≤50篇）
     */
    public function batchRepair(array $contentIds): array
    {
        $contentIds = array_slice($contentIds, 0, 50);
        $results = [];
        $successCount = 0;

        foreach ($contentIds as $id) {
            try {
                $report = $this->repair((int) $id, 'batch');
                $results[] = $report;
                if ($report['final_status'] === 'auto') $successCount++;
            } catch (\Throwable $e) {
                $results[] = ['content_id' => $id, 'final_status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return [
            'total' => count($contentIds),
            'success' => $successCount,
            'failed' => count($contentIds) - $successCount,
            'results' => $results,
        ];
    }

    /**
     * 按维度修复
     */
    private function repairByDimension(int $contentId, string $dimension, string $mode): array
    {
        $action = ['dimension' => $dimension, 'action' => '', 'result' => 'skip'];

        try {
            switch ($dimension) {
                case 'completeness':
                    $content = Content::find($contentId);
                    if ($content) {
                        if (empty($content->summary)) {
                            $content->summary = mb_substr(strip_tags($content->content ?? ''), 0, 200);
                            $action['action'] = '补充摘要';
                        }
                        if (empty($content->seo_title)) {
                            $content->seo_title = $content->title;
                            $action['action'] .= ' 补充SEO标题';
                        }
                        $content->save();
                        $action['result'] = 'done';
                    }
                    break;

                case 'readability':
                    $content = Content::find($contentId);
                    if ($content && !empty($content->content)) {
                        $html = $content->content;
                        $html = preg_replace('/(<\/p>)(?!\s*<p)/i', "$1\n\n", $html);
                        $content->content = $html;
                        $content->save();
                        $action = ['dimension' => $dimension, 'action' => '段落结构优化', 'result' => 'done'];
                    }
                    break;

                case 'seo':
                    $seoService = new AiSeoOptimizerService();
                    $seoService->optimizeContent($contentId);
                    $action = ['dimension' => $dimension, 'action' => 'AI SEO优化', 'result' => 'done'];
                    break;

                case 'image_match':
                    if (class_exists(AiImageService::class)) {
                        $imgService = new AiImageService();
                        $imgService->generate($contentId);
                        $action = ['dimension' => $dimension, 'action' => 'AI重新生成配图', 'result' => 'done'];
                    }
                    break;

                case 'tag_accuracy':
                    if (class_exists(AiTagService::class)) {
                        $tagService = new AiTagService();
                        $tags = $tagService->recommend($contentId);
                        if (!empty($tags)) {
                            Content::where('id', $contentId)->update(['tags' => implode(',', array_slice($tags, 0, 5))]);
                            $action = ['dimension' => $dimension, 'action' => 'AI重新推荐标签', 'result' => 'done'];
                        }
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $action['result'] = 'error: ' . $e->getMessage();
        }

        return $action;
    }
}
