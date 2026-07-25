<?php
declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 审计日志查询服务 - V2.9.40 COMPLIANCE2-1
 *
 * 审计日志高级检索：全文搜索+时间范围+操作类型+用户+多条件组合过滤
 * 基于V2.9.37 AuditLogEnhanceService扩展
 */
class AuditLogQueryService
{
    private const CACHE_TAG = 'audit_log_query';
    private const CACHE_TTL = 300;

    /**
     * 高级搜索审计日志
     */
    public function search(array $filters, int $page = 1, int $limit = 20): array
    {
        $query = Db::name('audit_log');

        // 时间范围
        if (!empty($filters['start_time'])) {
            $query->where('created_at', '>=', strtotime($filters['start_time']));
        }
        if (!empty($filters['end_time'])) {
            $query->where('created_at', '<=', strtotime($filters['end_time']));
        }

        // 操作类型
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // 操作模块
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        // 操作用户
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        // IP地址
        if (!empty($filters['ip'])) {
            $query->where('ip', $filters['ip']);
        }

        // 关键词搜索（模糊匹配描述字段）
        if (!empty($filters['keyword'])) {
            $query->where('description', 'like', '%' . $filters['keyword'] . '%');
        }

        // 风险等级
        if (!empty($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        // 状态
        if (!empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'list'  => $list,
        ];
    }

    /**
     * 获取审计日志详情
     */
    public function getDetail(int $id): ?array
    {
        $log = Db::name('audit_log')->find($id);
        if (!$log) return null;

        // 解析变更详情
        $log['change_detail'] = json_decode($log['change_detail'] ?? '{}', true);
        $log['before_data'] = json_decode($log['before_data'] ?? '{}', true);
        $log['after_data'] = json_decode($log['after_data'] ?? '{}', true);

        return $log;
    }

    /**
     * 获取操作类型统计
     */
    public function getActionStats(array $filters = []): array
    {
        $query = Db::name('audit_log');

        if (!empty($filters['start_time'])) {
            $query->where('created_at', '>=', strtotime($filters['start_time']));
        }

        return $query->group('action')
            ->column('count(*) as cnt', 'action');
    }

    /**
     * 获取用户操作统计
     */
    public function getUserStats(array $filters = []): array
    {
        $query = Db::name('audit_log');

        if (!empty($filters['start_time'])) {
            $query->where('created_at', '>=', strtotime($filters['start_time']));
        }

        return $query->group('user_id')
            ->field('user_id, count(*) as cnt, min(created_at) as first_action, max(created_at) as last_action')
            ->order('cnt', 'desc')
            ->limit(20)
            ->select()
            ->toArray();
    }

    /**
     * 获取风险事件统计
     */
    public function getRiskStats(): array
    {
        return [
            'high_risk'    => Db::name('audit_log')->where('risk_level', 'high')->whereTime('created_at', 'today')->count(),
            'medium_risk'  => Db::name('audit_log')->where('risk_level', 'medium')->whereTime('created_at', 'today')->count(),
            'low_risk'     => Db::name('audit_log')->where('risk_level', 'low')->whereTime('created_at', 'today')->count(),
            'total_today'  => Db::name('audit_log')->whereTime('created_at', 'today')->count(),
        ];
    }

    /**
     * 导出审计日志
     */
    public function export(array $filters, string $format = 'csv'): string
    {
        $list = $this->search($filters, 1, 10000)['list'];

        if ($format === 'csv') {
            $exportService = new \app\common\service\data\ReportExportService();
            return $exportService->exportCsv($list, [
                'ID', '操作模块', '操作类型', '描述', '操作用户ID', 'IP', '风险等级', '时间',
            ], [
                'id', 'module', 'action', 'description', 'user_id', 'ip', 'risk_level', 'created_at',
            ]);
        }

        return json_encode($list);
    }

    /**
     * 获取搜索过滤器选项
     */
    public function getFilterOptions(): array
    {
        return [
            'actions'     => Db::name('audit_log')->group('action')->column('action'),
            'modules'     => Db::name('audit_log')->group('module')->column('module'),
            'risk_levels' => ['low', 'medium', 'high'],
        ];
    }
}
