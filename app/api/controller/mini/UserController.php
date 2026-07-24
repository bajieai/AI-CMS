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

namespace app\api\controller\mini;

use app\api\controller\BaseController;
use app\common\service\mini\MiniUserService;

/**
 * 小程序用户API
 */
class UserController extends BaseController
{
    protected MiniUserService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new MiniUserService();
    }

    /**
     * 统一JSON响应
     */
    protected function miniJson(mixed $data, string $message = 'success', int $code = 0): \think\Response
    {
        return json([
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'timestamp'  => time(),
            'request_id' => bin2hex(random_bytes(8)),
        ]);
    }

    protected function getUserId(): int
    {
        $input = json_decode($this->request->getInput(), true);
        return (int) ($input['mini_user_id'] ?? 0);
    }

    /**
     * 微信登录
     */
    public function login(): \think\Response
    {
        $code = $this->request->param('code', '');
        $result = $this->service->login($code);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 用户信息
     */
    public function info(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $result = $this->service->getUserInfo($userId);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 更新用户信息
     */
    public function update(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $data = $this->request->param();
        $result = $this->service->updateUserInfo($userId, $data);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 收藏列表
     */
    public function favorite(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $page = (int) $this->request->param('page', 1);
        $data = $this->service->getFavoriteList($userId, $page);
        return $this->miniJson($data);
    }

    /**
     * 添加收藏
     */
    public function favoriteAdd(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $contentId = (int) $this->request->param('content_id', 0);
        $result = $this->service->addFavorite($userId, $contentId);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 取消收藏
     */
    public function favoriteRemove(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $contentId = (int) $this->request->param('content_id', 0);
        $result = $this->service->removeFavorite($userId, $contentId);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 点赞
     */
    public function like(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $contentId = (int) $this->request->param('content_id', 0);
        $result = $this->service->toggleLike($userId, $contentId);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 发表评论
     */
    public function comment(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $contentId = (int) $this->request->param('content_id', 0);
        $content = $this->request->param('content', '');
        $result = $this->service->addComment($userId, $contentId, $content);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }

    /**
     * 评论列表
     */
    public function commentList(): \think\Response
    {
        $contentId = (int) $this->request->param('content_id', 0);
        $page = (int) $this->request->param('page', 1);
        $data = $this->service->getCommentList($contentId, $page);
        return $this->miniJson($data);
    }

    /**
     * 提交留言
     */
    public function message(): \think\Response
    {
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return $this->miniJson(null, '未登录', 401);
        }
        $data = $this->request->param();
        $result = $this->service->submitMessage($userId, $data);
        return $this->miniJson($result['data'] ?? null, $result['msg'] ?? 'error', $result['code']);
    }
}
