<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateUserAction;

/**
 * 模板用户行为采集服务 (V2.9.29 T-2)
 */
class TemplateBehaviorService
{
    public function recordAction(int $userId, int $templateId, string $action): void
    {
        if ($userId <= 0 || $templateId <= 0) return;

        TemplateUserAction::create([
            'user_id' => $userId,
            'template_id' => $templateId,
            'action' => $action,
            'create_time' => time(),
        ]);
    }

    public function recordView(int $userId, int $templateId): void
    {
        $this->recordAction($userId, $templateId, 'view');
    }

    public function recordDownload(int $userId, int $templateId): void
    {
        $this->recordAction($userId, $templateId, 'download');
    }

    public function recordBuy(int $userId, int $templateId): void
    {
        $this->recordAction($userId, $templateId, 'buy');
    }

    public function recordFavorite(int $userId, int $templateId): void
    {
        $this->recordAction($userId, $templateId, 'favorite');
    }
}
