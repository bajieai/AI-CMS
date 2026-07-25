<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\AbTest;
use app\common\service\ops\AbTestService;

/**
 * AB测试定时检查命令
 * V2.9.38 OPS-DEEP-1
 */
class AbTestCheckCommand extends Command
{
    protected function configure()
    {
        $this->setName('ab_test:check')->setDescription('AB测试定时检查(自动停止过期测试)');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始检查AB测试...</info>');
        
        $expired = AbTest::where('status', 'running')
            ->where('end_time', '<', date('Y-m-d H:i:s'))
            ->select()
            ->toArray();
        
        $service = new AbTestService();
        foreach ($expired as $test) {
            $service->stopTest($test['id']);
            $output->writeln("  已停止过期测试: {$test['test_name']} (ID: {$test['id']})");
        }
        
        $output->writeln("<info>检查完成，共处理{count($expired)}个过期测试</info>");
    }
}
