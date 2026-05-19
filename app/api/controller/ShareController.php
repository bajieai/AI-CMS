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

use app\common\service\ShareTrackerService;
use think\facade\Request;

/**
 * 分享追踪API - V2.9.9
 * 公开接口，用于记录前台分享行为
 */
class ShareController extends BaseController
{
    /**
     * 记录分享事件（无需认证，游客可上报）
     */
    public function track()
    {
        $contentId = (int) Request::post('content_id', 0);
        $channel = Request::post('channel', '');
        $url = Request::post('url', '');

        $allowedChannels = ['wechat', 'weibo', 'qq', 'copy', 'other'];
        if (!in_array($channel, $allowedChannels, true)) {
            return json(['code' => 400, 'msg' => 'Invalid channel']);
        }

        try {
            ShareTrackerService::track($contentId, $channel);
            return json(['code' => 200, 'msg' => 'OK']);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'msg' => 'Server error']);
        }
    }
}
