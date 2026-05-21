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

namespace app\common\service\theme;

use app\common\model\AiThemeRecord;
use think\facade\Log;

/**
 * 批量主题生成编排服务 - Sprint 14
 *
 * 职责：
 * - 批量创建任务（行业分类 × 变体描述）
 * - 编排调用 AiThemeGenerateService
 * - 进度追踪（batch_id 关联）
 * - 断点续传（跳过已完成状态）
 * - 资源压力监控
 */
class BatchThemeGenerateService
{
    protected AiThemeGenerateService $generateService;

    public function __construct()
    {
        $this->generateService = new AiThemeGenerateService();
    }

    /**
     * 创建批量生成任务
     *
     * @param int    $userId    创建人ID
     * @param string $industry  行业类型（enterprise/ecommerce/blog/portal/education）
     * @param int    $count     生成数量
     * @param array  $options   额外选项
     * @return array ['success'=>bool, 'batch_id'=>string, 'tasks'=>array]
     */
    public function createBatch(int $userId, string $industry, int $count = 10, array $options = []): array
    {
        $batchId = 'batch-' . date('YmdHis') . '-' . substr(uniqid(), -6);
        $industryConfig = $this->getIndustryConfig($industry);

        if (empty($industryConfig)) {
            return ['success' => false, 'batch_id' => '', 'tasks' => [], 'message' => '无效的行业类型: ' . $industry];
        }

        $tasks = [];
        $variations = $this->buildVariations($industryConfig, $count);

        foreach ($variations as $index => $variation) {
            $description = $variation['description'];
            $taskOptions = array_merge($options, [
                'style'       => $variation['style'] ?? '现代简约',
                'color_scheme'=> $variation['color_scheme'] ?? '蓝色系',
                'layout_type' => $variation['layout_type'] ?? '响应式',
                'industry'    => $industry,
                'batch_id'    => $batchId,
                'batch_index' => $index + 1,
            ]);

            try {
                $recordId = $this->generateService->createTask($userId, $description, $taskOptions);
                $tasks[] = [
                    'record_id'   => $recordId,
                    'description' => $description,
                    'status'      => AiThemeRecord::STATUS_GENERATING,
                ];
            } catch (\Throwable $e) {
                Log::error("[BatchThemeGenerate] 创建任务失败: batch_id={$batchId}, index={$index}, error=" . $e->getMessage());
            }
        }

        Log::info("[BatchThemeGenerate] 批量任务创建完成: batch_id={$batchId}, industry={$industry}, count=" . count($tasks));

        return [
            'success'  => count($tasks) > 0,
            'batch_id' => $batchId,
            'tasks'    => $tasks,
            'message'  => "成功创建 " . count($tasks) . " 个生成任务",
        ];
    }

    /**
     * 执行批量生成（支持断点续传）
     *
     * @param string $batchId   批次ID
     * @param bool   $resume    是否断点续传模式
     * @return array ['success'=>bool, 'processed'=>int, 'failed'=>int]
     */
    public function executeBatch(string $batchId, bool $resume = true): array
    {
        $records = AiThemeRecord::where('batch_id', $batchId)
            ->order('id', 'asc')
            ->select();

        if ($records->isEmpty()) {
            return ['success' => false, 'processed' => 0, 'failed' => 0, 'message' => '批次不存在: ' . $batchId];
        }

        $processed = 0;
        $failed    = 0;
        $skipped   = 0;

        foreach ($records as $record) {
            $status = (int) $record->status;

            // 断点续传：跳过已完成状态
            if ($resume && in_array($status, [
                AiThemeRecord::STATUS_PENDING_REVIEW,
                AiThemeRecord::STATUS_VALIDATED,
                AiThemeRecord::STATUS_PUBLISHED,
            ], true)) {
                $skipped++;
                continue;
            }

            // 断点续传：跳过已成功的，但重试失败的
            if ($resume && $status === AiThemeRecord::STATUS_GENERATE_FAILED && (int) $record->retry_count >= 3) {
                $skipped++;
                continue;
            }

            Log::info("[BatchThemeGenerate] 执行任务: record_id={$record->id}, batch_id={$batchId}");

            try {
                $result = $this->generateService->executeTask((int) $record->id);
                if ($result['success']) {
                    $processed++;
                } else {
                    $failed++;
                    Log::warning("[BatchThemeGenerate] 任务失败: record_id={$record->id}, msg={$result['message']}");
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error("[BatchThemeGenerate] 任务异常: record_id={$record->id}, error=" . $e->getMessage());
            }
        }

        Log::info("[BatchThemeGenerate] 批量执行完成: batch_id={$batchId}, processed={$processed}, failed={$failed}, skipped={$skipped}");

        return [
            'success'   => $processed > 0,
            'processed' => $processed,
            'failed'    => $failed,
            'skipped'   => $skipped,
            'message'   => "完成: {$processed} 成功, {$failed} 失败, {$skipped} 跳过",
        ];
    }

    /**
     * 获取批次进度
     *
     * @param string $batchId
     * @return array
     */
    public function getBatchProgress(string $batchId): array
    {
        $records = AiThemeRecord::where('batch_id', $batchId)
            ->order('id', 'asc')
            ->select();

        if ($records->isEmpty()) {
            return ['exists' => false, 'batch_id' => $batchId];
        }

        $total      = $records->count();
        $generating = 0;
        $pending    = 0;
        $validated  = 0;
        $published  = 0;
        $failed     = 0;

        foreach ($records as $record) {
            switch ((int) $record->status) {
                case AiThemeRecord::STATUS_GENERATING:
                    $generating++;
                    break;
                case AiThemeRecord::STATUS_PENDING_REVIEW:
                    $pending++;
                    break;
                case AiThemeRecord::STATUS_VALIDATED:
                    $validated++;
                    break;
                case AiThemeRecord::STATUS_PUBLISHED:
                    $published++;
                    break;
                case AiThemeRecord::STATUS_GENERATE_FAILED:
                case AiThemeRecord::STATUS_VALIDATE_FAILED:
                    $failed++;
                    break;
            }
        }

        $completed = $pending + $validated + $published;

        return [
            'exists'      => true,
            'batch_id'    => $batchId,
            'total'       => $total,
            'completed'   => $completed,
            'generating'  => $generating,
            'pending'     => $pending,
            'validated'   => $validated,
            'published'   => $published,
            'failed'      => $failed,
            'progress_pct'=> $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }

    /**
     * 获取行业配置
     */
    public function getIndustryConfig(string $industry): array
    {
        $categories = config('ai.theme_industry_categories', []);
        return $categories[$industry] ?? [];
    }

    /**
     * 获取所有行业分类
     */
    public function getAllIndustries(): array
    {
        return config('ai.theme_industry_categories', []);
    }

    /**
     * 构建变体描述列表
     */
    protected function buildVariations(array $industryConfig, int $count): array
    {
        $variations = [];
        $styles      = $industryConfig['styles'] ?? ['现代简约'];
        $colors      = $industryConfig['colors'] ?? ['蓝色系'];
        $layouts     = $industryConfig['layouts'] ?? ['响应式'];
        $descriptions = $industryConfig['descriptions'] ?? [''];

        $index = 0;
        foreach ($descriptions as $desc) {
            foreach ($styles as $style) {
                foreach ($colors as $color) {
                    foreach ($layouts as $layout) {
                        if ($index >= $count) {
                            break 4;
                        }
                        $variations[] = [
                            'description'  => $desc . "，风格：{$style}，色系：{$color}，布局：{$layout}",
                            'style'        => $style,
                            'color_scheme' => $color,
                            'layout_type'  => $layout,
                        ];
                        $index++;
                    }
                }
            }
        }

        // 如果组合不够count，用默认描述补充
        while (count($variations) < $count) {
            $variations[] = [
                'description'  => ($industryConfig['name'] ?? '主题') . '模板' . (count($variations) + 1),
                'style'        => $styles[array_rand($styles)],
                'color_scheme' => $colors[array_rand($colors)],
                'layout_type'  => $layouts[array_rand($layouts)],
            ];
        }

        return array_slice($variations, 0, $count);
    }
}
