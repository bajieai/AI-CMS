<?php
declare(strict_types=1);
namespace app\common\service\content;

use app\common\model\ContentAuditLog;
use app\common\model\Content;

/**
 * 内容审计日志服务 (V2.9.29 I-6)
 * 字段级diff+回滚
 */
class ContentAuditLogService
{
    /**
     * 记录操作日志
     */
    public function log(int $contentId, int $userId, string $operation, array $oldData = [], array $newData = []): void
    {
        $diff = $this->computeDiff($oldData, $newData);

        ContentAuditLog::create([
            'content_id' => $contentId,
            'user_id' => $userId,
            'operation' => $operation,
            'diff_summary' => json_encode($diff, JSON_UNESCAPED_UNICODE),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->header('user-agent', ''), 0, 500),
            'create_time' => time(),
        ]);
    }

    /**
     * 计算字段级diff
     */
    private function computeDiff(array $old, array $new): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;
            if ($oldVal !== $newVal) {
                $diff[$key] = ['old' => $oldVal, 'new' => $newVal];
            }
        }
        return $diff;
    }

    /**
     * 获取内容的操作历史
     */
    public function getHistory(int $contentId, int $limit = 50): array
    {
        return ContentAuditLog::where('content_id', $contentId)
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 回滚到指定版本
     */
    public function rollback(int $logId): bool
    {
        $log = ContentAuditLog::find($logId);
        if (!$log || $log->operation !== 'update') return false;

        $diff = json_decode($log->diff_summary, true);
        if (empty($diff)) return false;

        $content = Content::find($log->content_id);
        if (!$content) return false;

        // 用旧值恢复
        foreach ($diff as $field => $change) {
            if (isset($change['old'])) {
                $content->{$field} = $change['old'];
            }
        }

        $result = $content->save();

        // 记录回滚操作
        $this->log($log->content_id, $log->user_id, 'restore');

        return (bool) $result;
    }
}
