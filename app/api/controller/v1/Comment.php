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

namespace app\api\controller\v1;

use app\common\model\Comment as CommentModel;
use app\common\traits\ApiScopeCheck;
use think\Request;

class Comment
{
    use ApiScopeCheck;

    public function index(Request $request)
    {
        $this->requireScope('content:read');

        $contentId = (int) $request->get('content_id', 0);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 10);

        $list = CommentModel::where('content_id', $contentId)
            ->where('status', 1)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
    }

    public function save(Request $request)
    {
        $this->requireScope('content:write');

        $data = [
            'content_id' => (int) $request->post('content_id', 0),
            'member_id'  => 0,
            'nickname'   => $request->post('nickname', '游客'),
            'email'      => $request->post('email', ''),
            'content'    => strip_tags($request->post('content', '')),
            'parent_id'  => (int) $request->post('parent_id', 0),
            'status'     => config('comment.comment_auto_approve') ? 1 : 0,
            'ip'         => $request->ip(),
        ];

        if (empty($data['content'])) {
            return json(['code' => 1, 'msg' => '评论内容不能为空']);
        }

        $comment = new CommentModel;
        $comment->save($data);
        return json(['code' => 0, 'msg' => '提交成功', 'data' => ['id' => $comment->id]]);
    }
}
