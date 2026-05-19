<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
        // AdminAuth 中间件已确保登录，此处只需检查权限
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
