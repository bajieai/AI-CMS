<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\theme\BatchThemeGenerateService;
use app\common\service\theme\ThemeQualityService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 批量主题生成CLI命令 - Sprint 14
 *
 * 用法:
 *   php think theme:batch                        # 默认：企业类型，生成10套
 *   php think theme:batch enterprise             # 指定行业
 *   php think theme:batch enterprise --count=20  # 指定数量
 *   php think theme:batch enterprise --resume    # 断点续传
 *   php think theme:batch --batch-id=xxx         # 执行已有批次
 *   php think theme:batch --progress             # 查看进度
 */
class ThemeBatchGenerate extends Command
{
    protected BatchThemeGenerateService $batchService;
    protected ThemeQualityService $qualityService;

    protected function configure()
    {
        $this->setName('theme:batch')
            ->setDescription('AI主题批量生成（行业分类×变体描述×进度追踪）')
            ->addArgument('industry', Argument::OPTIONAL, '行业类型(enterprise/ecommerce/blog/portal/education)', 'enterprise')
            ->addOption('count', 'c', Option::VALUE_OPTIONAL, '生成数量', 10)
            ->addOption('resume', 'r', Option::VALUE_NONE, '断点续传模式（跳过已完成任务）')
            ->addOption('batch-id', 'b', Option::VALUE_OPTIONAL, '执行已有批次ID', '')
            ->addOption('progress', 'p', Option::VALUE_NONE, '查看批次进度')
            ->addOption('quality', 'q', Option::VALUE_NONE, '生成完成后执行质量评分');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->batchService   = new BatchThemeGenerateService();
        $this->qualityService = new ThemeQualityService();

        $batchId   = $input->getOption('batch-id');
        $showProgress = (bool) $input->getOption('progress');

        // 查看进度模式
        if ($showProgress && !empty($batchId)) {
            return $this->showProgress($batchId, $output);
        }

        // 执行已有批次
        if (!empty($batchId)) {
            return $this->runExistingBatch($batchId, $input, $output);
        }

        // 创建新批次
        $industry = $input->getArgument('industry');
        $count    = (int) $input->getOption('count');
        $resume   = (bool) $input->getOption('resume');
        $quality  = (bool) $input->getOption('quality');

        $output->writeln('[' . date('Y-m-d H:i:s') . "] 批量生成启动: industry={$industry}, count={$count}");

        // 资源压力监控：记录起始状态
        $startMem = memory_get_usage(true);
        $startTime = time();

        // 1. 创建批量任务
        $createResult = $this->batchService->createBatch(0, $industry, $count);
        if (!$createResult['success']) {
            $output->writeln('❌ 任务创建失败: ' . $createResult['message']);
            return 1;
        }

        $newBatchId = $createResult['batch_id'];
        $output->writeln("✅ 批次创建成功: batch_id={$newBatchId}, tasks=" . count($createResult['tasks']));

        // 2. 执行批量生成
        $executeResult = $this->batchService->executeBatch($newBatchId, $resume);
        $output->writeln($executeResult['message']);

        // 3. 质量评分（如启用）
        if ($quality && $executeResult['processed'] > 0) {
            $this->runQualityCheck($newBatchId, $output);
        }

        // 资源压力监控：记录结束状态
        $endMem = memory_get_usage(true);
        $duration = time() - $startTime;
        $memPeak = memory_get_peak_usage(true);
        $output->writeln("📊 资源消耗: 耗时{$duration}s, 起始内存" . $this->formatBytes($startMem) . ", 峰值内存" . $this->formatBytes($memPeak));
        Log::info("[ThemeBatchGenerate] 批次完成: batch_id={$newBatchId}, duration={$duration}s, mem_peak=" . $this->formatBytes($memPeak));

        return 0;
    }

    /**
     * 执行已有批次
     */
    protected function runExistingBatch(string $batchId, Input $input, Output $output): int
    {
        $resume  = (bool) $input->getOption('resume');
        $quality = (bool) $input->getOption('quality');

        $output->writeln('[' . date('Y-m-d H:i:s') . "] 执行已有批次: batch_id={$batchId}, resume=" . ($resume ? 'yes' : 'no'));

        $executeResult = $this->batchService->executeBatch($batchId, $resume);
        $output->writeln($executeResult['message']);

        if ($quality && $executeResult['processed'] > 0) {
            $this->runQualityCheck($batchId, $output);
        }

        return 0;
    }

    /**
     * 查看批次进度
     */
    protected function showProgress(string $batchId, Output $output): int
    {
        $progress = $this->batchService->getBatchProgress($batchId);

        if (!$progress['exists']) {
            $output->writeln('❌ 批次不存在: ' . $batchId);
            return 1;
        }

        $output->writeln("批次进度: {$batchId}");
        $output->writeln(str_repeat('-', 40));
        $output->writeln("总任务数:   {$progress['total']}");
        $output->writeln("已完成:     {$progress['completed']}");
        $output->writeln("生成中:     {$progress['generating']}");
        $output->writeln("待审核:     {$progress['pending']}");
        $output->writeln("已校验:     {$progress['validated']}");
        $output->writeln("已发布:     {$progress['published']}");
        $output->writeln("失败:       {$progress['failed']}");
        $output->writeln("进度:       {$progress['progress_pct']}%");

        return 0;
    }

    /**
     * 对批次内已完成主题执行质量评分
     */
    protected function runQualityCheck(string $batchId, Output $output): void
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . "] 开始质量评分...");

        $records = \app\common\model\AiThemeRecord::where('options', 'like', '%"batch_id":"' . $batchId . '"%')
            ->whereIn('status', [1, 2, 3]) // PENDING_REVIEW / VALIDATED / PUBLISHED
            ->select();

        $checked = 0;
        foreach ($records as $record) {
            $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $record->theme_name;
            if (!is_dir($themePath)) {
                continue;
            }

            try {
                $options = json_decode($record->options, true) ?: [];
                $industry = $options['industry'] ?? '';
                $result = $this->qualityService->score($themePath, $industry);

                // 更新记录的quality_score
                $record->quality_score = $result['total'];
                $record->quality_detail = $result;
                $record->save();

                $output->writeln("  [{$record->theme_name}] 质量评分: {$result['total']}分");
                $checked++;
            } catch (\Throwable $e) {
                Log::warning("[ThemeBatchGenerate] 质量评分失败: record_id={$record->id}, error=" . $e->getMessage());
            }
        }

        $output->writeln("✅ 质量评分完成: {$checked} 套主题");
    }

    /**
     * 格式化字节大小
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . 'MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . 'B';
    }
}
