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