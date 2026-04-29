<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\CommentService;
use think\Request;

/**
 * 前台评论控制器
 */
class CommentController extends FrontBaseController
{
    protected CommentService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new CommentService;
    }

    /**
     * 提交评论
     */
    public function submit(Request $request)
    {
        if (!config('comment.comment_enabled')) {
            return json(['success' => false, 'msg' => '评论功能已关闭']);
        }

        $data = [
            'content_id' => (int) $request->post('content_id', 0),
            'member_id'  => $this->memberInfo['id'] ?? 0,
            'nickname'   => $this->memberInfo['nickname'] ?? $request->post('nickname', '游客'),
            'email'      => $request->post('email', ''),
            'content'    => $request->post('content', ''),
            'parent_id'  => (int) $request->post('parent_id', 0),
        ];

        if (empty($data['content'])) {
            return json(['success' => false, 'msg' => '评论内容不能为空']);
        }

        $result = $this->service->submit($data);
        return json($result);
    }

    /**
     * 评论列表
     */
    public function list(Request $request)
    {
        $contentId = (int) $request->get('content_id', 0);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);

        $list = $this->service->getList($contentId, 1, $page, $limit);
        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }
}