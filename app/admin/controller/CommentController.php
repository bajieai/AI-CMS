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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Comment as CommentModel;
use app\common\service\CommentService;
use think\Request;

/**
 * 评论管理
 */
class CommentController extends AdminBaseController
{
    protected CommentService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new CommentService;
    }

    /**
     * 评论列表
     */
    public function index(Request $request)
    {
        $status = $request->get('status', '');
        $contentId = (int) $request->get('content_id', 0);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);

        $query = CommentModel::with(['content', 'member'])->order('create_time', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($contentId > 0) {
            $query->where('content_id', $contentId);
        }

        $list = $query->paginate($limit, false, ['page' => $page]);
        return $this->view('/comment_list', ['list' => $list, 'status' => $status]);
    }

    /**
     * 审核/拒绝
     */
    public function audit(Request $request)
    {
        $id = (int) $request->post('id', 0);
        $status = (int) $request->post('status', 1);

        $result = $this->service->audit($id, $status);
        return json($result);
    }

    /**
     * 删除评论
     */
    public function delete(Request $request)
    {
        $id = (int) $request->post('id', 0);
        $result = $this->service->delete($id);
        return json($result);
    }

    /**
     * 批量操作
     */
    public function batch(Request $request)
    {
        $ids = $request->post('ids', []);
        $action = $request->post('action', '');

        if (empty($ids) || !in_array($action, ['approve', 'reject', 'delete'])) {
            return json(['success' => false, 'msg' => '参数错误']);
        }

        $statusMap = ['approve' => 1, 'reject' => -1];

        foreach ($ids as $id) {
            if ($action === 'delete') {
                $this->service->delete((int) $id);
            } else {
                $this->service->audit((int) $id, $statusMap[$action]);
            }
        }

        return json(['success' => true, 'msg' => '批量操作成功']);
    }
}