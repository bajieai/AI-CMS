<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\EmailService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 邮件队列Worker CLI命令 - V2.5新增
 * 用法: php think email:worker [--limit=100]
 */
class EmailWorker extends Command
{
    protected function configure()
    {
        $this->setName('email:worker')
            ->setDescription('处理邮件发送队列')
            ->addOption('limit', 'l', \think\console\input\Option::VALUE_OPTIONAL, '每次处理最大数量', 100);
    }

    protected function execute(Input $input, Output $output)
    {
        $limit = (int) $input->getOption('limit');
        $output->writeln("<info>开始处理邮件队列，最大处理数: {$limit}</info>");

        try {
            $result = EmailService::processQueue($limit);
            $output->writeln("<info>处理完成: 成功{$result['success']}封，失败{$result['fail']}封</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>邮件队列处理失败: {$e->getMessage()}</error>");
            return 1;
        }

        return 0;
    }
}
