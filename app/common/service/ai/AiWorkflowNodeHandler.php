<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Log;
use think\facade\Container;

/**
 * AI工作流节点处理器
 * V2.9.38 AI-PLUS-1
 * 
 * 节点类型→现有Service映射:
 * ai_write→AiWriteService | ai_image→AiImageService | ai_translate→AiTranslationService
 * ai_qa→ContentQualityScoreService | ai_seo→AiSeoService | ai_recommend→AiRecommendService
 * ai_summary→AiSummaryService | condition→内置 | publish→ContentService
 */
class AiWorkflowNodeHandler
{
    /**
     * 执行单个节点
     */
    public function execute(array $node, array $targetIds, array $previousResults = []): array
    {
        $type = $node['type'] ?? 'unknown';
        $config = $node['config'] ?? [];
        $nodeId = $node['id'] ?? '';
        
        // 收集上游节点的输出作为上下文
        $context = $this->collectContext($previousResults);
        
        switch ($type) {
            case 'ai_write':
                return $this->handleAiWrite($config, $targetIds, $context);
            case 'ai_image':
                return $this->handleAiImage($config, $targetIds, $context);
            case 'ai_translate':
                return $this->handleAiTranslate($config, $targetIds, $context);
            case 'ai_qa':
                return $this->handleAiQa($config, $targetIds, $context);
            case 'ai_seo':
                return $this->handleAiSeo($config, $targetIds, $context);
            case 'ai_recommend':
                return $this->handleAiRecommend($config, $targetIds, $context);
            case 'ai_summary':
                return $this->handleAiSummary($config, $targetIds, $context);
            case 'condition':
                return $this->handleCondition($config, $context);
            case 'publish':
                return $this->handlePublish($config, $targetIds, $context);
            default:
                throw new \RuntimeException("Unknown node type: {$type}");
        }
    }

    protected function collectContext(array $previousResults): array
    {
        $context = [];
        foreach ($previousResults as $nodeId => $result) {
            if (($result['status'] ?? '') === 'success' && isset($result['output'])) {
                $context[$nodeId] = $result['output'];
            }
        }
        return $context;
    }

    protected function handleAiWrite(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiWriteService::class);
        $prompt = $config['prompt'] ?? '';
        $keyword = $config['keyword'] ?? ($context['title']['title'] ?? '');
        
        $result = $service->write([
            'prompt' => $prompt,
            'keyword' => $keyword,
            'style' => $config['style'] ?? 'professional',
            'length' => $config['length'] ?? 1000,
        ]);
        
        return [
            'output' => ['title' => $result['title'] ?? '', 'content' => $result['content'] ?? ''],
            'ai_calls' => 1,
            'ai_cost' => $result['cost'] ?? 0.01,
        ];
    }

    protected function handleAiImage(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiImageService::class);
        $keyword = $config['keyword'] ?? ($context['content']['title'] ?? '');
        
        $result = $service->generate([
            'prompt' => $keyword,
            'style' => $config['style'] ?? 'auto',
            'count' => $config['count'] ?? 1,
        ]);
        
        return [
            'output' => ['images' => $result['images'] ?? []],
            'ai_calls' => $config['count'] ?? 1,
            'ai_cost' => ($result['cost'] ?? 0.02) * ($config['count'] ?? 1),
        ];
    }

    protected function handleAiTranslate(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiTranslationService::class);
        $text = $config['text'] ?? ($context['content']['content'] ?? '');
        $targetLangs = $config['target_langs'] ?? ['en'];
        
        $results = [];
        foreach ($targetLangs as $lang) {
            $result = $service->translate($text, $lang);
            $results[$lang] = $result;
        }
        
        return [
            'output' => ['translations' => $results],
            'ai_calls' => count($targetLangs),
            'ai_cost' => 0.01 * count($targetLangs),
        ];
    }

    protected function handleAiQa(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\ContentQualityScoreService::class);
        $threshold = $config['threshold'] ?? 70;
        
        $scores = [];
        foreach ($targetIds as $contentId) {
            $score = $service->score((int)$contentId);
            $scores[$contentId] = $score;
        }
        
        $avgScore = 0;
        if (!empty($scores)) {
            $totalScore = 0;
            foreach ($scores as $s) {
                $totalScore += $s['total_score'] ?? 0;
            }
            $avgScore = round($totalScore / count($scores));
        }
        
        return [
            'output' => ['scores' => $scores, 'avg_score' => $avgScore, 'passed' => $avgScore >= $threshold],
            'ai_calls' => count($targetIds),
            'ai_cost' => 0,
        ];
    }

    protected function handleAiSeo(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiSeoService::class);
        $results = [];
        foreach ($targetIds as $contentId) {
            $result = $service->optimize((int)$contentId, ['auto_fix' => $config['auto_fix'] ?? false]);
            $results[$contentId] = $result;
        }
        return [
            'output' => ['seo_results' => $results],
            'ai_calls' => count($targetIds),
            'ai_cost' => 0.01 * count($targetIds),
        ];
    }

    protected function handleAiRecommend(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiRecommendService::class);
        $strategy = $config['strategy'] ?? 'tfidf';
        $count = $config['count'] ?? 10;
        
        $recommendations = $service->recommend([
            'strategy' => $strategy,
            'count' => $count,
            'content_ids' => $targetIds,
        ]);
        
        return [
            'output' => ['recommendations' => $recommendations],
            'ai_calls' => 1,
            'ai_cost' => 0,
        ];
    }

    protected function handleAiSummary(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\ai\AiSummaryService::class);
        $text = $config['text'] ?? ($context['content']['content'] ?? '');
        $result = $service->summarize($text, ['max_length' => $config['max_length'] ?? 200]);
        return [
            'output' => ['summary' => $result],
            'ai_calls' => 1,
            'ai_cost' => 0.01,
        ];
    }

    protected function handleCondition(array $config, array $context): array
    {
        $expression = $config['expression'] ?? 'true';
        $branch = eval("return {$expression};") ? 'true' : 'false';
        return ['output' => ['branch' => $branch], 'ai_calls' => 0, 'ai_cost' => 0];
    }

    protected function handlePublish(array $config, array $targetIds, array $context): array
    {
        $service = Container::getInstance()->make(\app\common\service\content\ContentService::class);
        $channel = $config['channel'] ?? 'default';
        $published = 0;
        foreach ($targetIds as $contentId) {
            try {
                $service->publish((int)$contentId);
                $published++;
            } catch (\Throwable $e) {
                Log::error("Publish failed for content {$contentId}: " . $e->getMessage());
            }
        }
        return ['output' => ['published_count' => $published], 'ai_calls' => 0, 'ai_cost' => 0];
    }
}
