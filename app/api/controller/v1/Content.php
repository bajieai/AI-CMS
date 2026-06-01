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

use app\api\middleware\ApiMemberAuth;
use app\common\middleware\PaidContentGuard;
use app\common\model\Content as ContentModel;
use app\common\service\PaidService;
use app\common\traits\ApiScopeCheck;
use think\Request;

/**
 * 内容API - V2.7安全加固：付费内容权限校验
 * @api_group V1-内容
 * @api_desc RESTful内容接口，支持列表、详情查询，付费内容权限控制
 */
class Content
{
    use ApiScopeCheck;

    /**
     * 内容列表
     * @api 内容列表
     * @api_desc 分页获取已发布内容，可按分类/类型筛选，付费内容不返回完整正文
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param int $cate_id 分类ID筛选
     * @param int $type 类型筛选(1产品/2案例/3新闻/4下载/5招聘/6单页)
     * @return json 返回内容列表（付费内容标记is_locked）
     * @api_auth yes (scope: content:read)
     */
    public function index(Request $request)
    {
        $this->requireScope('content:read');

        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 20);
        $cateId = (int) $request->get('cate_id', 0);
        $type = (int) $request->get('type', 0);

        $query = ContentModel::where('status', 2);
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        if ($type > 0) {
            $query->where('type', $type);
        }

        // V2.9.5 N+1优化：预加载分类
        $list = $query->with(['cate'])->order('create_time', 'desc')->page($page, $limit)->select();

        // V2.7: 从认证信息获取会员ID，GET参数member_id已弃用
        $memberId = ApiMemberAuth::getApiMemberId($request);
        foreach ($list as &$item) {
            if (!empty($item['is_paid'])) {
                $hasAccess = $memberId !== null && $memberId > 0 && PaidService::canAccess($memberId, (int) $item['id']);
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
     * @api 内容详情
     * @api_desc 获取内容完整信息，含付费权限校验，未授权仅返回预览内容
     * @param int $id 内容ID
     * @return json 返回内容详情（含付费状态/is_unlocked/price等）
     * @api_auth yes (scope: content:read)
     */
    public function read(Request $request, int $id)
    {
        $this->requireScope('content:read');

        $content = ContentModel::with(['cate', 'tags'])->find($id);
        if (!$content || $content->status != 2) {
            return json(['code' => 404, 'msg' => '内容不存在'], 404);
        }

        // V2.7: 二级防护校验（可选，主要逻辑仍在下方）
        $memberId = ApiMemberAuth::getApiMemberId($request);
        $guardResponse = PaidContentGuard::checkAccess($id, $memberId);
        if ($guardResponse && empty($content->is_free_chapter)) {
            // 如果完全无权限且不是免费试读，返回402；但保留预览模式让前端优雅展示
            // 这里继续执行以返回预览内容，真正的拦截由前端根据is_unlocked判断
        }

        $safe = PaidService::getSafeContent($content, $memberId);

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
