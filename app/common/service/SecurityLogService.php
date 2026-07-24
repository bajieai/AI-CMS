<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;
use think\facade\Config;

/**
 * V2.9.35 SEC-6: 安全日志服务
 * 异步写入安全事件日志，不阻塞请求
 */
class SecurityLogService
{
    /**
     * 日志缓冲区（批量异步写入）
     */
    protected static array $buffer = [];

    /**
     * 写入安全日志
     */
    public function log(array $data, $request = null): void
    {
        $logData = array_merge([
            'event_type'  => $data['event_type'] ?? 'unknown',
            'severity'    => $data['severity'] ?? 1,
            'user_id'     => $data['user_id'] ?? 0,
            'username'    => $data['username'] ?? '',
            'ip'          => $data['ip'] ?? '',
            'user_agent'  => $data['user_agent'] ?? '',
            'url'         => $data['url'] ?? '',
            'method'      => $data['method'] ?? 'GET',
            'payload'     => $data['payload'] ?? '',
            'description' => $data['description'] ?? '',
            'extra'       => $data['extra'] ?? null,
        ], $this->extractRequestInfo($request));

        $config = Config::get('security.log', []);
        if (!empty($config['async'])) {
            // 异步模式：加入缓冲区，register_shutdown_function批量写入
            self::$buffer[] = $logData;
            if (count(self::$buffer) >= ($config['batch_size'] ?? 50)) {
                $this->flush();
            }
            // 确保请求结束时写入
            if (count(self::$buffer) === 1) {
                register_shutdown_function([$this, 'flush']);
            }
        } else {
            // 同步模式：直接写入
            $this->insertLog($logData);
        }

        // 严重事件告警
        if (!empty($config['alert']['enabled']) && $logData['severity'] >= ($config['alert']['min_severity'] ?? 3)) {
            $this->sendAlert($logData);
        }
    }

    /**
     * 批量写入缓冲区日志
     */
    public function flush(): void
    {
        if (empty(self::$buffer)) {
            return;
        }

        $batch = self::$buffer;
        self::$buffer = [];

        try {
            foreach (array_chunk($batch, 50) as $chunk) {
                $rows = array_map(function ($item) {
                    return [
                        'event_type'  => $item['event_type'],
                        'severity'    => $item['severity'],
                        'user_id'     => $item['user_id'],
                        'username'    => $item['username'],
                        'ip'          => $item['ip'],
                        'user_agent'  => mb_substr($item['user_agent'], 0, 512),
                        'url'         => mb_substr($item['url'], 0, 512),
                        'method'      => $item['method'],
                        'payload'     => $item['payload'],
                        'description' => mb_substr($item['description'], 0, 512),
                        'extra'       => is_array($item['extra']) ? json_encode($item['extra'], JSON_UNESCAPED_UNICODE) : $item['extra'],
                        'created_at'  => date('Y-m-d H:i:s'),
                    ];
                }, $chunk);

                Db::name('security_log')->insertAll($rows);
            }
        } catch (\Throwable) {
            // 日志写入失败不阻断业务流程
        }
    }

    /**
     * 从请求中提取信息
     */
    protected function extractRequestInfo($request): array
    {
        if ($request === null) {
            return [];
        }

        try {
            $userId = (int) session('user_id');
            $username = (string) (session('username') ?? '');
        } catch (\Throwable) {
            $userId = 0;
            $username = '';
        }

        return [
            'user_id'    => $userId,
            'username'   => $username,
            'ip'         => $request ? ($request->ip() ?? '') : '',
            'user_agent' => $request ? mb_substr((string) $request->header('user-agent', ''), 0, 512) : '',
            'url'        => $request ? $request->url(true) : '',
            'method'     => $request ? strtoupper($request->method()) : 'GET',
        ];
    }

    /**
     * 单条日志写入
     */
    protected function insertLog(array $data): void
    {
        try {
            Db::name('security_log')->insert([
                'event_type'  => $data['event_type'],
                'severity'    => $data['severity'],
                'user_id'     => $data['user_id'],
                'username'    => $data['username'],
                'ip'          => $data['ip'],
                'user_agent'  => mb_substr($data['user_agent'], 0, 512),
                'url'         => mb_substr($data['url'], 0, 512),
                'method'      => $data['method'],
                'payload'     => $data['payload'],
                'description' => mb_substr($data['description'], 0, 512),
                'extra'       => is_array($data['extra']) ? json_encode($data['extra'], JSON_UNESCAPED_UNICODE) : $data['extra'],
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // 忽略写入失败
        }
    }

    /**
     * 严重事件告警
     */
    protected function sendAlert(array $logData): void
    {
        $config = Config::get('security.log.alert', []);
        $channels = $config['channels'] ?? [];

        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        // 邮件告警（复用现有邮件服务）
                        // TODO: 调用MailService::send()
                        break;
                    case 'notification':
                        // 站内通知
                        // TODO: 调用NotificationService::send()
                        break;
                }
            } catch (\Throwable) {
                // 告警发送失败不影响主流程
            }
        }
    }

    /**
     * 查询日志列表（Controller调用方法）
     */
    public function getLogs(int $page = 1, int $pageSize = 20, array $filter = []): array
    {
        return $this->getList($filter, $page, $pageSize);
    }

    /**
     * 获取日志详情
     */
    public function getLogById(int $id): ?array
    {
        $log = Db::name('security_log')->where('id', $id)->find();
        return $log ?: null;
    }

    /**
     * 导出日志
     */
    public function exportLogs(array $filter = []): string
    {
        $result = $this->getList($filter, 1, 10000);
        $list = $result['list'] ?? [];

        $csv = "ID,事件类型,严重级别,用户ID,用户名,IP,URL,方法,描述,时间\n";
        foreach ($list as $row) {
            $csv .= implode(',', [
                $row['id'] ?? '',
                $row['event_type'] ?? '',
                $row['severity'] ?? '',
                $row['user_id'] ?? '',
                $row['username'] ?? '',
                $row['ip'] ?? '',
                $row['url'] ?? '',
                $row['method'] ?? '',
                $row['description'] ?? '',
                $row['created_at'] ?? '',
            ]) . "\n";
        }
        return $csv;
    }

    /**
     * 获取事件类型列表
     */
    public function getEventTypes(): array
    {
        return [
            'xss' => 'XSS攻击',
            'csrf' => 'CSRF攻击',
            'sqli' => 'SQL注入',
            'file_upload' => '文件上传',
            'auth_deny' => '权限拒绝',
            'login_fail' => '登录失败',
            'brute_force' => '暴力破解',
            'privilege_escalation' => '权限提升',
            'data_leak' => '数据泄露',
            'config_change' => '配置变更',
        ];
    }

    /**
     * 获取统计（按天数）
     */
    public function getStats(int $days = 7): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        return $this->getStatsByDate($startDate, $endDate);
    }

    /**
     * 查询日志列表
     */
    public function getList(array $filter = [], int $page = 1, int $pageSize = 20): array
    {
        $query = Db::name('security_log');

        if (!empty($filter['event_type'])) {
            $query->where('event_type', $filter['event_type']);
        }
        if (!empty($filter['severity'])) {
            $query->where('severity', '>=', $filter['severity']);
        }
        if (!empty($filter['ip'])) {
            $query->where('ip', $filter['ip']);
        }
        if (!empty($filter['user_id'])) {
            $query->where('user_id', $filter['user_id']);
        }
        if (!empty($filter['start_date'])) {
            $query->where('created_at', '>=', $filter['start_date']);
        }
        if (!empty($filter['end_date'])) {
            $query->where('created_at', '<=', $filter['end_date'] . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取统计数据（按日期范围）
     */
    public function getStatsByDate(string $startDate = '', string $endDate = ''): array
    {
        $query = Db::name('security_log');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // 按事件类型统计
        $byType = $query->field('event_type, COUNT(*) as count')
            ->group('event_type')
            ->select()
            ->toArray();

        // 按严重级别统计
        $bySeverity = Db::name('security_log')
            ->when($startDate, function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->where('created_at', '<=', $endDate . ' 23:59:59');
            })
            ->field('severity, COUNT(*) as count')
            ->group('severity')
            ->select()
            ->toArray();

        // 总数
        $total = $query->count();

        // 今日数量
        $todayCount = Db::name('security_log')
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        return [
            'total'       => $total,
            'today'       => $todayCount,
            'by_type'     => $byType,
            'by_severity' => $bySeverity,
        ];
    }
}
