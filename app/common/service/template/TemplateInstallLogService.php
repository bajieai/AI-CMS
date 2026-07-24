<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint T3: 模板安装日志服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateInstallLog;
use think\facade\Request;

/**
 * 模板安装日志服务 - V2.9.31 T3-2
 */
class TemplateInstallLogService
{
    /**
     * 记录安装日志
     */
    public function log(int $storeId, int $memberId, string $action, array $extra = []): void
    {
        $log = new TemplateInstallLog();
        $log->store_id = $storeId;
        $log->member_id = $memberId;
        $log->action = $action;
        $log->from_version = $extra['from_version'] ?? '';
        $log->to_version = $extra['to_version'] ?? '';
        $log->ip = Request::ip() ?: '';
        $log->user_agent = Request::header('User-Agent') ?: '';
        $log->result = $extra['result'] ?? 1;
        $log->error_msg = $extra['error_msg'] ?? '';
        $log->create_time = time();
        $log->save();
    }

    /**
     * 获取模板的安装统计
     */
    public function getStoreStats(int $storeId): array
    {
        $total = TemplateInstallLog::byStore($storeId)->count();
        $success = TemplateInstallLog::byStore($storeId)->success()->count();
        $install = TemplateInstallLog::byStore($storeId)->where('action', TemplateInstallLog::ACTION_INSTALL)->count();
        $upgrade = TemplateInstallLog::byStore($storeId)->where('action', TemplateInstallLog::ACTION_UPGRADE)->count();

        return [
            'total' => $total,
            'success' => $success,
            'failure' => $total - $success,
            'install' => $install,
            'upgrade' => $upgrade,
            'success_rate' => $total > 0 ? round($success / $total * 100, 1) : 0,
        ];
    }

    /**
     * 获取用户安装历史
     */
    public function getMemberHistory(int $memberId, int $limit = 20): array
    {
        return TemplateInstallLog::byMember($memberId)
            ->with('store')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取最近安装日志（后台）
     */
    public function getRecentLogs(int $limit = 50): array
    {
        return TemplateInstallLog::with('store')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取安装趋势（按天）
     */
    public function getTrend(int $days = 7): array
    {
        $start = strtotime("-{$days} days");
        $end = time();

        $rows = TemplateInstallLog::where('create_time', '>=', $start)
            ->where('create_time', '<=', $end)
            ->where('action', TemplateInstallLog::ACTION_INSTALL)
            ->where('result', 1)
            ->field([
                'DATE_FORMAT(FROM_UNIXTIME(create_time), "%Y-%m-%d") as date',
                'COUNT(*) as count',
            ])
            ->group('date')
            ->select()
            ->toArray();

        $map = array_column($rows, 'count', 'date');
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'date' => $date,
                'count' => (int) ($map[$date] ?? 0),
            ];
        }
        return $result;
    }
}
