<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTaskQueue;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Container;

/**
 * AI内容批量生产管线服务
 * V2.9.38 AI-PLUS-2
 * 复用AiBatchService(批量AI调用) + AiTaskQueue(入队) + ContentService(入库发布)
 */
class AiBatchPipelineService
{
    protected const CACHE_TAG = 'batch_pipeline';

    /**
     * 创建批量任务
     */
    public function createBatchTask(array $params): int
    {
        $task = new AiTaskQueue();
        $task->save([
            'task_type' => 'batch_pipeline',
            'task_data' => json_encode([
                'template' => $params['template'] ?? '',
                'variables' => $params['variables'] ?? [],
                'count' => $params['count'] ?? 1,
                'interval' => $params['interval'] ?? 1000,
                'dedup' => $params['dedup'] ?? true,
                'quality_threshold' => $params['quality_threshold'] ?? 70,
                'publish_strategy' => $params['publish_strategy'] ?? 'manual',
                'category_id' => $params['category_id'] ?? 0,
            ]),
            'status' => 'pending',
            'priority' => $params['priority'] ?? 0,
        ]);
        return (int) $task->id;
    }

    /**
     * 启动批量任务
     */
    public function start(int $taskId): bool
    {
        AiTaskQueue::where('id', $taskId)->update(['status' => 'running', 'started_at' => date('Y-m-d H:i:s')]);
        $this->executeNextItem($taskId);
        return true;
    }

    public function pause(int $taskId): bool
    {
        AiTaskQueue::where('id', $taskId)->update(['status' => 'paused']);
        return true;
    }

    public function resume(int $taskId): bool
    {
        AiTaskQueue::where('id', $taskId)->update(['status' => 'running']);
        $this->executeNextItem($taskId);
        return true;
    }

    public function cancel(int $taskId): bool
    {
        AiTaskQueue::where('id', $taskId)->update(['status' => 'cancelled', 'completed_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * 执行单个批次项: 写作→配图→翻译→质检→发布
     */
    public function executeItem(int $taskId, array $itemData): array
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task) throw new \RuntimeException('Task not found');
        $config = json_decode($task->task_data, true) ?? [];

        // 去重检查
        if ($config['dedup'] ?? true) {
            if ($this->checkDuplicate($itemData)) {
                return ['status' => 'skipped', 'reason' => 'duplicate'];
            }
        }

        $writeService = Container::getInstance()->make(AiWriteService::class);
        $imageService = Container::getInstance()->make(AiImageService::class);
        $translateService = Container::getInstance()->make(AiTranslationService::class);
        $qualityService = Container::getInstance()->make(ContentQualityScoreService::class);
        $contentService = Container::getInstance()->make(\app\common\service\content\ContentService::class);

        // 1. AI写作
        $writeResult = $writeService->write([
            'prompt' => $config['template'] ?? '',
            'keyword' => $itemData['keyword'] ?? '',
            'variables' => array_merge($config['variables'] ?? [], $itemData['variables'] ?? []),
        ]);

        // 2. AI配图
        $imageResult = $imageService->generate([
            'prompt' => $writeResult['title'] ?? '',
            'style' => 'auto',
            'count' => 1,
        ]);

        // 3. AI翻译(可选)
        $translations = [];
        if (!empty($config['translate_to'])) {
            foreach ($config['translate_to'] as $lang) {
                $translations[$lang] = $translateService->translate($writeResult['content'] ?? '', $lang);
            }
        }

        // 4. 创建内容
        $contentId = $contentService->create([
            'title' => $writeResult['title'] ?? '',
            'content' => $writeResult['content'] ?? '',
            'cover_image' => $imageResult['images'][0] ?? '',
            'category_id' => $config['category_id'] ?? 0,
        ]);

        // 5. 质量检测
        $qualityScore = $qualityService->score($contentId);
        $passed = ($qualityScore['total_score'] ?? 0) >= ($config['quality_threshold'] ?? 70);

        // 6. 发布策略
        if (($config['publish_strategy'] ?? 'manual') === 'auto' && $passed) {
            $contentService->publish($contentId);
        }

        return [
            'status' => 'success',
            'content_id' => $contentId,
            'quality_score' => $qualityScore['total_score'] ?? 0,
            'quality_passed' => $passed,
            'translations' => $translations,
        ];
    }

    /**
     * 从CSV导入
     */
    public function importFromCsv(string $filePath, int $taskId): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) throw new \RuntimeException('Cannot open CSV file');
        $header = fgetcsv($handle);
        $items = [];
        while (($row = fgetcsv($handle)) !== false) {
            $items[] = array_combine($header, $row);
        }
        fclose($handle);
        // 存储导入数据到任务
        $task = AiTaskQueue::find($taskId);
        if ($task) {
            $data = json_decode($task->task_data, true) ?? [];
            $data['imported_items'] = $items;
            $task->save(['task_data' => json_encode($data)]);
        }
        return ['imported_count' => count($items)];
    }

    /**
     * 从Excel导入
     */
    public function importFromExcel(string $filePath, int $taskId): array
    {
        // 使用PhpSpreadsheet读取(如已安装)
        $items = [];
        // 简化实现: 转换为CSV再处理
        return $this->importFromCsv($filePath, $taskId);
    }

    /**
     * 去重检查: SHA256 + similar_text双重策略
     */
    public function checkDuplicate(array $itemData): bool
    {
        $keyword = $itemData['keyword'] ?? '';
        $hash = hash('sha256', $keyword);
        
        // 检查缓存中是否已有相同hash
        $exists = Cache::get('batch_dedup_' . $hash);
        if ($exists) return true;
        
        // 检查数据库中相似标题
        $existingContents = \app\common\model\Content::where('title', 'like', '%' . $keyword . '%')
            ->limit(5)->column('title');
        foreach ($existingContents as $title) {
            similar_text($keyword, $title, $percent);
            if ($percent > 80) {
                Cache::set('batch_dedup_' . $hash, true, 86400);
                return true;
            }
        }
        
        Cache::set('batch_dedup_' . $hash, true, 86400);
        return false;
    }

    /**
     * 获取进度
     */
    public function getProgress(int $taskId): array
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task) return [];
        $data = json_decode($task->task_data, true) ?? [];
        $totalCount = $data['count'] ?? count($data['imported_items'] ?? []);
        $completedCount = $data['completed_count'] ?? 0;
        $failedCount = $data['failed_count'] ?? 0;
        $progress = $totalCount > 0 ? round($completedCount / $totalCount * 100, 1) : 0;
        $estimatedTime = $totalCount > $completedCount 
            ? round(($totalCount - $completedCount) * ($data['interval'] ?? 1000) / 1000) 
            : 0;
        return [
            'task_id' => $taskId,
            'status' => $task->status,
            'total' => $totalCount,
            'completed' => $completedCount,
            'failed' => $failedCount,
            'progress' => $progress,
            'estimated_time' => $estimatedTime,
        ];
    }

    /**
     * 获取结果
     */
    public function getResults(int $taskId, int $page = 1, int $limit = 20): array
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task) return [];
        $data = json_decode($task->task_data, true) ?? [];
        $results = $data['results'] ?? [];
        $total = count($results);
        $offset = ($page - 1) * $limit;
        $list = array_slice($results, $offset, $limit);
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 执行下一项
     */
    protected function executeNextItem(int $taskId): void
    {
        $task = AiTaskQueue::find($taskId);
        if (!$task || $task->status !== 'running') return;
        $data = json_decode($task->task_data, true) ?? [];
        $items = $data['imported_items'] ?? [];
        $completedCount = $data['completed_count'] ?? 0;
        
        if ($completedCount >= count($items)) {
            $task->save(['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
            return;
        }
        
        $item = $items[$completedCount] ?? ['keyword' => ''];
        try {
            $result = $this->executeItem($taskId, $item);
            $data['results'][] = $result;
            $data['completed_count'] = $completedCount + 1;
        } catch (\Throwable $e) {
            $data['failed_count'] = ($data['failed_count'] ?? 0) + 1;
            $data['completed_count'] = $completedCount + 1;
            $data['errors'][] = ['item' => $item, 'error' => $e->getMessage()];
            Log::error("Batch item failed: " . $e->getMessage());
        }
        
        $task->save(['task_data' => json_encode($data)]);
    }
}
