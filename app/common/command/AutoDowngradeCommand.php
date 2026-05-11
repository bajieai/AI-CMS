<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\MemberLevelService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 会员自动降级命令 - V2.9.3 M20
 * 含7天缓冲期预警通知
 * 用法: php think member:auto-downgrade
 */
class AutoDowngradeCommand extends Command
{
    protected function configure()
    {
        $this->setName('member:auto-downgrade')
            ->setDescription('扫描会员积分，执行自动降级（含缓冲期）');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->info('开始执行会员自动降级扫描...');

        $result = MemberLevelService::autoDowngrade();

        $output->info("扫描完成：预警 {$result['warned']} 人，降级 {$result['downgraded']} 人，恢复 {$result['cancelled']} 人");
        Log::info("[AutoDowngrade] 扫描完成: " . json_encode($result));

        return 0;
    }
}
