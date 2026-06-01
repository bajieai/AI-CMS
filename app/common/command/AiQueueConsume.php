<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\command;

use app\common\service\ai\AiImageGenerateService;
use app\common\service\ai\AiTaskQueueService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * AI任务队列消费者 - V2.9.14
 *
 * 用法：
 *   php think ai-queue:consume --type=ai_image_generate --limit=30
 *   php think ai-queue:consume --type=batch_seo_optimize --limit=10
 *
 * Cron配置（每分钟执行，方案D）：
 *   * * * * * cd /path && php think ai-queue:consume --type=ai_image_generate --limit=30
 *   * * * * * cd /path && php think ai-queue:consume --type=batch_seo_optimize --limit=10
 */
class AiQueueConsume extends Command
{
    protected function configure()
    {
        $this->setName('ai-queue:consume')
            ->setDescription('AI任务队列消费者（Cron模式）')
            ->addOption('type', 't', Option::VALUE_REQUIRED, '任务类型(ai_image_generate/batch_seo_optimize)', '')
            ->addOption('limit', 'l', Option::VALUE_REQUIRED, '每次处理数量', '30')
            ->addOption('loop', null, Option::VALUE_NONE, '命令内循环模式（开发调试用）')
            ->addOption('interval', 'i', Option::VALUE_REQUIRED, '循环间隔秒数（配合--loop）', '5');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');
        $limit = (int) $input->getOption('limit');
        $loop = $input->getOption('loop');
        $interval = (int) $input->getOption('interval');

        if (empty($type)) {
            $output->error('请指定任务类型：--type=ai_image_generate 或 --type=batch_seo_optimize');
            return 1;
        }

        $queueService = new AiTaskQueueService();

        // 循环模式（开发调试）
        if ($loop) {
            $output->info("[AI-Queue] 循环模式启动：type={$type}, interval={$interval}s");
            while (true) {
                $count = $this->processBatch($queueService, $type, $limit, $output);
                if ($count === 0) {
                    $output->info("[AI-Queue] 暂无任务，等待 {$interval}s...");
                }
                sleep($interval);
            }
        }

        // 标准模式（Cron调用，单次执行）
        $output->info("[AI-Queue] 消费开始：type={$type}, limit={$limit}");
        $count = $this->processBatch($queueService, $type, $limit, $output);
        $output->info("[AI-Queue] 消费完成：处理 {$count} 个任务");

        return 0;
    }

    /**
     * 处理一批任务
     */
    protected function processBatch(AiTaskQueueService $queueService, string $type, int $limit, Output $output): int
    {
        $tasks = $queueService->consume($type, $limit);
        if (empty($tasks)) {
            return 0;
        }

        foreach ($tasks as $task) {
            try {
                $this->processTask($task, $output);
            } catch (\Throwable $e) {
                Log::error("[AiQueueConsume] 任务处理异常 task_id={$task['id']}: " . $e->getMessage());
                $queueService->fail((int) $task['id'], '消费者异常: ' . $e->getMessage());
            }
        }

        return count($tasks);
    }

    /**
     * 处理单个任务
     */
    protected function processTask(array $task, Output $output): void
    {
        $taskId = (int) $task['id'];
        $type = $task['task_type'];
        $payload = $task['payload'] ?? [];

        $output->info("[AI-Queue] 处理任务 #{$taskId} [{$type}]");

        switch ($type) {
            case 'ai_image_generate':
                $this->processImageGenerate($taskId, $payload, $output);
                break;

            case 'batch_seo_optimize':
            case 'single_seo_optimize':
                $this->processSeoOptimize($taskId, $payload, $output);
                break;

            case 'content_translate':
                $this->processContentTranslate($taskId, $payload, $output);
                break;

            default:
                (new AiTaskQueueService())->fail($taskId, '未知任务类型: ' . $type);
        }
    }

    /**
     * 处理配图生成任务
     */
    protected function processImageGenerate(int $taskId, array $payload, Output $output): void
    {
        $queueService = new AiTaskQueueService();
        $imageService = new AiImageGenerateService();

        $contentId = $payload['content_id'] ?? 0;
        $index = $payload['index'] ?? 0; // 第几张配图 0/1/2

        if (!$contentId) {
            $queueService->fail($taskId, '缺少content_id');
            return;
        }

        $output->info("[AI-Queue] 配图生成: content_id={$contentId}, index={$index}");

        // 调用实际生成逻辑
        $result = $imageService->consumerProcess($contentId, $payload);

        if ($result['success']) {
            $queueService->complete($taskId, $result);
            $output->info("[AI-Queue] 配图完成: task_id={$taskId}, url={$result['url']}");
        } else {
            $queueService->fail($taskId, $result['message'] ?? '生成失败');
            $output->warning("[AI-Queue] 配图失败: task_id={$taskId}, " . ($result['message'] ?? ''));
        }
    }

    /**
     * 处理SEO优化任务
     */
    protected function processSeoOptimize(int $taskId, array $payload, Output $output): void
    {
        $queueService = new AiTaskQueueService();
        $contentService = new \app\common\service\ContentService();

        $contentId = $payload['content_id'] ?? 0;
        $bizKey = $payload['biz_key'] ?? '';

        if (!$contentId) {
            $queueService->fail($taskId, '缺少content_id');
            return;
        }

        $output->info("[AI-Queue] SEO优化: content_id={$contentId}");

        // 检查暂停标志
        if (!empty($bizKey)) {
            $cacheKey = 'batch_seo_progress_' . $bizKey;
            $progress = \think\facade\Cache::get($cacheKey);
            if (!empty($progress['paused'])) {
                $output->info("[AI-Queue] 任务暂停: biz_key={$bizKey}");
                $queueService->pause($taskId);
                return;
            }
        }

        $result = $contentService->autoFillSeo($contentId);

        if ($result['success']) {
            $queueService->complete($taskId, $result);
            $output->info("[AI-Queue] SEO完成: task_id={$taskId}");
        } else {
            $queueService->fail($taskId, $result['message'] ?? 'SEO优化失败');
            $output->warning("[AI-Queue] SEO失败: task_id={$taskId}");
        }
    }

    /**
     * V2.9.15: 处理内容翻译任务
     */
    protected function processContentTranslate(int $taskId, array $payload, Output $output): void
    {
        $queueService = new AiTaskQueueService();
        $translateService = new \app\common\service\ai\AiTranslateService();

        $contentId = $payload['content_id'] ?? 0;
        $targetLang = $payload['target_lang'] ?? 'en';

        if (!$contentId) {
            $queueService->fail($taskId, '缺少content_id');
            return;
        }

        $output->info("[AI-Queue] 内容翻译: content_id={$contentId}, lang={$targetLang}");

        $result = $translateService->translateContent($contentId, $targetLang);

        if ($result['success']) {
            $queueService->complete($taskId, ['content_id' => $contentId, 'lang' => $targetLang]);
            $output->info("[AI-Queue] 翻译完成: task_id={$taskId}, content_id={$contentId}, lang={$targetLang}");
        } else {
            $queueService->fail($taskId, $result['message'] ?? '翻译失败');
            $output->warning("[AI-Queue] 翻译失败: task_id={$taskId}, " . ($result['message'] ?? ''));
        }
    }
}
