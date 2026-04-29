<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\model\Content as ContentModel;
use think\Request;

/**
 * 内容API
 */
class Content
{
    /**
     * 内容列表
     */
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);
        $cateId = (int) $request->get('cate_id', 0);
        $type = (int) $request->get('type', 0);

        $query = ContentModel::where('status', 2);
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        if ($type > 0) {
            $query->where('type', $type);
        }

        $list = $query->order('create_time', 'desc')->page($page, $limit)->select();
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

    /**
     * 内容详情
     */
    public function read(int $id)
    {
        $content = ContentModel::with(['cate', 'tags'])->find($id);
        if (!$content || $content->status != 2) {
            return json(['code' => 404, 'msg' => '内容不存在'], 404);
        }
        return json(['code' => 0, 'msg' => 'success', 'data' => $content]);
    }
}