<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;

/**
 * 队列重启命令
 */
class QueueRestartCommand extends Command
{
    protected function configure()
    {
        $this->setName('queue:restart')->setDescription('重启所有队列工作进程');
    }

    protected function execute(Input $input, Output $output)
    {
        Cache::set('queue:restart', time());
        $output->writeln('<info>已发送重启信号</info>');
    }
}
