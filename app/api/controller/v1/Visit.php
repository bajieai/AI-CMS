<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\service\VisitService;
use think\Request;

/**
 * PV统计API - V2.7 P0-6
 */
class Visit
{
    /**
     * 接收前端PV打点数据（公开接口，无需认证）
     * POST /api/v1/visit/pv
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
     * 获取热门内容（近7天）公开接口
     * GET /api/v1/visit/hot
     */
    public function hot(Request $request)
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $days  = min((int) $request->get('days', 7), 30);

        $list = VisitService::getHotContents($limit, $days);

        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }
}
