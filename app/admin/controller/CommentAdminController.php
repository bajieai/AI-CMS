<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Comment;

/**
 * 评论管理后台控制器 - V2.9.29 Sprint I-5
 */
class CommentAdminController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $status = $this->request->get('status', '');
        $query = Comment::with(['content', 'user'])->order('id', 'desc');
        if ($status !== '') $query->where('status', (int) $status);
        $list = $query->paginate(15);
        $this->assign('list', $list);
        $this->assign('status', $status);
        return $this->view('/comment_list');
    }

    public function audit(int $id = 0, int $status = 1)
    {
        Comment::where('id', $id)->update(['status' => $status]);
        return $this->success('操作成功');
    }

    public function delete(int $id = 0)
    {
        Comment::where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->success('已删除');
    }

    public function batchDelete()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $ids = $this->request->post('ids', []);
        if (!empty($ids)) {
            Comment::whereIn('id', $ids)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->success('批量删除成功');
    }
}
