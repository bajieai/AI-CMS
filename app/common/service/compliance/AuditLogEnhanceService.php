<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-2: 审计日志增强服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 审计日志增强服务 - V2.9.39 COMPLIANCE-2
 * 字段级审计 + 合规审计 + 报告 + 告警
 */
class AuditLogEnhanceService
{
    protected const CACHE_TAG = 'audit_log_enhance';
    protected const CACHE_TTL = 300;

    protected string $logTable = 'security_log';

    // 审计级别
    public const LEVEL_INFO     = 'info';
    public const LEVEL_WARNING   = 'warning';
    public const LEVEL_CRITICAL  = 'critical';

    // 审计类别
    public const CATEGORY_AUTH         = 'authentication';
    public const CATEGORY_DATA_ACCESS  = 'data_access';
    public const CATEGORY_DATA_CHANGE  = 'data_change';
    public const CATEGORY_PERMISSION   = 'permission';
    public const CATEGORY_CONFIG       = 'configuration';
    public const CATEGORY_EXPORT       = 'data_export';
    public const CATEGORY_DELETE       = 'data_delete';

    /**
     * 记录字段级审计日志
     * 记录数据的变更前/变更后值
     */
    public function logFieldChange(
        int $userId,
        string $module,
        string $table,
        int $recordId,
        array $oldData,
        array $newData,
        string $action = 'update'
    ): void {
        $changes = $this->diffFields($oldData, $newData);

        if (empty($changes)) {
            return;
        }

        $logData = [
            'user_id'      => $userId,
            'module'       => $module,
            'action'       => $action,
            'table_name'   => $table,
            'record_id'    => $recordId,
            'category'     => self::CATEGORY_DATA_CHANGE,
            'level'        => self::LEVEL_INFO,
            'field_changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);
    }

    /**
     * 记录数据访问审计
     */
    public function logDataAccess(
        int $userId,
        string $module,
        string $table,
        ?int $recordId = null,
        array $queryParams = []
    ): void {
        $logData = [
            'user_id'      => $userId,
            'module'       => $module,
            'action'       => 'read',
            'table_name'   => $table,
            'record_id'    => $recordId ?? 0,
            'category'     => self::CATEGORY_DATA_ACCESS,
            'level'        => self::LEVEL_INFO,
            'query_params' => json_encode($queryParams, JSON_UNESCAPED_UNICODE),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);
    }

    /**
     * 记录数据导出审计
     */
    public function logDataExport(
        int $userId,
        string $module,
        string $table,
        int $recordCount,
        string $format = 'csv',
        string $filePath = ''
    ): void {
        $logData = [
            'user_id'      => $userId,
            'module'       => $module,
            'action'       => 'export',
            'table_name'   => $table,
            'category'     => self::CATEGORY_EXPORT,
            'level'        => self::LEVEL_WARNING,
            'record_count' => $recordCount,
            'export_format' => $format,
            'file_path'    => $filePath,
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);

        // 大量数据导出触发告警
        if ($recordCount > 1000) {
            $this->triggerAlert('large_data_export', $logData);
        }
    }

    /**
     * 记录数据删除审计
     */
    public function logDataDelete(
        int $userId,
        string $module,
        string $table,
        array $recordIds,
        string $reason = ''
    ): void {
        $logData = [
            'user_id'      => $userId,
            'module'       => $module,
            'action'       => 'delete',
            'table_name'   => $table,
            'category'     => self::CATEGORY_DELETE,
            'level'        => self::LEVEL_CRITICAL,
            'record_ids'   => json_encode($recordIds, JSON_UNESCAPED_UNICODE),
            'reason'       => $reason,
            'record_count' => count($recordIds),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);

        // 批量删除触发告警
        if (count($recordIds) > 50) {
            $this->triggerAlert('bulk_delete', $logData);
        }
    }

    /**
     * 记录权限变更审计
     */
    public function logPermissionChange(
        int $userId,
        int $targetUserId,
        string $action,
        array $oldPermissions,
        array $newPermissions
    ): void {
        $logData = [
            'user_id'      => $userId,
            'module'       => 'permission',
            'action'       => $action,
            'target_user_id' => $targetUserId,
            'category'     => self::CATEGORY_PERMISSION,
            'level'        => self::LEVEL_CRITICAL,
            'old_value'    => json_encode($oldPermissions, JSON_UNESCAPED_UNICODE),
            'new_value'    => json_encode($newPermissions, JSON_UNESCAPED_UNICODE),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);
        $this->triggerAlert('permission_change', $logData);
    }

    /**
     * 记录配置变更审计
     */
    public function logConfigChange(
        int $userId,
        string $configKey,
        $oldValue,
        $newValue,
        string $description = ''
    ): void {
        $logData = [
            'user_id'      => $userId,
            'module'       => 'config',
            'action'       => 'change',
            'category'     => self::CATEGORY_CONFIG,
            'level'        => self::LEVEL_WARNING,
            'config_key'   => $configKey,
            'old_value'    => is_scalar($oldValue) ? (string) $oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE),
            'new_value'    => is_scalar($newValue) ? (string) $newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE),
            'description'  => $description,
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->header('user-agent', ''), 0, 255),
            'create_time'  => time(),
        ];

        $this->writeLog($logData);
    }

    /**
     * 获取审计日志列表
     */
    public function getList(array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $userId = $params['user_id'] ?? null;
        $module = $params['module'] ?? null;
        $category = $params['category'] ?? null;
        $level = $params['level'] ?? null;
        $startTime = $params['start_time'] ?? null;
        $endTime = $params['end_time'] ?? null;

        $query = Db::name($this->logTable);

        if ($userId !== null && $userId !== '') {
            $query->where('user_id', (int) $userId);
        }
        if (!empty($module)) {
            $query->where('module', $module);
        }
        if (!empty($category)) {
            $query->where('category', $category);
        }
        if (!empty($level)) {
            $query->where('level', $level);
        }
        if (!empty($startTime)) {
            $query->where('create_time', '>=', strtotime($startTime));
        }
        if (!empty($endTime)) {
            $query->where('create_time', '<=', strtotime($endTime));
        }

        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 生成审计报告
     */
    public function generateReport(string $type = 'daily', ?string $date = null): array
    {
        $cacheKey = 'audit_report_' . $type . '_' . ($date ?? date('Y-m-d'));

        return Cache::remember($cacheKey, function () use ($type, $date) {
            $startTime = match ($type) {
                'weekly'  => strtotime('-7 days'),
                'monthly' => strtotime('-30 days'),
                default   => strtotime('-1 day'),
            };

            $endDate = $date ? strtotime($date . ' 23:59:59') : time();

            // 统计概要
            $totalLogs = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->count();

            $byCategory = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->field('category, count(*) as count')
                ->group('category')
                ->select()
                ->toArray();

            $byLevel = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->field('level, count(*) as count')
                ->group('level')
                ->select()
                ->toArray();

            $byUser = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->field('user_id, count(*) as count')
                ->group('user_id')
                ->order('count', 'desc')
                ->limit(20)
                ->select()
                ->toArray();

            // 高危操作
            $criticalOps = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->where('level', self::LEVEL_CRITICAL)
                ->count();

            // 异常IP
            $suspiciousIps = Db::name($this->logTable)
                ->whereBetweenTime('create_time', $startTime, $endDate)
                ->where('level', 'in', [self::LEVEL_WARNING, self::LEVEL_CRITICAL])
                ->field('ip_address, count(*) as count')
                ->group('ip_address')
                ->having('count > 10')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();

            return [
                'type'           => $type,
                'start_time'     => date('Y-m-d H:i:s', $startTime),
                'end_time'       => date('Y-m-d H:i:s', $endDate),
                'total_logs'     => $totalLogs,
                'by_category'    => $byCategory,
                'by_level'       => $byLevel,
                'top_users'      => $byUser,
                'critical_ops'   => $criticalOps,
                'suspicious_ips' => $suspiciousIps,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 触发告警
     */
    protected function triggerAlert(string $alertType, array $logData): void
    {
        $alert = [
            'type'        => $alertType,
            'level'       => $logData['level'] ?? self::LEVEL_WARNING,
            'user_id'     => $logData['user_id'] ?? 0,
            'module'      => $logData['module'] ?? '',
            'action'      => $logData['action'] ?? '',
            'description' => $this->getAlertDescription($alertType, $logData),
            'ip_address'  => $logData['ip_address'] ?? '',
            'create_time' => time(),
        ];

        try {
            Db::name('security_alert')->insert($alert);
        } catch (\Throwable $e) {
            Log::error('[AuditLog] 告警写入失败', ['error' => $e->getMessage()]);
        }

        Log::warning('[AuditLog] 安全告警触发', $alert);
    }

    /**
     * 获取告警描述
     */
    protected function getAlertDescription(string $alertType, array $logData): string
    {
        return match ($alertType) {
            'large_data_export'  => sprintf('用户 %d 大量数据导出（%d 条）', $logData['user_id'] ?? 0, $logData['record_count'] ?? 0),
            'bulk_delete'        => sprintf('用户 %d 批量删除 %d 条记录', $logData['user_id'] ?? 0, $logData['record_count'] ?? 0),
            'permission_change'  => sprintf('用户 %d 修改了用户 %d 的权限', $logData['user_id'] ?? 0, $logData['target_user_id'] ?? 0),
            default              => '安全告警: ' . $alertType,
        };
    }

    /**
     * 比较字段差异
     */
    protected function diffFields(array $old, array $new): array
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        // 敏感字段（变更时需重点记录）
        $sensitiveFields = ['password', 'email', 'phone', 'role_id', 'status', 'amount', 'balance'];

        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if ($oldVal !== $newVal) {
                // 敏感字段不记录旧值
                if (in_array($key, $sensitiveFields, true)) {
                    $changes[$key] = ['changed' => true, 'sensitive' => true];
                } else {
                    $changes[$key] = [
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * 写入审计日志
     */
    protected function writeLog(array $data): void
    {
        try {
            Db::name($this->logTable)->insert($data);
        } catch (\Throwable $e) {
            // 降级：写入PHP错误日志
            error_log('[AUDIT_LOG_FALLBACK] ' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' | Error: ' . $e->getMessage());
        }
    }
}
