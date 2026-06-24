<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Like;

/**
 * 点赞前台控制器 - V2.9.29 Sprint I-5
 */
class LikeController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function toggle()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $userId = $this->memberInfo['id'] ?? 0;
        if ($userId <= 0) return $this->error('请先登录', 1, ['login_url' => '/member/login']);
        $contentId = (int) $this->request->post('content_id', 0);

        $exists = Like::where('user_id', $userId)->where('content_id', $contentId)->find();
        if ($exists) {
            $exists->delete();
            return $this->success('已取消点赞', ['is_liked' => false]);
        }
        Like::create(['user_id' => $userId, 'content_id' => $contentId]);
        return $this->success('点赞成功', ['is_liked' => true]);
    }
}
