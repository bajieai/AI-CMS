<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\CacheService;

/**
 * 缓存接口控制器
 */
class CacheController
{
    /**
     * 清除缓存
     * POST /api/cache/clear
     */
    public function clear()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        if ((int) session('role_id') !== 1) {
            return json(['code' => 3, 'msg' => '仅超级管理员可操作', 'data' => null]);
        }

        $service = new CacheService();
        if ($service->clearAll()) {
            return json(['code' => 0, 'msg' => '缓存清理成功', 'data' => null]);
        }
        return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
    }
}
