<?php

declare(strict_types=1);

namespace app\common\service\sys;

use think\facade\Db;
use think\facade\Cache;

/**
 * 日志管理服务
 */
class LogManageService
{
    /**
     * 获取日志列表
     */
    public static function getLogs(array $filter, int $page = 1, int $pageSize = 20): array
    {
        $query = Db::name('security_log');

        if (!empty($filter['module'])) {
            $query->where('module', $filter['module']);
        }
        if (!empty($filter['action_type'])) {
            $query->where('action_type', $filter['action_type']);
        }
        if (!empty($filter['user_id'])) {
            $query->where('user_id', $filter['user_id']);
        }
        if (!empty($filter['start_date'])) {
            $query->where('create_time', '>=', $filter['start_date']);
        }
        if (!empty($filter['end_date'])) {
            $query->where('create_time', '<=', $filter['end_date'] . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $pageSize)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 搜索日志
     */
    public static function searchLogs(string $keyword, array $filter = []): array
    {
        $query = Db::name('security_log')->where('action|detail|ip', 'like', "%{$keyword}%");

        if (!empty($filter['module'])) {
            $query->where('module', $filter['module']);
        }
        if (!empty($filter['start_date'])) {
            $query->where('create_time', '>=', $filter['start_date']);
        }

        return $query->order('id', 'desc')->limit(100)->select()->toArray();
    }

    /**
     * 日志统计
     */
    public static function getLogStats(): array
    {
        $total = Db::name('security_log')->count();
        $today = Db::name('security_log')->whereTime('create_time', 'today')->count();
        $modules = Db::name('security_log')
            ->field('module, COUNT(*) as count')
            ->group('module')
            ->order('count', 'desc')
            ->select()
            ->toArray();

        $actions = Db::name('security_log')
            ->field('action_type, COUNT(*) as count')
            ->group('action_type')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return [
            'total'   => $total,
            'today'   => $today,
            'modules' => $modules,
            'actions' => $actions,
        ];
    }

    /**
     * 归档日志
     */
    public static function archiveLogs(string $date): int
    {
        return Db::name('security_log')
            ->where('create_time', '<', $date)
            ->delete();
    }

    /**
     * 清理旧日志
     */
    public static function cleanOldLogs(int $days): int
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return Db::name('security_log')
            ->where('create_time', '<', $threshold)
            ->delete();
    }

    /**
     * 导出日志
     */
    public static function exportLogs(array $filter): string
    {
        $query = Db::name('security_log');
        if (!empty($filter['module'])) $query->where('module', $filter['module']);
        if (!empty($filter['start_date'])) $query->where('create_time', '>=', $filter['start_date']);
        if (!empty($filter['end_date'])) $query->where('create_time', '<=', $filter['end_date'] . ' 23:59:59');

        $logs = $query->order('id', 'desc')->limit(10000)->select()->toArray();

        $csv = "ID,用户ID,模块,操作类型,操作,IP,时间\n";
        foreach ($logs as $log) {
            $csv .= sprintf("%d,%s,%s,%s,%s,%s,%s\n",
                $log['id'],
                $log['user_id'] ?? '',
                $log['module'] ?? '',
                $log['action_type'] ?? '',
                str_replace(',', ' ', $log['action'] ?? ''),
                $log['ip'] ?? '',
                $log['create_time'] ?? ''
            );
        }

        return $csv;
    }
}
