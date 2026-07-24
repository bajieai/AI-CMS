<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateAuditConfig;
use app\common\model\TemplateAuditLog;
use app\common\model\TemplateRejectReason;
use app\common\model\TemplateStore;
use app\common\model\TemplateVersionRecord;
use think\facade\Cache;

/**
 * 模板审核工作流服务 — V2.9.28 M-5
 *
 * 多级审核：提交 → 初审 → 终审 → 发布
 * 审核层级可配置(1单级/2两级/3三级)
 * 审核人空时自动跳过(用户确认Q4)
 */
class TemplateAuditWorkflowService
{
    private const CACHE_TAG = 'template_audit_workflow';

    // 审核状态
    const ST_DRAFT = 'draft';
    const ST_PENDING_FIRST = 'pending_first';  // 待初审
    const ST_PENDING_FINAL = 'pending_final';  // 待终审
    const ST_APPROVED = 'approved';
    const ST_REJECTED = 'rejected';
    const ST_PUBLISHED = 'published';

    /**
     * 提交审核
     */
    public function submit(int $templateId, int $userId, string $userName): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $currentStatus = $template->review_status ?? self::ST_DRAFT;
        if (in_array($currentStatus, [self::ST_PENDING_FIRST, self::ST_PENDING_FINAL])) {
            return ['success' => false, 'message' => '模板正在审核中'];
        }

        $config = TemplateAuditConfig::getForTemplate($templateId);
        $level = $config['audit_level'] ?? 2;

        // 根据审核层级决定起始状态
        $newStatus = $level == 1 ? self::ST_PENDING_FINAL : self::ST_PENDING_FIRST;

        $prevStatus = $currentStatus;
        $template->review_status = $newStatus;
        $template->save();

        TemplateAuditLog::logAction(
            $templateId, $userId, $userName,
            'submit', $newStatus, $prevStatus, $newStatus
        );
        Cache::clear();

        return ['success' => true, 'message' => '已提交审核'];
    }

    /**
     * 初审通过
     */
    public function firstReviewPass(int $templateId, int $auditorId, string $auditorName, string $comment = ''): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $currentStatus = $template->review_status ?? '';
        if ($currentStatus !== self::ST_PENDING_FIRST) {
            return ['success' => false, 'message' => '模板不在待初审状态'];
        }

        $prevStatus = $currentStatus;
        $template->review_status = self::ST_PENDING_FINAL;
        $template->save();

        TemplateAuditLog::logAction(
            $templateId, $auditorId, $auditorName,
            'first_review_pass', self::ST_PENDING_FINAL, $prevStatus, self::ST_PENDING_FINAL, $comment
        );
        Cache::clear();

        return ['success' => true, 'message' => '初审通过，等待终审'];
    }

    /**
     * 终审通过
     */
    public function finalReviewPass(int $templateId, int $auditorId, string $auditorName, string $comment = ''): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $currentStatus = $template->review_status ?? '';
        if ($currentStatus !== self::ST_PENDING_FINAL) {
            return ['success' => false, 'message' => '模板不在待终审状态'];
        }

        $prevStatus = $currentStatus;
        $template->review_status = self::ST_APPROVED;
        $template->is_published = 1;
        $template->status = TemplateStore::STATUS_ONLINE;
        $template->save();

        TemplateAuditLog::logAction(
            $templateId, $auditorId, $auditorName,
            'final_review_pass', self::ST_APPROVED, $prevStatus, self::ST_APPROVED, $comment
        );
        Cache::clear();

        return ['success' => true, 'message' => '终审通过，模板已发布'];
    }

    /**
     * 驳回审核
     */
    public function reject(int $templateId, int $auditorId, string $auditorName, string $reason, int $reasonId = 0): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $currentStatus = $template->review_status ?? '';
        if (!in_array($currentStatus, [self::ST_PENDING_FIRST, self::ST_PENDING_FINAL])) {
            return ['success' => false, 'message' => '模板不在审核中'];
        }

        $prevStatus = $currentStatus;
        $template->review_status = self::ST_REJECTED;
        $template->save();

        TemplateAuditLog::logAction(
            $templateId, $auditorId, $auditorName,
            'reject', self::ST_REJECTED, $prevStatus, self::ST_REJECTED, $reason, $reasonId
        );
        Cache::clear();

        return ['success' => true, 'message' => '已驳回'];
    }

    /**
     * 撤回审核
     */
    public function withdraw(int $templateId, int $userId, string $userName): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $prevStatus = $template->review_status ?? self::ST_DRAFT;
        $template->review_status = self::ST_DRAFT;
        $template->save();

        TemplateAuditLog::logAction(
            $templateId, $userId, $userName,
            'withdraw', self::ST_DRAFT, $prevStatus, self::ST_DRAFT
        );
        Cache::clear();

        return ['success' => true, 'message' => '已撤回'];
    }

    /**
     * 获取待审核列表
     */
    public function getPendingList(string $stage = '', int $page = 1, int $limit = 20): array
    {
        $query = TemplateStore::whereNotNull('review_status');

        if ($stage === 'first') {
            $query->where('review_status', self::ST_PENDING_FIRST);
        } elseif ($stage === 'final') {
            $query->where('review_status', self::ST_PENDING_FINAL);
        } else {
            $query->whereIn('review_status', [self::ST_PENDING_FIRST, self::ST_PENDING_FINAL]);
        }

        $total = $query->count();
        $list = $query->order('update_time', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取审核历史
     */
    public function getHistory(int $templateId): array
    {
        return TemplateAuditLog::getHistory($templateId);
    }

    /**
     * 保存审核配置
     */
    public function saveAuditConfig(int $templateId, array $data): array
    {
        $config = TemplateAuditConfig::where('template_id', $templateId)->find();
        if ($config) {
            $config->save($data);
        } else {
            $data['template_id'] = $templateId;
            TemplateAuditConfig::create($data);
        }
        Cache::clear();
        return ['success' => true, 'message' => '配置已保存'];
    }

    /**
     * 获取版本对比数据
     */
    public function getVersionDiff(int $templateId): array
    {
        $versions = TemplateVersionRecord::where('template_id', $templateId)
            ->order('id', 'desc')
            ->limit(2)
            ->select()
            ->toArray();

        if (count($versions) < 2) {
            return ['success' => false, 'message' => '历史版本不足，无法对比'];
        }

        return [
            'success' => true,
            'current' => $versions[0],
            'previous' => $versions[1],
        ];
    }
}
