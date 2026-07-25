<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\perf\RedisAdvancedService;
use think\facade\Json;

/**
 * Redis监控控制器
 * V2.9.38 PERF-II-3
 */
class RedisController extends AdminBaseController
{
    protected RedisAdvancedService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new RedisAdvancedService();
    }

    public function index()
    {
        $monitor = $this->service->getMonitorData();
        return $this->view('redis/index', ['monitor' => $monitor]);
    }

    public function testLock()
    {
        $token = $this->service->lock('test', 10);
        if ($token) {
            $this->service->unlock('test', $token);
            return Json::success('分布式锁测试成功', ['token' => $token]);
        }
        return Json::fail('获取锁失败');
    }

    public function testCounter()
    {
        $count = $this->service->incr('test_counter');
        return Json::success('ok', ['count' => $count]);
    }

    public function testRateLimit()
    {
        $allowed = $this->service->rateLimit('test_api', 10, 60);
        return Json::success('ok', ['allowed' => $allowed]);
    }
}
