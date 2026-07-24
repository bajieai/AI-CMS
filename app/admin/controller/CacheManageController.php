<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\CacheStatsService;
use app\common\service\CachePrewarmService;
use think\App;
use think\facade\Cache;
use think\facade\View;

/**
 * V2.9.35 PERF-1: 缓存管理控制器
 */
class CacheManageController extends AdminBaseController
{
    protected CacheStatsService $statsService;
    protected CachePrewarmService $prewarmService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->statsService = new CacheStatsService();
        $this->prewarmService = new CachePrewarmService();
    }

    public function index()
    {
        $stats = $this->statsService->getStats();
        View::assign($stats);
        return $this->view('/cache_manage/index');
    }

    public function clear()
    {
        $tag = $this->request->post('tag', '');
        if ($tag === 'all') {
            Cache::clear();
        } elseif ($tag) {
            Cache::clear();
        }
        return json(['code' => 0, 'msg' => '缓存已清除']);
    }

    public function prewarm()
    {
        $module = $this->request->post('module', 'all');
        if ($module === 'all') {
            $result = $this->prewarmService->startupPrewarm();
        } else {
            $result = $this->prewarmService->prewarmModule($module);
        }
        return json(['code' => 0, 'msg' => '预热完成', 'data' => $result]);
    }

    public function stats()
    {
        $date = $this->request->get('date', '');
        $stats = $this->statsService->getStats($date);
        return json(['code' => 0, 'data' => $stats]);
    }
}
