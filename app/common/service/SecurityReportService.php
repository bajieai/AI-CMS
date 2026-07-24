<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 SEC-6: 安全审计报告服务
 * 日/周/月报自动生成 + 合规检查
 */
class SecurityReportService
{
    /**
     * 生成日报
     */
    public function generateDailyReport(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');
        $startTime = $date . ' 00:00:00';
        $endTime = $date . ' 23:59:59';

        return $this->buildReport($startTime, $endTime, 'daily', $date);
    }

    /**
     * 生成周报
     */
    public function generateWeeklyReport(string $endDate = ''): array
    {
        $endDate = $endDate ?: date('Y-m-d');
        $startDate = date('Y-m-d', strtotime($endDate . ' -6 days'));

        return $this->buildReport(
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59',
            'weekly',
            $startDate . '~' . $endDate
        );
    }

    /**
     * 生成月报
     */
    public function generateMonthlyReport(string $month = ''): array
    {
        $month = $month ?: date('Y-m');
        $startTime = $month . '-01 00:00:00';
        $endTime = date('Y-m-d 23:59:59', strtotime('last day of ' . $month));

        return $this->buildReport($startTime, $endTime, 'monthly', $month);
    }

    /**
     * 构建报告
     */
    protected function buildReport(string $startTime, string $endTime, string $type, string $label): array
    {
        $query = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime);

        // 总事件数
        $totalEvents = $query->count();

        // 按事件类型统计
        $byType = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->field('event_type, COUNT(*) as count')
            ->group('event_type')
            ->select()
            ->toArray();

        // 按严重级别统计
        $bySeverity = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->field('severity, COUNT(*) as count')
            ->group('severity')
            ->select()
            ->toArray();

        // 高危事件TOP10
        $topThreats = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('severity', '>=', 3)
            ->order('id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 攻击IP TOP10
        $topIps = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('severity', '>=', 2)
            ->field('ip, COUNT(*) as count, MAX(created_at) as last_time')
            ->group('ip')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 受攻击用户TOP10
        $topUsers = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('user_id', '>', 0)
            ->where('severity', '>=', 2)
            ->field('user_id, username, COUNT(*) as count, MAX(created_at) as last_time')
            ->group('user_id, username')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 合规检查
        $compliance = $this->runComplianceCheck($startTime, $endTime);

        return [
            'type'         => $type,
            'label'        => $label,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'total_events' => $totalEvents,
            'by_type'      => $byType,
            'by_severity'  => $bySeverity,
            'top_threats'  => $topThreats,
            'top_ips'      => $topIps,
            'top_users'    => $topUsers,
            'compliance'   => $compliance,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 合规检查
     */
    protected function runComplianceCheck(string $startTime, string $endTime): array
    {
        $checks = [];

        // 1. 检查是否有未处理的高危事件
        $highRiskCount = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('severity', 4)
            ->count();

        $checks[] = [
            'item'    => '高危事件处理',
            'status'  => $highRiskCount === 0 ? 'pass' : 'warn',
            'detail'  => $highRiskCount === 0 ? '无高危事件' : "发现{$highRiskCount}条严重事件",
        ];

        // 2. 检查登录失败锁定是否生效
        $loginFailCount = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('event_type', 'login_fail')
            ->count();

        $lockedCount = Db::name('member')
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', date('Y-m-d H:i:s'))
            ->count();

        $checks[] = [
            'item'    => '暴力破解防护',
            'status'  => ($loginFailCount > 0 && $lockedCount > 0) ? 'pass' : ($loginFailCount > 0 ? 'warn' : 'pass'),
            'detail'  => "登录失败{$loginFailCount}次, 锁定账号{$lockedCount}个",
        ];

        // 3. 检查SQL注入防护
        $sqliCount = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('event_type', 'sqli')
            ->count();

        $checks[] = [
            'item'    => 'SQL注入防护',
            'status'  => 'pass',
            'detail'  => "拦截SQL注入{$sqliCount}次",
        ];

        // 4. 检查XSS防护
        $xssCount = Db::name('security_log')
            ->whereBetweenTime('created_at', $startTime, $endTime)
            ->where('event_type', 'xss')
            ->count();

        $checks[] = [
            'item'    => 'XSS防护',
            'status'  => 'pass',
            'detail'  => "拦截XSS攻击{$xssCount}次",
        ];

        $passCount = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));

        return [
            'total'  => count($checks),
            'passed' => $passCount,
            'checks' => $checks,
            'score'  => round(($passCount / max(1, count($checks))) * 100),
        ];
    }
}
