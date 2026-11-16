<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use app\common\service\AiService;
use app\common\service\ContentService;
use think\facade\Log;
// S0-T3: 框架 facade 未提供 Container 类（facade 目录仅 14 个），改用全局 app() 解析

/**
 * AI工作流节点处理器
 * V2.9.38 AI-PLUS-1 / V2.9.64 R-1~R-3 断链修复
 *
 * 节点类型→真实Service映射（V2.9.64 校准，原映射中的 AiWriteService / ai\AiTranslationService /
 * AiSeoService 在全库并不存在，节点执行即 ClassNotFoundException）:
 * ai_write→AiService::generate | ai_image→AiService::generateImage | ai_translate→AiService::translate
 * ai_qa→ContentQualityScoreService | ai_seo→ContentService::autoFillSeo / AiService::seoOptimize
 * ai_recommend→AiRecommendService(按策略选方法) | ai_summary→AiService::summarize / AiSummaryService::generate
 * condition→内置 | publish→ContentService(状态置为已发布)
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
        // V2.9.64 R-1: 原 AiWriteService 在全库不存在，改走统一 AiService（配额/计费/调用日志同源）
        $keyword = (string) ($config['keyword'] ?? ($context['title']['title'] ?? ''));
        $prompt  = (string) ($config['prompt'] ?? '');
        if ($prompt === '') {
            $length = max(200, (int) ($config['length'] ?? 1000));
            $prompt = "请围绕「{$keyword}」撰写一篇约 {$length} 字的文章，直接输出正文，不要输出解释。";
        }

        $result  = (new AiService())->generate($prompt, 'continue', [
            'style' => (string) ($config['style'] ?? 'professional'),
        ]);
        $content = (string) ($result['content'] ?? '');

        return [
            'output' => [
                'title'   => $keyword !== '' ? $keyword : mb_substr($content, 0, 30),
                'content' => $content,
            ],
            'ai_calls' => 1,
            'ai_cost' => 0.01,
        ];
    }

    protected function handleAiImage(array $config, array $targetIds, array $context): array
    {
        // V2.9.64 R-3: AiImageService::generate(int $contentId, string $style, int $count) 只接受内容 ID，
        // 本节点是按提示词出图，改走 AiService::generateImage(string $prompt, array $options)
        $prompt = (string) ($config['prompt'] ?? ($config['keyword'] ?? ($context['content']['title'] ?? '')));
        $count  = max(1, (int) ($config['count'] ?? 1));
        $style  = (string) ($config['style'] ?? 'natural');
        if ($style === 'auto') {
            $style = 'natural';
        }

        $images = [];
        $ai     = new AiService();
        for ($i = 0; $i < $count; $i++) {
            $one = $ai->generateImage($prompt, ['style' => $style]);
            if (!empty($one['url'])) {
                $images[] = $one['url'];
            }
        }

        return [
            'output' => ['images' => $images],
            'ai_calls' => $count,
            'ai_cost' => 0.02 * $count,
        ];
    }

    protected function handleAiTranslate(array $config, array $targetIds, array $context): array
    {
        // V2.9.64 R-1: 原 ai\AiTranslationService 不存在；AiTranslateService::translateField 返回数组、
        // 语义为「按字段翻译」，节点要的是纯文本翻译，改走 AiService::translate(string,string,string): string
        $text        = (string) ($config['text'] ?? ($context['content']['content'] ?? ''));
        $targetLangs = (array) ($config['target_langs'] ?? ['en']);

        if ($text === '') {
            throw new \RuntimeException('ai_translate 节点缺少待翻译文本（config.text 或上游 content）');
        }

        $ai      = new AiService();
        $results = [];
        foreach ($targetLangs as $lang) {
            $results[(string) $lang] = $ai->translate($text, 'zh', (string) $lang);
        }

        return [
            'output' => ['translations' => $results],
            'ai_calls' => count($targetLangs),
            'ai_cost' => 0.01 * count($targetLangs),
        ];
    }

    protected function handleAiQa(array $config, array $targetIds, array $context): array
    {
        $service = app(\app\common\service\ai\ContentQualityScoreService::class);
        $threshold = $config['threshold'] ?? 70;
        
        $scores = [];
        foreach ($targetIds as $contentId) {
            $score = $service->score((int)$contentId);
            $scores[$contentId] = $score;
        }
        
        // V2.9.64 R-6: score() 实际返回 ['success','content_id','scores'=>['total'=>..],'suggestions']，
        // 原按 total_score 读取导致得分恒为 0、passed 永远为 false
        $avgScore = 0;
        if (!empty($scores)) {
            $totalScore = 0;
            foreach ($scores as $s) {
                $totalScore += (int) ($s['scores']['total'] ?? ($s['total_score'] ?? 0));
            }
            $avgScore = (int) round($totalScore / count($scores));
        }
        
        return [
            'output' => ['scores' => $scores, 'avg_score' => $avgScore, 'passed' => $avgScore >= $threshold],
            'ai_calls' => count($targetIds),
            'ai_cost' => 0,
        ];
    }

    protected function handleAiSeo(array $config, array $targetIds, array $context): array
    {
        // V2.9.64 R-1/R-3: 原 AiSeoService::optimize(int, array) 不存在。
        // auto_fix=true → ContentService::autoFillSeo() 落库；false → AiService::seoOptimize() 只出建议不写库
        $autoFix        = (bool) ($config['auto_fix'] ?? false);
        $contentService = app(ContentService::class);
        $ai             = new AiService();
        $results        = [];

        foreach ($targetIds as $contentId) {
            $id = (int) $contentId;
            if ($autoFix) {
                $results[$id] = $contentService->autoFillSeo($id);
                continue;
            }
            $content = Content::find($id);
            $results[$id] = $content
                ? $ai->seoOptimize((string) $content->content, [])
                : ['success' => false, 'message' => '内容不存在'];
        }

        return [
            'output' => ['seo_results' => $results],
            'ai_calls' => count($targetIds),
            'ai_cost' => 0.01 * count($targetIds),
        ];
    }

    protected function handleAiRecommend(array $config, array $targetIds, array $context): array
    {
        // V2.9.64 R-3: AiRecommendService 无 recommend(array)，按策略映射到真实方法
        $service   = app(\app\common\service\ai\AiRecommendService::class);
        $strategy  = (string) ($config['strategy'] ?? 'content');
        $count     = (int) ($config['count'] ?? 10);
        $contentId = (int) ($config['content_id'] ?? ($targetIds[0] ?? 0));

        $recommendations = match ($strategy) {
            'hot'    => $service->recommendHot($count),
            'latest' => $service->recommendLatest($count),
            'hybrid' => $service->recommendHybrid(0, 'home'),
            default  => $contentId > 0
                ? $service->recommendByContent($contentId, $count)
                : $service->recommendHot($count),
        };

        return [
            'output' => ['recommendations' => $recommendations],
            'ai_calls' => 1,
            'ai_cost' => 0,
        ];
    }

    protected function handleAiSummary(array $config, array $targetIds, array $context): array
    {
        // V2.9.64 R-3: AiSummaryService::summarize(string, array) 不存在。
        // 有文本 → AiService::summarize(string, int): string；否则按目标内容 ID → AiSummaryService::generate(int, string)
        $maxLength = max(50, (int) ($config['max_length'] ?? 200));
        $text      = (string) ($config['text'] ?? ($context['content']['content'] ?? ''));

        if ($text !== '') {
            $summary = (new AiService())->summarize($text, $maxLength);
        } elseif (!empty($targetIds)) {
            $result  = app(\app\common\service\ai\AiSummaryService::class)->generate((int) $targetIds[0], 'medium');
            $summary = (string) ($result['summary'] ?? '');
        } else {
            throw new \RuntimeException('ai_summary 节点缺少 text 或目标内容');
        }

        return [
            'output' => ['summary' => $summary],
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
        // V2.9.64 R-3: 原引用 \app\common\service\content\ContentService（命名空间不存在）且无 publish() 方法。
        // 内容状态：0 草稿 / 1 待审 / 2 已发布，发布即置 2
        $published = 0;
        $failed    = [];

        foreach ($targetIds as $contentId) {
            $id = (int) $contentId;
            try {
                $content = Content::find($id);
                if (!$content) {
                    $failed[$id] = '内容不存在';
                    continue;
                }
                $content->status = 2;
                $content->save();
                $published++;
            } catch (\Throwable $e) {
                $failed[$id] = $e->getMessage();
                Log::error("Publish failed for content {$id}: " . $e->getMessage());
            }
        }

        return [
            'output' => ['published_count' => $published, 'failed' => $failed],
            'ai_calls' => 0,
            'ai_cost' => 0,
        ];
    }
}
