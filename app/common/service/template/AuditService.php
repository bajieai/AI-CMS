<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateAuditLog;
use app\common\model\TemplateRejectReason;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板审核流程服务 — V2.9.26 P-3
 *
 * 流程：提交审核 -> 审核(通过/驳回) -> 撤回
 * 状态：draft -> pending -> approved/rejected -> published
 */
class AuditService
{
    public function submit(int $templateId, int $userId, string $userName): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];
        if (($template->review_status ?? '') === 'pending') return ['success' => false, 'message' => '模板正在审核中'];

        $prevStatus = $template->review_status ?? 'draft';
        $template->review_status = 'pending';
        $template->save();
        TemplateAuditLog::logAction($templateId, $userId, $userName, 'submit', 'pending', $prevStatus, 'pending');
        return ['success' => true, 'message' => '已提交审核'];
    }

    public function approve(int $templateId, int $auditorId, string $auditorName, string $comment = ''): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];
        if (($template->review_status ?? '') !== 'pending') return ['success' => false, 'message' => '模板不在待审核状态'];

        $prevStatus = $template->review_status;
        $template->review_status = 'approved';
        $template->is_published = 1;
        $template->save();
        TemplateAuditLog::logAction($templateId, $auditorId, $auditorName, 'approve', 'approved', $prevStatus, 'approved', $comment);
        return ['success' => true, 'message' => '审核通过'];
    }

    public function reject(int $templateId, int $auditorId, string $auditorName, string $reason, int $reasonId = 0): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];
        if (($template->review_status ?? '') !== 'pending') return ['success' => false, 'message' => '模板不在待审核状态'];

        $prevStatus = $template->review_status;
        $template->review_status = 'rejected';
        $template->save();
        TemplateAuditLog::logAction($templateId, $auditorId, $auditorName, 'reject', 'rejected', $prevStatus, 'rejected', $reason, $reasonId);
        return ['success' => true, 'message' => '已驳回'];
    }

    public function withdraw(int $templateId, int $userId, string $userName): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];

        $prevStatus = $template->review_status ?? 'draft';
        $template->review_status = 'draft';
        $template->save();
        TemplateAuditLog::logAction($templateId, $userId, $userName, 'withdraw', 'draft', $prevStatus, 'draft');
        return ['success' => true, 'message' => '已撤回'];
    }

    public function getPendingList(int $page = 1, int $limit = 20): array
    {
        $query = TemplateStore::where('review_status', 'pending')->order('updated_at', 'asc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function getRejectReasons(string $category = ''): array
    {
        return TemplateRejectReason::getActiveReasons($category);
    }

    public function getHistory(int $templateId): array
    {
        return TemplateAuditLog::getHistory($templateId);
    }
}
