<?php
declare(strict_types=1);
namespace app\common\service\home;

use app\common\model\TemplateLicense;
use app\common\model\TemplateOrder;
use app\common\model\TemplateStore;

class MyTemplateService
{
    public static function getMyTemplates(int $memberId): array
    {
        $licenses = TemplateLicense::where('member_id', $memberId)
            ->where('status', TemplateLicense::STATUS_ACTIVE)
            ->where(function ($q) { $q->where('expires_at', 0)->whereOr('expires_at', '>', time()); })
            ->order('create_time', 'desc')->select();
        $result = [];
        foreach ($licenses as $license) {
            $template = TemplateStore::find($license->template_id);
            $order = TemplateOrder::find($license->order_id);
            if ($template) $result[] = ['license' => $license, 'template' => $template, 'order' => $order];
        }
        return $result;
    }

    public static function checkOwnership(int $memberId, int $templateId): bool
    {
        return TemplateLicense::checkLicense($memberId, $templateId);
    }
}
