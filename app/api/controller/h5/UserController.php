<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\facade\Cache;
use think\response\Json;

/**
 * H5用户接口
 */
class UserController extends H5BaseController
{
    /**
     * 用户登录
     */
    public function login(): Json
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');
        if (!$username || !$password) {
            return $this->error('用户名和密码不能为空');
        }
        $member = Db::name('member')->where('username', $username)->whereOr('email', $username)->whereOr('phone', $username)->find();
        if (!$member || !password_verify($password, $member['password'])) {
            return $this->error('用户名或密码错误');
        }
        if ($member['status'] != 1) {
            return $this->error('账号已被禁用');
        }
        $token = $this->generateToken($member['id']);
        return $this->success([
            'token' => $token,
            'member' => [
                'id' => $member['id'],
                'username' => $member['username'],
                'nickname' => $member['nickname'] ?? '',
                'avatar' => $member['avatar'] ?? '',
                'email' => $member['email'] ?? '',
            ],
        ]);
    }

    /**
     * 用户信息
     */
    public function info(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $member = Db::name('member')->where('id', $this->memberId)->field('id,username,nickname,avatar,email,phone,level,points,create_time')->find();
        if (!$member) {
            return $this->error('用户不存在');
        }
        $unreadCount = Db::name('notification')->where('member_id', $this->memberId)->where('is_read', 0)->count();
        $member['unread_count'] = $unreadCount;
        return $this->success($member);
    }

    /**
     * 用户收藏列表
     */
    public function favorites(): Json
    {
        if (!$this->memberId) {
            return $this->error('请先登录', 401);
        }
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $total = Db::name('favorite')->where('member_id', $this->memberId)->count();
        $list = Db::name('favorite')->alias('f')->join('content c', 'f.content_id = c.id')->where('f.member_id', $this->memberId)->where('c.status', 1)->order('f.create_time', 'desc')->page($page, $limit)->field('c.id,c.title,c.cover,c.description,f.create_time')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page]);
    }

    /**
     * 生成Token
     */
    protected function generateToken(int $memberId): string
    {
        $token = bin2hex(random_bytes(32));
        Cache::set('h5_token_' . $token, $memberId, 7200);
        Cache::set('h5_refresh_' . $memberId, $token, 604800);
        return $token;
    }
}
