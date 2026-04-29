<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\PublishService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 定时发布命令
 */
class PublishSchedule extends Command
{
    protected function configure()
    {
        $this->setName('schedule:publish')
            ->setDescription('执行定时发布任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new PublishService;
        $result = $service->schedule();
        $output->writeln($result['msg']);
        return 0;
    }
}