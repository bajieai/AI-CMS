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

namespace app\api\controller\v1;

use app\common\service\VisitService;
use think\Request;

/**
 * PV统计API
 * @api_group V1-数据统计
 * @api_desc PV打点、热门内容、分享追踪等统计接口
 */
class Visit
{
    /**
     * PV打点上报
     * @api PV打点上报
     * @api_desc 接收前端页面访问打点数据（无需认证，支持img标签埋点）
     * @param int $content_id 内容ID
     * @param string $page_url 页面URL
     * @param string $referrer 来源URL
     * @return json 返回上报结果
     */
    public function pv(Request $request)
    {
        $data = [
            'content_id' => (int) $request->post('content_id', 0),
            'visitor_id' => (int) $request->post('visitor_id', 0),
            'ip'         => $request->ip(),
            'ua'         => $request->header('User-Agent', ''),
            'page_url'   => $request->post('page_url', ''),
            'referrer'   => $request->post('referrer', ''),
        ];

        VisitService::track($data);

        // 返回1x1透明gif（兼容img标签埋点）
        if ($request->get('img') === '1') {
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            exit;
        }

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 热门内容
     * @api 热门内容
     * @api_desc 获取近N天PV最高的热门内容排行榜
     * @param int $limit 返回数量(默认10)
     * @param int $days 统计天数(默认7天)
     * @return json 返回热门内容列表
     */
    public function hot(Request $request)
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $days  = min((int) $request->get('days', 7), 30);

        $list = VisitService::getHotContents($limit, $days);

        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

    /**
     * 分享统计埋点
     * @api 分享追踪上报
     * @api_desc 记录前台分享行为数据（无需认证）
     * @param string $url 分享链接
     * @param string $channel 分享渠道
     * @return json 返回上报结果
     */
    public function trackShare(Request $request)
    {
        $data = [
            'content_id' => 0,
            'visitor_id' => 0,
            'ip'         => $request->ip(),
            'ua'         => $request->header('User-Agent', ''),
            'page_url'   => $request->post('url', ''),
            'referrer'   => '',
            'event_type' => 'share',
            'share_channel' => $request->post('channel', ''),
        ];

        VisitService::track($data);

        return json(['code' => 0, 'msg' => 'ok']);
    }
}
