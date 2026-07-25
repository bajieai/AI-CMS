<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Content;
use think\facade\Cache;

/**
 * V2.9.20 A-4: 内容API（下载计数等）
 */
class ContentController extends BaseController
{
    /**
     * 下载计数递增（防刷）
     * V2.9.21 BUG-2 修复：inc()后重新查询获取最新值
     */
    public function downloadCount()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }

        $content = Content::find($contentId);
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '内容不存在']);
        }

        // 防刷：同一IP同一内容24小时内只计1次
        $ip = $this->request->ip();
        $cacheKey = 'download_count:' . md5($ip . ':' . $contentId);
        if (Cache::has($cacheKey)) {
            // 已计数，仅返回当前总数
            return json([
                'code' => 0,
                'msg'  => '已计数',
                'data' => ['count' => (int) $content->download_count],
            ]);
        }

        // 递增计数
        $content->inc('download_count')->save();
        Cache::set($cacheKey, 1, 86400);

        // V2.9.21 BUG-2 修复：重新查询获取最新值
        $newCount = Content::where('id', $contentId)->value('download_count');

        return json([
            'code' => 0,
            'msg'  => '计数成功',
            'data' => ['count' => (int) $newCount],
        ]);
    }

    /**
     * V2.9.21 D-1: 播放量递增（防刷）
     * 复用 downloadCount() 防刷逻辑
     */
    public function playCount()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }

        $content = Content::find($contentId);
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '内容不存在']);
        }

        // 防刷：同一IP同一内容1小时内只计1次（播放场景频率更高，缩短窗口）
        $ip = $this->request->ip();
        $cacheKey = 'play_count:' . md5($ip . ':' . $contentId);
        if (Cache::has($cacheKey)) {
            return json([
                'code' => 0,
                'msg'  => '已计数',
                'data' => ['count' => (int) $content->play_count],
            ]);
        }

        // 递增计数
        $content->inc('play_count')->save();
        Cache::set($cacheKey, 1, 3600);

        // 重新查询获取最新值（避免 BUG-2 同类问题）
        $newCount = Content::where('id', $contentId)->value('play_count');

        return json([
            'code' => 0,
            'msg'  => '计数成功',
            'data' => ['count' => (int) $newCount],
        ]);
    }
}
