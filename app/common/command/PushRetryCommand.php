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

use app\common\service\PushRetryService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 推送重试队列处理命令 - V2.9.19 D-1c
 *
 * 用法: php think push:retry [--limit=50]
 * 建议 cron: 每5分钟执行一次
 */
class PushRetryCommand extends Command
{
    protected function configure()
    {
        $this->setName('push:retry')
            ->setDescription('处理推送重试队列')
            ->addOption('limit', 'l', \think\console\input\Option::VALUE_OPTIONAL, '每次处理最大数量', 50);
    }

    protected function execute(Input $input, Output $output)
    {
        $limit = (int) $input->getOption('limit');
        $output->writeln('<info>开始处理推送重试队列...</info>');

        $stats = PushRetryService::getStats();
        $output->writeln("待处理: {$stats['pending']} 成功: {$stats['success']} 失败: {$stats['failed']}");

        if ($stats['pending'] === 0) {
            $output->writeln('<comment>无待重试任务</comment>');
            return 0;
        }

        $result = PushRetryService::processRetries($limit);
        $output->writeln("<info>处理完成: 成功{$result['success']} 失败{$result['fail']} 延后{$result['skip']}</info>");

        return 0;
    }
}
