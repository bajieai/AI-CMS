<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\ai\AiAgentService;

/**
 * 智能体定时执行命令
 * V2.9.38 AI-PLUS-3
 */
class AgentScheduledCommand extends Command
{
    protected function configure()
    {
        $this->setName('agent:scheduled')->setDescription('执行定时智能体任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始执行定时智能体...</info>');
        
        $service = new AiAgentService();
        $results = $service->runScheduled();
        
        $output->writeln("<info>执行完成，共处理{count($results)}个智能体</info>");
    }
}
