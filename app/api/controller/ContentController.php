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

        return json([
            'code' => 0,
            'msg'  => '计数成功',
            'data' => ['count' => (int) $content->download_count],
        ]);
    }
}
