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

use app\common\service\PaymentService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 超时订单关闭CLI命令 - V2.5新增
 * 用法: php think order:close-timeout
 */
class OrderCloseTimeout extends Command
{
    protected function configure()
    {
        $this->setName('order:close-timeout')
            ->setDescription('关闭超时未支付的订单');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始关闭超时订单...</info>');

        try {
            $count = PaymentService::closeExpiredOrders();
            $output->writeln("<info>处理完成: 关闭{$count}个超时订单</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>处理失败: {$e->getMessage()}</error>");
            return 1;
        }

        return 0;
    }
}
