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
}
