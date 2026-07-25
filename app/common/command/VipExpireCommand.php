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

use app\common\model\Member;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * VIP过期扫描定时任务 - V2.7
 * 用法: php think vip:expire [--notify=1]
 */
class VipExpireCommand extends Command
{
    protected function configure()
    {
        $this->setName('vip:expire')
            ->setDescription('扫描并处理VIP过期的会员')
            ->addOption('notify', 'n', \think\console\input\Option::VALUE_OPTIONAL, '是否发送过期通知', 0);
    }

    protected function execute(Input $input, Output $output)
    {
        $notify = (int) $input->getOption('notify');
        $now = time();
        $expireBefore = $now - 86400; // 24小时内过期

        // 1. 标记已过期（vip_expire_time < now 且 之前是有效的）
        $expiredCount = Member::where('vip_expire_time', '>', 0)
            ->where('vip_expire_time', '<', $now)
            ->where('vip_expire_time', '>=', $expireBefore)
            ->count();

        // 2. 即将过期（7天内）
        $soonExpire = $now + 7 * 86400;
        $soonCount = Member::where('vip_expire_time', '>', $now)
            ->where('vip_expire_time', '<', $soonExpire)
            ->count();

        // 实际处理：将过期会员的level_id重置为默认等级，并记录日志
        $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->value('id') ?: 0;
        $affected = Member::where('vip_expire_time', '>', 0)
            ->where('vip_expire_time', '<', $now)
            ->where('level_id', '>', $defaultLevel)
            ->update([
                'level_id' => $defaultLevel,
                'update_time' => $now,
            ]);

        $output->writeln("<info>VIP过期扫描完成</info>");
        $output->writeln("<comment>24小时内过期会员: {$expiredCount}</comment>");
        $output->writeln("<comment>7天内即将过期会员: {$soonCount}</comment>");
        $output->writeln("<info>已重置等级会员: {$affected}</info>");

        if ($notify && ($expiredCount > 0 || $soonCount > 0)) {
            // 站内通知/邮件通知（接入EmailService队列）
            $expiredMembers = Member::where('vip_expire_time', '>', 0)
                ->where('vip_expire_time', '<', $now)
                ->where('vip_expire_time', '>=', $expireBefore)
                ->select();
            foreach ($expiredMembers as $m) {
                try {
                    \app\common\service\EmailService::queue('vip_expire', $m->email ?? '', [
                        'nickname' => $m->nickname ?? $m->username,
                        'expire_date' => date('Y-m-d', $m->vip_expire_time),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("VIP过期通知邮件入队失败: " . $e->getMessage());
                }
            }
            $output->writeln("<info>已触发过期通知邮件入队</info>");
        }

        Log::info("VIP过期扫描: 已过期{$expiredCount}, 即将过期{$soonCount}, 重置等级{$affected}");
        return 0;
    }
}
