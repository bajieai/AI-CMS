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

use app\common\service\ShareTrackerService;
use think\facade\Request;

/**
 * 分享追踪API
 * @api_group 分享追踪
 * @api_desc 记录前台分享行为，用于分享统计和分析
 */
class ShareController extends BaseController
{
    /**
     * 记录分享事件
     * @api 分享追踪上报
     * @api_desc 记录前台用户的分享行为（无需认证），用于分享看板统计
     * @param int $content_id 内容ID
     * @param string $channel 分享渠道(wechat/weibo/qq/copy/other)
     * @param string $url 分享链接
     * @return json 返回上报结果
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
