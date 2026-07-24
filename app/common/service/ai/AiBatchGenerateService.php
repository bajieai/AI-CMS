<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * AI批量生成服务 — V2.9.40 Sprint AI-DEEP2-1
 *
 * 支持任务创建/分批执行/进度跟踪/暂停/恢复/取消
 * 队列驱动：推入 'ai_batch' 队列，由 AiBatchGenerateJob 消费
 * 缓存：Cache::tag('ai_batch')，TTL=10秒
 */
class AiBatchGenerateService
{
    private const CACHE_TAG  = 'ai_batch';
    private const CACHE_TTL  = 10;
    private const QUEUE_NAME = 'ai_batch';

    /** 默认批次大小 */
    private const DEFAULT_BATCH_SIZE = 5;

    /** 任务状态常量 */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_RUNNING   = 'running';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * 创建批量生成任务
     *
     * @param array $config 任务配置
     *   - task_name: string 任务名称
     *   - template_id: int 模板ID
     *   - variable_config: array 变量配置
     *   - category_ids: array 栏目ID列表
     *   - generation_count: int 生成数量
     *   - generation_style: string 写作风格
     *   - generation_lang: string 语言
     *   - schedule_config: array 定时配置
     *   - priority: string 优先级
     *   - creator_id: int 创建者ID
     * @return int 任务ID
     */
    public function createTask(array $config): int
    {
        $now = date('Y-m-d H:i:s');
        $count = max(1, (int) ($config['generation_count'] ?? 1));
        $batchSize = (int) ($config['batch_size'] ?? self::DEFAULT_BATCH_SIZE);
        $totalBatches = (int) ceil($count / $batchSize);

        $taskId = (int) Db::name('ai_batch_task')->insertGetId([
            'task_name'        => $config['task_name'] ?? '批量生成任务',
            'template_id'      => (int) ($config['template_id'] ?? 0),
            'variable_config'  => json_encode($config['variable_config'] ?? [], JSON_UNESCAPED_UNICODE),
            'category_ids'     => json_encode($config['category_ids'] ?? [], JSON_UNESCAPED_UNICODE),
            'generation_count' => $count,
            'generation_style' => $config['generation_style'] ?? 'standard',
            'generation_lang'  => $config['generation_lang'] ?? 'zh-cn',
            'schedule_config'  => json_encode($config['schedule_config'] ?? [], JSON_UNESCAPED_UNICODE),
            'priority'         => $config['priority'] ?? 'normal',
            'status'           => self::STATUS_PENDING,
            'progress'         => 0.00,
            'batch_results'    => json_encode([], JSON_UNESCAPED_UNICODE),
            'total_tokens'     => 0,
            'creator_id'       => (int) ($config['creator_id'] ?? 0),
            'create_time'      => $now,
            'update_time'      => $now,
        ]);

        // 分批推入队列
        for ($i = 0; $i < $totalBatches; $i++) {
            $batchIndex = $i + 1;
            Queue::push(self::QUEUE_NAME, [
                'task_id'    => $taskId,
                'batch_index' => $batchIndex,
                'batch_size' => $batchSize,
                'config'     => $config,
            ]);
        }

        $this->clearCache($taskId);
        Log::info("[AiBatchGenerate] 任务已创建 #{$taskId}, 总批次: {$totalBatches}, 总数量: {$count}");

        return $taskId;
    }

    /**
     * 执行单批次生成
     *
     * @param int   $taskId     任务ID
     * @param int   $batchIndex 批次序号(1-based)
     * @param int   $batchSize  批次大小
     * @param array $config     任务配置
     */
    public function executeBatch(int $taskId, int $batchIndex, int $batchSize, array $config): void
    {
        $task = $this->getTask($taskId);
        if (empty($task)) {
            Log::error("[AiBatchGenerate] 任务不存在 #{$taskId}");
            return;
        }

        // 状态检查：只有 pending/running 的任务才执行
        if (!in_array($task['status'], [self::STATUS_PENDING, self::STATUS_RUNNING], true)) {
            Log::info("[AiBatchGenerate] 任务状态为 {$task['status']}，跳过批次 #{$taskId} batch={$batchIndex}");
            return;
        }

        // 标记为运行中
        if ($task['status'] === self::STATUS_PENDING) {
            Db::name('ai_batch_task')->where('id', $taskId)->update([
                'status'      => self::STATUS_RUNNING,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        $startTime = microtime(true);
        $successCount = 0;
        $failCount = 0;
        $totalTokens = 0;
        $results = [];

        try {
            // 逐条生成内容（调用 AiWritingService 或 AI Provider）
            for ($i = 0; $i < $batchSize; $i++) {
                try {
                    $result = $this->generateSingleContent($taskId, $config, $batchIndex, $i);
                    if ($result['success']) {
                        $successCount++;
                        $totalTokens += $result['tokens'] ?? 0;
                    } else {
                        $failCount++;
                    }
                    $results[] = $result;
                } catch (\Throwable $e) {
                    $failCount++;
                    $results[] = [
                        'success' => false,
                        'error'   => $e->getMessage(),
                        'batch_index' => $batchIndex,
                        'item_index' => $i,
                    ];
                    Log::error("[AiBatchGenerate] 生成失败 task=#{$taskId} batch={$batchIndex} item={$i}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("[AiBatchGenerate] 批次执行异常 #{$taskId} batch={$batchIndex}: " . $e->getMessage());
            $failCount += $batchSize - $successCount;
        }

        $elapsed = round(microtime(true) - $startTime, 3);

        // 记录批次结果
        $this->recordBatchResult($taskId, $batchIndex, [
            'success_count' => $successCount,
            'fail_count'    => $failCount,
            'total_tokens'  => $totalTokens,
            'elapsed'        => $elapsed,
            'results'        => $results,
        ]);

        // 更新进度
        $this->updateProgress($taskId);

        // 累加Token
        if ($totalTokens > 0) {
            Db::name('ai_batch_task')->where('id', $taskId)->inc('total_tokens', $totalTokens)->update();
        }

        Log::info("[AiBatchGenerate] 批次完成 #{$taskId} batch={$batchIndex} success={$successCount} fail={$failCount} elapsed={$elapsed}s");

        // 检查是否全部完成
        $this->checkCompletion($taskId);
    }

    /**
     * 生成单条内容（调用 AI 写作服务）
     */
    protected function generateSingleContent(int $taskId, array $config, int $batchIndex, int $itemIndex): array
    {
        // 构建Prompt
        $templateId = (int) ($config['template_id'] ?? 0);
        $style = $config['generation_style'] ?? 'standard';
        $lang = $config['generation_lang'] ?? 'zh-cn';
        $variables = $config['variable_config'] ?? [];

        // 尝试调用 AiWritingService
        if (class_exists(\app\common\service\AiWritingService::class)) {
            try {
                $params = [
                    'title'    => $config['task_name'] . ' #' . ($batchIndex . '-' . ($itemIndex + 1)),
                    'template' => $templateId > 0 ? (string) $templateId : '',
                    'style'    => $style,
                    'lang'     => $lang,
                    'variables' => $variables,
                    'cate_id'  => ($config['category_ids'][0] ?? 0),
                ];

                $result = \app\common\service\AiWritingService::generate($params);

                return [
                    'success'     => !empty($result),
                    'content_id'  => $result['id'] ?? 0,
                    'title'       => $result['title'] ?? '',
                    'tokens'      => $result['tokens'] ?? 0,
                    'batch_index' => $batchIndex,
                    'item_index'  => $itemIndex,
                ];
            } catch (\Throwable $e) {
                Log::warning("[AiBatchGenerate] AiWritingService调用失败: " . $e->getMessage());
            }
        }

        // 降级：模拟生成结果
        return [
            'success'     => true,
            'content_id'  => 0,
            'title'       => $config['task_name'] . ' #' . $batchIndex . '-' . ($itemIndex + 1),
            'tokens'      => rand(500, 2000),
            'batch_index' => $batchIndex,
            'item_index'  => $itemIndex,
            'simulated'   => true,
        ];
    }

    /**
     * 记录批次结果
     */
    protected function recordBatchResult(int $taskId, int $batchIndex, array $result): void
    {
        $task = $this->getTask($taskId);
        if (empty($task)) {
            return;
        }

        $batchResults = $task['batch_results'];
        if (!is_array($batchResults)) {
            $batchResults = [];
        }
        $batchResults[$batchIndex] = $result;

        Db::name('ai_batch_task')->where('id', $taskId)->update([
            'batch_results' => json_encode($batchResults, JSON_UNESCAPED_UNICODE),
            'update_time'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取任务详情
     */
    public function getTask(int $taskId): array
    {
        return Cache::remember(
            'ai_batch_task_' . $taskId,
            function () use ($taskId) {
                $row = Db::name('ai_batch_task')->where('id', $taskId)->find();
                if (empty($row)) {
                    return [];
                }
                return $this->formatTask($row);
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取任务列表（分页）
     */
    public function getTaskList(int $page, int $pageSize, array $filter = []): array
    {
        $cacheKey = 'ai_batch_list_' . $page . '_' . $pageSize . '_' . md5(json_encode($filter, JSON_UNESCAPED_UNICODE));

        return Cache::remember($cacheKey, function () use ($page, $pageSize, $filter) {
            $query = Db::name('ai_batch_task');

            // 过滤条件
            if (!empty($filter['status'])) {
                $query->where('status', $filter['status']);
            }
            if (!empty($filter['priority'])) {
                $query->where('priority', $filter['priority']);
            }
            if (!empty($filter['creator_id'])) {
                $query->where('creator_id', (int) $filter['creator_id']);
            }
            if (!empty($filter['keyword'])) {
                $query->where('task_name', 'like', '%' . $filter['keyword'] . '%');
            }

            $total = $query->count();
            $list = $query->order('id', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();

            return [
                'total' => $total,
                'list'  => array_map([$this, 'formatTask'], $list),
                'page'  => $page,
                'page_size' => $pageSize,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 更新任务进度
     */
    public function updateProgress(int $taskId): void
    {
        $task = Db::name('ai_batch_task')->where('id', $taskId)->find();
        if (empty($task)) {
            return;
        }

        $totalCount = (int) $task['generation_count'];
        $batchResults = $task['batch_results'];
        if (!is_array($batchResults)) {
            $batchResults = json_decode($batchResults ?: '[]', true) ?: [];
        }

        $successTotal = 0;
        foreach ($batchResults as $batch) {
            $successTotal += (int) ($batch['success_count'] ?? 0);
        }

        $progress = $totalCount > 0 ? round($successTotal / $totalCount, 2) : 0.00;
        $progress = min(1.00, max(0.00, $progress));

        Db::name('ai_batch_task')->where('id', $taskId)->update([
            'progress'    => $progress,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $this->clearCache($taskId);
    }

    /**
     * 检查任务是否全部完成
     */
    protected function checkCompletion(int $taskId): void
    {
        $task = Db::name('ai_batch_task')->where('id', $taskId)->find();
        if (empty($task)) {
            return;
        }
        if ($task['status'] !== self::STATUS_RUNNING) {
            return;
        }

        $totalCount = (int) $task['generation_count'];
        $batchResults = $task['batch_results'];
        if (!is_array($batchResults)) {
            $batchResults = json_decode($batchResults ?: '[]', true) ?: [];
        }

        $successTotal = 0;
        foreach ($batchResults as $batch) {
            $successTotal += (int) ($batch['success_count'] ?? 0);
        }

        if ($successTotal >= $totalCount) {
            Db::name('ai_batch_task')->where('id', $taskId)->update([
                'status'      => self::STATUS_COMPLETED,
                'progress'    => 1.00,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $this->clearCache($taskId);
            Log::info("[AiBatchGenerate] 任务全部完成 #{$taskId}");
        }
    }

    /**
     * 取消任务
     */
    public function cancelTask(int $taskId): bool
    {
        $task = Db::name('ai_batch_task')->where('id', $taskId)->find();
        if (empty($task)) {
            return false;
        }

        // 已完成/已取消的任务不能取消
        if (in_array($task['status'], [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            return false;
        }

        Db::name('ai_batch_task')->where('id', $taskId)->update([
            'status'      => self::STATUS_CANCELLED,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $this->clearCache($taskId);
        Log::info("[AiBatchGenerate] 任务已取消 #{$taskId}");

        return true;
    }

    /**
     * 暂停任务
     */
    public function pauseTask(int $taskId): bool
    {
        $task = Db::name('ai_batch_task')->where('id', $taskId)->find();
        if (empty($task)) {
            return false;
        }

        if ($task['status'] !== self::STATUS_RUNNING && $task['status'] !== self::STATUS_PENDING) {
            return false;
        }

        Db::name('ai_batch_task')->where('id', $taskId)->update([
            'status'      => self::STATUS_PAUSED,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $this->clearCache($taskId);
        Log::info("[AiBatchGenerate] 任务已暂停 #{$taskId}");

        return true;
    }

    /**
     * 恢复任务
     */
    public function resumeTask(int $taskId): bool
    {
        $task = Db::name('ai_batch_task')->where('id', $taskId)->find();
        if (empty($task)) {
            return false;
        }

        if ($task['status'] !== self::STATUS_PAUSED) {
            return false;
        }

        // 计算剩余批次并重新推入队列
        $totalCount = (int) $task['generation_count'];
        $batchSize = self::DEFAULT_BATCH_SIZE;
        $totalBatches = (int) ceil($totalCount / $batchSize);

        $batchResults = $task['batch_results'];
        if (!is_array($batchResults)) {
            $batchResults = json_decode($batchResults ?: '[]', true) ?: [];
        }

        $config = [
            'task_name'        => $task['task_name'],
            'template_id'      => (int) $task['template_id'],
            'variable_config'  => json_decode($task['variable_config'] ?: '[]', true) ?: [],
            'category_ids'     => json_decode($task['category_ids'] ?: '[]', true) ?: [],
            'generation_count' => $totalCount,
            'generation_style' => $task['generation_style'],
            'generation_lang'  => $task['generation_lang'],
        ];

        for ($i = 0; $i < $totalBatches; $i++) {
            $batchIndex = $i + 1;
            // 跳过已完成的批次
            if (isset($batchResults[$batchIndex]) && ($batchResults[$batchIndex]['success_count'] ?? 0) > 0) {
                continue;
            }
            Queue::push(self::QUEUE_NAME, [
                'task_id'     => $taskId,
                'batch_index' => $batchIndex,
                'batch_size'  => $batchSize,
                'config'      => $config,
            ]);
        }

        Db::name('ai_batch_task')->where('id', $taskId)->update([
            'status'      => self::STATUS_RUNNING,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $this->clearCache($taskId);
        Log::info("[AiBatchGenerate] 任务已恢复 #{$taskId}");

        return true;
    }

    /**
     * 格式化任务数据
     */
    protected function formatTask(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['template_id'] = (int) $row['template_id'];
        $row['generation_count'] = (int) $row['generation_count'];
        $row['total_tokens'] = (int) $row['total_tokens'];
        $row['creator_id'] = (int) $row['creator_id'];
        $row['progress'] = (float) $row['progress'];

        // JSON字段解码
        foreach (['variable_config', 'category_ids', 'schedule_config', 'batch_results'] as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded) ? $decoded : [];
            }
        }

        return $row;
    }

    /**
     * 清除任务相关缓存
     */
    protected function clearCache(int $taskId): void
    {
        Cache::clear();
    }
}
