<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SecurityConfigService;
use app\common\service\SecurityLogService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 SEC-1: 安全配置控制器
 */
class SecurityController extends AdminBaseController
{
    protected SecurityConfigService $configService;
    protected SecurityLogService $logService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->configService = new SecurityConfigService();
        $this->logService = new SecurityLogService();
    }

    /**
     * 安全配置页
     */
    public function index()
    {
        $config = $this->configService->getConfig();
        $stats = $this->logService->getStats();

        View::assign([
            'config' => $config,
            'stats'  => $stats,
        ]);

        return $this->view('/security/index');
    }

    /**
     * 保存安全配置
     */
    public function save()
    {
        $data = $this->request->post();

        $config = [
            'level' => $data['level'] ?? 'standard',
            'xss_input' => [
                'enabled' => !empty($data['xss_input_enabled']),
            ],
            'sql_injection' => [
                'enabled' => !empty($data['sql_injection_enabled']),
                'mode'    => $data['sql_injection_mode'] ?? 'block',
            ],
            'csrf' => [
                'bind_ip'  => !empty($data['csrf_bind_ip']),
                'token_ttl' => (int)($data['csrf_token_ttl'] ?? 1800),
            ],
            'password' => [
                'min_length'         => (int)($data['password_min_length'] ?? 8),
                'require_case'       => !empty($data['password_require_case']),
                'require_number'     => !empty($data['password_require_number']),
                'require_special'    => !empty($data['password_require_special']),
                'max_login_attempts' => (int)($data['password_max_login_attempts'] ?? 5),
                'lock_minutes'       => (int)($data['password_lock_minutes'] ?? 30),
            ],
            'log' => [
                'async' => !empty($data['log_async']),
                'alert' => [
                    'enabled'      => !empty($data['alert_enabled']),
                    'min_severity' => (int)($data['alert_min_severity'] ?? 3),
                ],
            ],
        ];

        $this->configService->saveConfig($config);

        return json(['code' => 0, 'msg' => '安全配置已保存']);
    }

    /**
     * 安全日志列表
     */
    public function log()
    {
        $page = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('page_size', 20);

        $filter = [
            'event_type'  => $this->request->get('event_type', ''),
            'severity'    => (int) $this->request->get('severity', 0),
            'ip'          => $this->request->get('ip', ''),
            'start_date'  => $this->request->get('start_date', ''),
            'end_date'    => $this->request->get('end_date', ''),
        ];

        $result = $this->logService->getList($filter, $page, $pageSize);

        View::assign($result);
        return $this->view('/security_log/index');
    }

    /**
     * 导出安全日志
     */
    public function exportLog()
    {
        $filter = [
            'event_type'  => $this->request->get('event_type', ''),
            'severity'    => (int) $this->request->get('severity', 0),
            'start_date'  => $this->request->get('start_date', ''),
            'end_date'    => $this->request->get('end_date', ''),
        ];

        $result = $this->logService->getList($filter, 1, 10000);

        $header = ['ID', '事件类型', '严重级别', '用户ID', '用户名', 'IP', 'URL', '方法', '描述', '时间'];
        $rows = [];
        foreach ($result['list'] as $item) {
            $rows[] = [
                $item['id'],
                $item['event_type'],
                $item['severity'],
                $item['user_id'],
                $item['username'],
                $item['ip'],
                $item['url'],
                $item['method'],
                $item['description'],
                $item['created_at'],
            ];
        }

        // CSV导出
        $filename = 'security_log_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        fputcsv($fp, $header);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}
