<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 模板审核日志模型 — V2.9.26 P-3
 */
class TemplateAuditLog extends Model
{
    protected $name = 'template_audit_log';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    public const CACHE_TAG = 'template_audit';

    public static function logAction(
        int $templateId, int $auditorId, string $auditorName,
        string $action, string $status, string $prevStatus, string $newStatus,
        string $reason = '', int $reasonId = 0
    ): void {
        self::create([
            'template_id'  => $templateId,
            'auditor_id'   => $auditorId,
            'auditor_name' => $auditorName,
            'action'       => $action,
            'status'       => $status,
            'reason'       => $reason,
            'reason_id'    => $reasonId,
            'prev_status'  => $prevStatus,
            'new_status'   => $newStatus,
        ]);
        Cache::tag(self::CACHE_TAG)->clear();
    }

    public static function getHistory(int $templateId, int $limit = 50): array
    {
        return self::where('template_id', $templateId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
