<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Log as LogModel;
use app\common\service\CacheService;

/**
 * 缓存接口控制器
 */
class CacheController
{
    /**
     * 清除全部缓存
     * POST /api/cache/clear
     */
    public function clear()
    {
        if ((int) session('role_id') !== 1) {
            return json(['code' => 3, 'msg' => '仅超级管理员可操作', 'data' => null]);
        }

        if (CacheService::clearAll()) {
            $this->recordCacheLog('清除全部缓存');
            return json(['code' => 0, 'msg' => '全部缓存清理成功', 'data' => null]);
        }
        return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
    }

    /**
     * 按类型清除缓存（V2.9.10 重组为5项：all/content/template/plugin/browser）
     * POST /api/cache/clearByType
     */
    public function clearByType()
    {
        if ((int) session('role_id') !== 1) {
            return json(['code' => 3, 'msg' => '仅超级管理员可操作', 'data' => null]);
        }

        $type = input('post.type', '');
        $groupNames = [
            'all'      => '全部',
            'content'  => '内容',
            'template' => '模板',
            'plugin'   => '插件',
            'browser'  => '浏览器',
        ];

        if (empty($type) || !isset($groupNames[$type])) {
            return json(['code' => 1, 'msg' => '无效的缓存类型', 'data' => null]);
        }

        // browser 纯前端处理，后端直接返回成功
        if ($type === 'browser') {
            $this->recordCacheLog('清除浏览器缓存');
            return json(['code' => 0, 'msg' => '浏览器缓存清理成功', 'data' => null]);
        }

        // all 走 clearAll
        if ($type === 'all') {
            if (CacheService::clearAll()) {
                $this->recordCacheLog('清除全部缓存');
                return json(['code' => 0, 'msg' => '全部缓存清理成功', 'data' => null]);
            }
            return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
        }

        // template 走 clearTemplate（含runtime编译缓存）
        if ($type === 'template') {
            if (CacheService::clearTemplate()) {
                $this->recordCacheLog('清除模板缓存');
                return json(['code' => 0, 'msg' => '模板缓存清理成功', 'data' => null]);
            }
            return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
        }

        // plugin 走 clearPlugin
        if ($type === 'plugin') {
            if (CacheService::clearPlugin()) {
                $this->recordCacheLog('清除插件缓存');
                return json(['code' => 0, 'msg' => '插件缓存清理成功', 'data' => null]);
            }
            return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
        }

        // content 走 clearByGroup
        if (CacheService::clearByGroup($type)) {
            $this->recordCacheLog('清除' . $groupNames[$type] . '缓存');
            return json(['code' => 0, 'msg' => $groupNames[$type] . '缓存清理成功', 'data' => null]);
        }
        return json(['code' => 4, 'msg' => '缓存清理失败', 'data' => null]);
    }

    /**
     * 记录缓存清除日志到 i8j_log 表
     */
    private function recordCacheLog(string $action): void
    {
        try {
            LogModel::create([
                'user_id' => (int) session('user_id'),
                'module'  => 'cache',
                'action'  => $action,
                'target'  => '',
                'ip'      => request()->ip(),
                'data'    => json_encode(['type' => input('post.type', 'all')]),
            ]);
        } catch (\Exception) {
            // 日志记录失败不阻断主流程
        }
    }
}
