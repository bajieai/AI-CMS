<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\CollectService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * 采集执行CLI命令 - V2.5新增
 * 用法: php think collect:run <source_id> [--rewrite=1]
 */
class CollectRun extends Command
{
    protected function configure()
    {
        $this->setName('collect:run')
            ->addArgument('source_id', Argument::REQUIRED, '采集源ID')
            ->addOption('rewrite', 'r', \think\console\input\Option::VALUE_OPTIONAL, '是否AI改写', 0)
            ->setDescription('执行内容采集任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $sourceId = (int) $input->getArgument('source_id');
        $rewrite = (bool) $input->getOption('rewrite');

        try {
            $result = CollectService::runCollect($sourceId, $rewrite);
            $output->writeln("<info>采集完成: 新增{$result['added']}篇，跳过{$result['skipped']}篇</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>采集失败: {$e->getMessage()}</error>");
            return 1;
        }

        return 0;
    }
}
