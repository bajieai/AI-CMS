<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\model\Media as MediaModel;
use think\Request;

/**
 * 媒体资源API
 */
class Media
{
    /**
     * 媒体列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);
        $filetype = $request->get('filetype', '');

        $query = MediaModel::order('id', 'desc');
        if ($filetype !== '') {
            $query->where('filetype', $filetype);
        }

        $list = $query->page($page, $limit)->select();
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }
}
