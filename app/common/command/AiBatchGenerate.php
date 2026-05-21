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

namespace app\common\command;

use app\common\service\AiWritingService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * AI批量生成CLI命令 - V2.5新增
 * 用法: php think ai:batch-generate <task_id>
 */
class AiBatchGenerate extends Command
{
    protected function configure()
    {
        $this->setName('ai:batch-generate')
            ->addArgument('task_id', Argument::REQUIRED, '批量任务ID')
            ->setDescription('执行AI批量生成任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $taskId = (int) $input->getArgument('task_id');

        try {
            $result = AiWritingService::executeBatchTask($taskId);
            $task = \app\common\model\AiBatchTask::find($taskId);
            if ($result) {
                $output->writeln("<info>批量任务 #{$taskId} 执行完成</info>");
                $output->writeln("成功: {$task->completed}, 总计: {$task->total}");
            } else {
                $output->writeln("<error>批量任务 #{$taskId} 执行失败</error>");
                return 1;
            }
        } catch (\Exception $e) {
            $output->writeln("<error>批量任务 #{$taskId} 执行失败: {$e->getMessage()}</error>");
            return 1;
        }

        return 0;
    }
}
