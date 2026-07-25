<?php
declare(strict_types=1);

namespace app\home\service;

use app\common\model\Developer;
use app\common\model\TemplateStore;

/**
 * 开发者申请服务 - V2.9.29 Sprint D-1
 */
class DeveloperApplyService
{
    public function apply(int $userId, array $data): bool
    {
        $existing = Developer::where('user_id', $userId)->find();
        if ($existing) {
            $existing->real_name = $data['real_name'];
            $existing->contact_phone = $data['contact_phone'];
            $existing->contact_email = $data['contact_email'];
            $existing->introduction = $data['introduction'];
            $existing->status = 0;
            return (bool) $existing->save();
        }
        $dev = new Developer();
        $dev->user_id = $userId;
        $dev->real_name = $data['real_name'];
        $dev->contact_phone = $data['contact_phone'];
        $dev->contact_email = $data['contact_email'];
        $dev->introduction = $data['introduction'];
        $dev->level = 1;
        $dev->status = 0;
        return (bool) $dev->save();
    }

    public function getDeveloperByUserId(int $userId): ?Developer
    {
        return Developer::where('user_id', $userId)->find();
    }

    public function getPanelData(int $userId): array
    {
        $dev = $this->getDeveloperByUserId($userId);
        $templateCount = 0;
        $totalRevenue = 0;
        if ($dev) {
            $templateCount = TemplateStore::where('developer_id', $dev->id)->count();
            $totalRevenue = (float) $dev->total_revenue;
        }
        return [
            'template_count' => $templateCount,
            'total_revenue' => $totalRevenue,
            'level' => $dev->level ?? 1,
        ];
    }
}
