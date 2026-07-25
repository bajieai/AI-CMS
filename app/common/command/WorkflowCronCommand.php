<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\AiWorkflow;
use app\common\service\ai\AiWorkflowService;

/**
 * 工作流定时执行命令
 * V2.9.38 AI-PLUS-1
 */
class WorkflowCronCommand extends Command
{
    protected function configure()
    {
        $this->setName('workflow:cron')->setDescription('执行定时触发的工作流');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始执行定时工作流...</info>');
        
        $service = new AiWorkflowService();
        $service->registerTriggers();
        
        $scheduled = AiWorkflow::where('trigger_type', 'scheduled')
            ->where('is_active', 1)
            ->select()
            ->toArray();
        
        foreach ($scheduled as $workflow) {
            $service->execute($workflow['id'], [], 'scheduled');
            $output->writeln("  已执行工作流: {$workflow['name']} (ID: {$workflow['id']})");
        }
        
        $output->writeln("<info>执行完成，共处理{count($scheduled)}个工作流</info>");
    }
}
