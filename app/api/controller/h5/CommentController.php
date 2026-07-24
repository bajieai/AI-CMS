<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\response\Json;
use app\common\service\h5\H5CommentService;

/**
 * H5评论控制器 - V2.9.40
 * 提供评论列表、发表、回复、点赞、删除
 */
class CommentController extends H5BaseController
{
    /**
     * GET评论列表（分页，支持排序：latest/hottest）
     */
    public function list(): Json
    {
        $contentId = (int)$this->request->param('contentId', 0);
        if ($contentId <= 0) {
            return $this->error('内容ID不能为空');
        }
        $page = (int)$this->request->param('page', 1);
        $pageSize = (int)$this->request->param('page_size', 10);
        $sort = $this->request->param('sort', 'latest');
        if (!in_array($sort, ['latest', 'hottest'])) {
            $sort = 'latest';
        }
        $result = H5CommentService::getComments($contentId, $page, $pageSize, $sort);
        return $this->success($result);
    }

    /**
     * POST发表评论
     */
    public function post(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $contentId = (int)$this->request->param('content_id', 0);
        $content = $this->request->param('content', '');
        $parentId = (int)$this->request->param('parent_id', 0);
        if ($contentId <= 0) {
            return $this->error('内容ID不能为空');
        }
        [$ok, $msg, $data] = H5CommentService::postComment($this->memberId, $contentId, $content, $parentId);
        if (!$ok) {
            return $this->error($msg);
        }
        return $this->success($data, $msg);
    }

    /**
     * POST回复评论
     */
    public function reply(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $parentId = (int)$this->request->route('id', 0);
        if ($parentId <= 0) {
            $parentId = (int)$this->request->param('id', 0);
        }
        if ($parentId <= 0) {
            return $this->error('父评论ID不能为空');
        }
        $contentId = (int)$this->request->param('content_id', 0);
        $content = $this->request->param('content', '');
        if ($contentId <= 0) {
            return $this->error('内容ID不能为空');
        }
        [$ok, $msg, $data] = H5CommentService::postComment($this->memberId, $contentId, $content, $parentId);
        if (!$ok) {
            return $this->error($msg);
        }
        return $this->success($data, $msg);
    }

    /**
     * POST点赞评论
     */
    public function like(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $commentId = (int)$this->request->route('id', 0);
        if ($commentId <= 0) {
            $commentId = (int)$this->request->param('id', 0);
        }
        if ($commentId <= 0) {
            return $this->error('评论ID不能为空');
        }
        [$ok, $msg, $data] = H5CommentService::likeComment($commentId, $this->memberId);
        if (!$ok) {
            return $this->error($msg);
        }
        return $this->success($data, $msg);
    }

    /**
     * DELETE删除评论
     */
    public function delete(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $commentId = (int)$this->request->route('id', 0);
        if ($commentId <= 0) {
            $commentId = (int)$this->request->param('id', 0);
        }
        if ($commentId <= 0) {
            return $this->error('评论ID不能为空');
        }
        $ok = H5CommentService::deleteComment($commentId, $this->memberId);
        if (!$ok) {
            return $this->error('删除失败，可能无权删除或评论不存在');
        }
        return $this->success(null, '删除成功');
    }
}
