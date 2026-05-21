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

namespace app\common\service;

use app\common\model\InviteLog;
use app\common\model\PointsLog;
use think\facade\Log;

/**
 * 邀请奖励服务 - V2.9新增
 * 事件驱动模式，解耦SigninService/PaidService
 *
 * 奖励阶段：0(注册) → 1(首次签到) → 2(首次付费)
 * 每个阶段仅触发一次，按阶段递进
 */
class InviteRewardService
{
    /**
     * 会员事件统一入口
     *
     * @param int    $memberId 被邀请人ID
     * @param string $event    事件类型：register/signin/pay
     */
    public static function onMemberEvent(int $memberId, string $event): void
    {
        // 查找邀请关系（invitee_id = 被邀请人）
        $relation = InviteLog::where('invitee_id', $memberId)->find();
        if (!$relation) {
            return; // 无邀请关系，直接返回
        }

        $stageMap   = ['register' => 0, 'signin' => 1, 'pay' => 2];
        $targetStage = $stageMap[$event] ?? -1;

        // 阶段无效 或 当前阶段已达标，跳过
        // reward_stage: -1=未发放, 0=注册已发, 1=签到已发, 2=付费已发
        if ($targetStage < 0 || $relation->reward_stage >= $targetStage) {
            return;
        }

        // 发放积分
        $configKey = "points_invite_{$event}";
        $points    = (int) ConfigService::get($configKey, 0);

        if ($points > 0 && $relation->inviter_id > 0) {
            PointsLog::add($relation->inviter_id, $points, "邀请奖励-{$event}");
            Log::info("[InviteReward] 邀请奖励发放: invitee={$memberId}, inviter={$relation->inviter_id}, event={$event}, points={$points}");
        }

        // 注册事件：同时发放被邀请人奖励
        if ($event === 'register') {
            $inviteePoints = (int) ConfigService::get('points_invitee_register', 20);
            if ($inviteePoints > 0) {
                PointsLog::add($memberId, $inviteePoints, 'invitee_register', 0, '被邀请注册奖励');
                Log::info("[InviteReward] 被邀请人奖励发放: invitee={$memberId}, points={$inviteePoints}");
            }
        }

        // 推进阶段
        $relation->reward_stage = $targetStage;
        $relation->save();
    }
}
