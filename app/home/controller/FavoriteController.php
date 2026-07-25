<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Favorite;

/**
 * 收藏前台控制器 - V2.9.29 Sprint I-5
 */
class FavoriteController extends FrontBaseController
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

        $exists = Favorite::where('user_id', $userId)->where('content_id', $contentId)->find();
        if ($exists) {
            $exists->delete();
            return $this->success('已取消收藏', ['is_favorited' => false]);
        }
        Favorite::create(['user_id' => $userId, 'content_id' => $contentId]);
        return $this->success('收藏成功', ['is_favorited' => true]);
    }

    public function myFavorites()
    {
        $userId = $this->memberInfo['id'] ?? 0;
        $list = Favorite::with(['content'])->where('user_id', $userId)->order('id', 'desc')->paginate(15);
        $this->assign('list', $list);
        return $this->view('/my_favorites');
    }
}
