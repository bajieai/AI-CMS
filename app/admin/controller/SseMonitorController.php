<?php
declare(strict_types=1);
namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\admin\SseMonitorService;
use think\facade\Cache;

class SseMonitorController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $data = SseMonitorService::getDashboard();
        $this->assign('data', $data);
        return $this->view('/sse_monitor_index');
    }

    public function refresh() { Cache::delete('sse_dashboard'); return $this->success('已刷新'); }

    public function cleanup()
    {
        $result = SseMonitorService::doCleanup();
        $this->recordLog('SSE清理', "消息:{$result['cleaned_messages']} 客户端:{$result['cleaned_clients']}");
        return $this->success('清理完成', $result);
    }

    public function detail()
    {
        $data = SseMonitorService::getDashboard();
        return $this->success('获取成功', $data);
    }
}
