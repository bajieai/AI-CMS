<?php
declare(strict_types=1);

namespace app\api\controller\mini;

use app\api\controller\BaseController;
use app\common\service\mini\MiniStatsService;

/**
 * 小程序/H5 统计API
 * V2.9.37 MINI-FULL-5
 */
class StatsController extends BaseController
{
    /**
     * 行为上报
     */
    public function track()
    {
        $data = $this->request->post();
        $type = $data['event_type'] ?? '';
        if (empty($type)) {
            return $this->success('缺少事件类型', [], 400);
        }
        $service = new MiniStatsService();
        $result = $service->recordEvent($type, $data);
        return $this->success('已记录', ['result' => $result]);
    }

    /**
     * 实时在线数
     */
    public function realtime()
    {
        $count = cache('mini_realtime_online') ?: 0;
        return $this->success('ok', ['online' => $count]);
    }
}
