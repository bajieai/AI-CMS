<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\hook\Hook;
use app\common\hook\HookEvents;
use app\common\hook\HookRegistry;

/**
 * V2.9.25 M-5: Hook 调试面板控制器
 */
class HookDebugController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 调试面板首页
     */
    public function index()
    {
        $events = HookEvents::getByModule();
        $registered = HookRegistry::getRegisteredEvents();
        $logs = HookRegistry::getDebugLogs(50);

        $this->assign([
            'events' => $events,
            'registered' => $registered,
            'logs' => $logs,
            'menuActive' => 'hook_debug',
        ]);
        return $this->view('/hook_debug/index');
    }

    /**
     * 切换调试模式
     */
    public function toggleDebug()
    {
        $enabled = (int) $this->request->post('enabled', 0);
        Hook::setDebugMode($enabled === 1);
        return json(['code' => 0, 'msg' => $enabled ? '调试模式已开启' : '调试模式已关闭']);
    }

    /**
     * 获取实时日志（AJAX轮询）
     */
    public function logs()
    {
        $limit = (int) $this->request->get('limit', 50);
        $logs = HookRegistry::getDebugLogs($limit);
        return json(['code' => 0, 'data' => $logs]);
    }

    /**
     * 清空日志
     */
    public function clearLogs()
    {
        HookRegistry::clearDebugLogs();
        return json(['code' => 0, 'msg' => '日志已清空']);
    }

    /**
     * 获取事件元数据
     */
    public function meta()
    {
        $event = $this->request->get('event', '');
        if ($event) {
            $meta = HookEvents::getMeta()[$event] ?? null;
            return json(['code' => 0, 'data' => $meta]);
        }
        return json(['code' => 0, 'data' => HookEvents::getMeta()]);
    }

    /**
     * 模拟触发事件（测试用）
     */
    public function testFire()
    {
        $event = $this->request->post('event', '');
        $data = $this->request->post('data', []);
        if (empty($event)) {
            return json(['code' => 1, 'msg' => '事件名不能为空']);
        }
        $result = Hook::fire($event, $data, ['module' => 'debug', 'ip' => $this->request->ip()]);
        return json([
            'code' => 0,
            'msg' => '触发完成',
            'data' => [
                'event' => $event,
                'stopped' => $result->stopped,
                'code' => $result->code,
                'message' => $result->message,
                'elapsed_ms' => round($result->elapsed, 2),
                'listener_count' => count($result->responses),
            ],
        ]);
    }

    // ==================== V2.9.28 H-6: 增强调试功能 ====================

    /**
     * 执行链详情（显示每个监听器的执行结果）
     */
    public function executionChain()
    {
        $event = $this->request->get('event', '');
        $logs = HookRegistry::getDebugLogs(200);
        $chain = [];
        foreach ($logs as $log) {
            if ($log['event'] === $event) {
                $chain[] = $log;
            }
        }
        return json(['code' => 0, 'data' => $chain]);
    }

    /**
     * 性能分析（各事件执行耗时统计）
     */
    public function performance()
    {
        $logs = HookRegistry::getDebugLogs(500);
        $stats = [];
        foreach ($logs as $log) {
            $event = $log['event'] ?? 'unknown';
            $elapsed = $log['elapsed'] ?? 0;
            if (!isset($stats[$event])) {
                $stats[$event] = ['count' => 0, 'total_ms' => 0, 'avg_ms' => 0, 'max_ms' => 0];
            }
            $stats[$event]['count']++;
            $stats[$event]['total_ms'] += $elapsed;
            $stats[$event]['max_ms'] = max($stats[$event]['max_ms'], $elapsed);
        }
        foreach ($stats as &$s) {
            $s['avg_ms'] = $s['count'] > 0 ? round($s['total_ms'] / $s['count'], 3) : 0;
        }

        $this->assign([
            'stats' => $stats,
            'menuActive' => 'hook_debug',
        ]);
        return $this->view('/hook_debug/performance');
    }

    /**
     * 监听器列表（所有已注册的监听器）
     */
    public function listeners()
    {
        $registered = HookRegistry::getRegisteredEvents();
        $allMeta = HookEvents::getMeta();

        $listeners = [];
        foreach ($registered as $event => $callbacks) {
            $meta = $allMeta[$event] ?? null;
            $listeners[] = [
                'event' => $event,
                'listener_count' => is_array($callbacks) ? count($callbacks) : 1,
                'since' => $meta['since'] ?? 'unknown',
                'description' => $meta['description'] ?? '',
            ];
        }

        $this->assign([
            'listeners' => $listeners,
            'menuActive' => 'hook_debug',
        ]);
        return $this->view('/hook_debug/listeners');
    }
}
