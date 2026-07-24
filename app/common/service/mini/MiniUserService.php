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

namespace app\common\service\mini;

use app\common\model\Member;
use app\common\service\mini\MiniApiService;
use think\facade\Db;

/**
 * MINI-3 用户体系
 * 微信登录/用户信息/收藏/点赞/评论/留言
 */
class MiniUserService
{
    protected MiniApiService $apiService;

    public function __construct()
    {
        $this->apiService = new MiniApiService();
    }

    /**
     * 微信登录 (code -> openid -> 查找/创建用户 -> Token)
     */
    public function login(string $code): array
    {
        return $this->apiService->loginByCode($code);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo(int $userId): array
    {
        $member = Member::find($userId);
        if (!$member) {
            return ['code' => 1, 'msg' => '用户不存在', 'data' => null];
        }

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'id'             => (int) $member->id,
                'nickname'       => $member->nickname,
                'avatar'         => $member->wechat_avatar ?: '',
                'phone'          => $member->wechat_phone ?: '',
                'points'         => (int) ($member->points ?? 0),
                'level_id'       => (int) ($member->level_id ?? 0),
                'create_time'    => $member->create_time,
                'last_login_time' => $member->mini_login_time,
            ],
        ];
    }

    /**
     * 更新用户信息
     */
    public function updateUserInfo(int $userId, array $data): array
    {
        $member = Member::find($userId);
        if (!$member) {
            return ['code' => 1, 'msg' => '用户不存在', 'data' => null];
        }

        $allowedFields = ['nickname', 'wechat_avatar', 'wechat_nickname', 'wechat_phone'];
        $update = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }

        if (empty($update)) {
            return ['code' => 1, 'msg' => '无有效更新字段', 'data' => null];
        }

        $member->save($update);

        return ['code' => 0, 'msg' => '更新成功', 'data' => $update];
    }

    /**
     * 添加收藏
     */
    public function addFavorite(int $userId, int $contentId): array
    {
        $exists = Db::name('favorite')
            ->where('member_id', $userId)
            ->where('content_id', $contentId)
            ->find();

        if ($exists) {
            return ['code' => 0, 'msg' => '已收藏', 'data' => ['favorited' => true]];
        }

        Db::name('favorite')->insert([
            'member_id'  => $userId,
            'content_id' => $contentId,
            'create_time' => time(),
        ]);

        return ['code' => 0, 'msg' => '收藏成功', 'data' => ['favorited' => true]];
    }

    /**
     * 取消收藏
     */
    public function removeFavorite(int $userId, int $contentId): array
    {
        Db::name('favorite')
            ->where('member_id', $userId)
            ->where('content_id', $contentId)
            ->delete();

        return ['code' => 0, 'msg' => '取消收藏', 'data' => ['favorited' => false]];
    }

    /**
     * 收藏列表
     */
    public function getFavoriteList(int $userId, int $page = 1): array
    {
        $limit = 20;
        $total = Db::name('favorite')->where('member_id', $userId)->count();

        $list = Db::name('favorite')
            ->alias('f')
            ->join('content c', 'f.content_id = c.id')
            ->where('f.member_id', $userId)
            ->where('c.status', 1)
            ->field('c.id,c.title,c.thumb,c.views,f.create_time as fav_time')
            ->order('f.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'has_more' => ($page * $limit) < $total,
        ];
    }

    /**
     * 是否已收藏
     */
    public function isFavorited(int $userId, int $contentId): bool
    {
        return Db::name('favorite')
            ->where('member_id', $userId)
            ->where('content_id', $contentId)
            ->count() > 0;
    }

    /**
     * 点赞/取消点赞
     */
    public function toggleLike(int $userId, int $contentId): array
    {
        $exists = Db::name('like')
            ->where('member_id', $userId)
            ->where('content_id', $contentId)
            ->find();

        if ($exists) {
            Db::name('like')
                ->where('member_id', $userId)
                ->where('content_id', $contentId)
                ->delete();
            Db::name('content')->where('id', $contentId)->dec('likes')->update();
            return ['code' => 0, 'msg' => '取消点赞', 'data' => ['liked' => false]];
        }

        Db::name('like')->insert([
            'member_id'  => $userId,
            'content_id' => $contentId,
            'create_time' => time(),
        ]);
        Db::name('content')->where('id', $contentId)->inc('likes')->update();

        return ['code' => 0, 'msg' => '点赞成功', 'data' => ['liked' => true]];
    }

    /**
     * 点赞列表
     */
    public function getLikeList(int $userId, int $page = 1): array
    {
        $limit = 20;
        $total = Db::name('like')->where('member_id', $userId)->count();

        $list = Db::name('like')
            ->alias('l')
            ->join('content c', 'l.content_id = c.id')
            ->where('l.member_id', $userId)
            ->where('c.status', 1)
            ->field('c.id,c.title,c.thumb,l.create_time as like_time')
            ->order('l.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'has_more' => ($page * $limit) < $total,
        ];
    }

    /**
     * 添加评论
     */
    public function addComment(int $userId, int $contentId, string $content): array
    {
        if (empty($content)) {
            return ['code' => 1, 'msg' => '评论内容不能为空', 'data' => null];
        }

        $contentExists = Db::name('content')->where('id', $contentId)->where('status', 1)->find();
        if (!$contentExists) {
            return ['code' => 1, 'msg' => '内容不存在', 'data' => null];
        }

        $id = Db::name('comment')->insertGetId([
            'member_id'  => $userId,
            'content_id' => $contentId,
            'content'    => $content,
            'status'     => 1,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return ['code' => 0, 'msg' => '评论成功', 'data' => ['id' => $id]];
    }

    /**
     * 评论列表
     */
    public function getCommentList(int $contentId, int $page = 1): array
    {
        $limit = 20;
        $total = Db::name('comment')
            ->where('content_id', $contentId)
            ->where('status', 1)
            ->count();

        $list = Db::name('comment')
            ->alias('cm')
            ->join('member m', 'cm.member_id = m.id')
            ->where('cm.content_id', $contentId)
            ->where('cm.status', 1)
            ->field('cm.id,cm.content,cm.create_time,m.nickname,m.wechat_avatar')
            ->order('cm.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'has_more' => ($page * $limit) < $total,
        ];
    }

    /**
     * 提交留言
     */
    public function submitMessage(int $userId, array $data): array
    {
        if (empty($data['content'])) {
            return ['code' => 1, 'msg' => '留言内容不能为空', 'data' => null];
        }

        $id = Db::name('message')->insertGetId([
            'member_id'  => $userId,
            'name'       => $data['name'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'email'      => $data['email'] ?? '',
            'content'    => $data['content'],
            'status'     => 0,
            'create_time' => time(),
        ]);

        return ['code' => 0, 'msg' => '留言成功', 'data' => ['id' => $id]];
    }
}
