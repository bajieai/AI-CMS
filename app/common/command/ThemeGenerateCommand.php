<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use app\common\model\AiThemeRecord;
use app\common\service\theme\AiThemeGenerateService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * AI主题生成CLI命令 - V3.0 Phase 2
 *
 * 用法:
 *   php think theme:generate           # 单次运行，处理生成中的任务
 *   php think theme:generate --daemon  # 守护模式，持续运行
 *   php think theme:generate --id=123  # 执行指定任务
 *
 * 设计约束:
 *   - 单次运行最长300秒（与AI生成超时一致）
 *   - 每5秒扫描一次生成中任务
 *   - 每个任务最多3次重试
 */
class ThemeGenerateCommand extends Command
{
    protected int $startTime;
    protected int $maxRuntime = 300;
    protected int $scanInterval = 5;
    protected bool $daemon = false;

    protected function configure()
    {
        $this->setName('theme:generate')
            ->setDescription('AI主题生成任务处理（异步执行LLM生成、文件落盘、校验）')
            ->addOption('daemon', 'd', Option::VALUE_NONE, '守护模式，持续运行')
            ->addOption('max-runtime', 'r', Option::VALUE_OPTIONAL, '单次运行最大秒数', 300)
            ->addOption('id', 'i', Option::VALUE_OPTIONAL, '指定任务ID执行', 0);
    }

    protected function execute(Input $input, Output $output)
    {
        $this->daemon = (bool) $input->getOption('daemon');
        $this->maxRuntime = (int) $input->getOption('max-runtime');
        $targetId = (int) $input->getOption('id');
        $this->startTime = time();

        $output->writeln('[' . date('Y-m-d H:i:s') . '] theme:generate 启动');

        // 指定ID模式：只执行一个任务
        if ($targetId > 0) {
            $this->processSingleTask($targetId, $output);
            $output->writeln('[' . date('Y-m-d H:i:s') . '] theme:generate 结束');
            return 0;
        }

        // 批量扫描模式
        do {
            $processed = $this->processGeneratingTasks($output);

            if (!$this->daemon) {
                if ($processed === 0) {
                    $output->writeln('无生成中任务，退出');
                    break;
                }
                if (time() - $this->startTime >= $this->maxRuntime) {
                    $output->writeln("单次运行已达{$this->maxRuntime}秒上限，退出");
                    break;
                }
            }

            if ($processed === 0 || $this->daemon) {
                sleep($this->scanInterval);
            }
        } while ($this->daemon || (time() - $this->startTime < $this->maxRuntime));

        $output->writeln('[' . date('Y-m-d H:i:s') . '] theme:generate 结束');
        return 0;
    }

    /**
     * 处理所有生成中的任务
     */
    protected function processGeneratingTasks(Output $output): int
    {
        $records = AiThemeRecord::getGeneratingRecords(10);
        if (empty($records)) {
            return 0;
        }

        $count = 0;
        foreach ($records as $record) {
            if (time() - $this->startTime >= $this->maxRuntime) {
                $output->writeln('运行时间即将超限，停止处理新任务');
                break;
            }

            $this->processSingleTask((int) $record['id'], $output);
            $count++;
        }

        return $count;
    }

    /**
     * 处理单个任务
     */
    protected function processSingleTask(int $recordId, Output $output): void
    {
        $output->writeln("[Record #{$recordId}] 开始处理...");

        try {
            $service = new AiThemeGenerateService();
            $result = $service->executeTask($recordId);

            if ($result['success']) {
                $output->writeln("[Record #{$recordId}] ✅ {$result['message']}");
                Log::info("[ThemeGenerateCommand] 任务完成: record_id={$recordId}");
            } else {
                $output->writeln("[Record #{$recordId}] ❌ {$result['message']}");
                Log::error("[ThemeGenerateCommand] 任务失败: record_id={$recordId}, msg={$result['message']}");
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $output->writeln("[Record #{$recordId}] 💥 异常: {$msg}");
            Log::error("[ThemeGenerateCommand] 任务异常: record_id={$recordId}, error={$msg}");

            // 兜底：标记为失败
            try {
                AiThemeRecord::markFailed($recordId, "命令异常: {$msg}");
            } catch (\Throwable) {
                // 忽略二次异常
            }
        }
    }
}
