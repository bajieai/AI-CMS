<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\model\Content as ContentModel;
use app\common\service\PaidService;
use app\common\traits\ApiScopeCheck;
use think\Request;

/**
 * 内容API - V2.6增强：付费内容权限校验
 */
class Content
{
    use ApiScopeCheck;

    /**
     * 内容列表
     * 付费内容不返回完整content字段，仅保留摘要
     */
    public function index(Request $request)
    {
        $this->requireScope('content:read');

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

        // 列表中移除付费内容的完整正文，防止绕过
        $memberId = (int) $request->get('member_id', 0);
        foreach ($list as &$item) {
            if (!empty($item['is_paid'])) {
                $hasAccess = $memberId > 0 && PaidService::canAccess($memberId, (int) $item['id']);
                if (!$hasAccess) {
                    $item['content'] = '';
                    $item['is_locked'] = true;
                } else {
                    $item['is_locked'] = false;
                }
            }
        }

        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

    /**
     * 内容详情
     * V2.6: 传入member_id校验付费权限，未授权返回安全预览版本
     */
    public function read(Request $request, int $id)
    {
        $this->requireScope('content:read');

        $content = ContentModel::with(['cate', 'tags'])->find($id);
        if (!$content || $content->status != 2) {
            return json(['code' => 404, 'msg' => '内容不存在'], 404);
        }

        $memberId = (int) $request->get('member_id', 0);
        $safe = PaidService::getSafeContent($content, $memberId > 0 ? $memberId : null);

        $data = $content->toArray();
        $data['content'] = $safe['full'] ?? $safe['preview'];
        $data['is_paid_content'] = $safe['is_paid_content'];
        $data['is_unlocked'] = $safe['is_unlocked'] ?? false;
        if (!empty($safe['price'])) {
            $data['price'] = $safe['price'];
        }
        if (!empty($safe['paid_type'])) {
            $data['paid_type'] = $safe['paid_type'];
        }

        return json(['code' => 0, 'msg' => 'success', 'data' => $data]);
    }
}
