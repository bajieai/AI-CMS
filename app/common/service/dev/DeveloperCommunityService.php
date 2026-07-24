<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 DEV-ECO-1: 开发者社区服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\dev;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 开发者社区服务 - V2.9.39 DEV-ECO-1
 * 复用content表实现问答/技术分享/文档/示例 + 积分系统
 */
class DeveloperCommunityService
{
    protected const CACHE_TAG = 'dev_community';
    protected const CACHE_TTL = 300;

    // 社区内容类型（复用content表的type字段）
    public const TYPE_QA          = 'dev_qa';        // 问答
    public const TYPE_TECH_SHARE  = 'dev_tech';      // 技术分享
    public const TYPE_DOC         = 'dev_doc';       // 文档
    public const TYPE_EXAMPLE     = 'dev_example';   // 示例代码

    // 积分规则
    public const POINTS = [
        'post_qa'        => 5,    // 发布问答
        'post_tech'      => 10,   // 发布技术分享
        'post_doc'       => 8,    // 发布文档
        'post_example'   => 10,   // 发布示例
        'answer_accepted'=> 20,   // 回答被采纳
        'answer_upvoted' => 2,    // 回答被点赞
        'post_downvoted' => -2,   // 内容被踩
    ];

    /**
     * 发布社区内容
     */
    public function createPost(int $userId, string $type, array $data): array
    {
        $validTypes = [self::TYPE_QA, self::TYPE_TECH_SHARE, self::TYPE_DOC, self::TYPE_EXAMPLE];
        if (!in_array($type, $validTypes, true)) {
            return ['success' => false, 'msg' => '无效的内容类型'];
        }

        try {
            $contentId = Db::name('content')->insertGetId([
                'title'       => $data['title'] ?? '',
                'content'     => $data['content'] ?? '',
                'type'        => $type,
                'user_id'     => $userId,
                'cate_id'     => $data['cate_id'] ?? 0,
                'status'      => 1,
                'is_dev_post' => 1,
                'tags'        => json_encode($data['tags'] ?? [], JSON_UNESCAPED_UNICODE),
                'extra_data'  => json_encode([
                    'code_snippet'  => $data['code_snippet'] ?? '',
                    'language'      => $data['language'] ?? '',
                    'difficulty'    => $data['difficulty'] ?? '',
                    'plugin_ref'    => $data['plugin_ref'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'view_count'  => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 记录开发者积分
            $this->addDevPoints($userId, $type);

            Cache::clear();

            return ['success' => true, 'content_id' => $contentId];
        } catch (\Throwable $e) {
            Log::error('[DevCommunity] 发布失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取社区内容列表
     */
    public function getPostList(array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $type = $params['type'] ?? null;
        $sort = $params['sort'] ?? 'latest';
        $keyword = $params['keyword'] ?? '';

        $query = Db::name('content')->where('is_dev_post', 1)->where('status', 1);

        if ($type) {
            $query->where('type', $type);
        }

        if (!empty($keyword)) {
            $query->where('title|content', 'like', "%{$keyword}%");
        }

        switch ($sort) {
            case 'hot':
                $query->order('view_count', 'desc');
                break;
            case 'discussed':
                $query->order('comment_count', 'desc');
                break;
            default:
                $query->order('create_time', 'desc');
        }

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,type,user_id,view_count,comment_count,like_count,create_time')
            ->select()
            ->toArray();

        // 关联作者信息
        if (!empty($list)) {
            $userIds = array_column($list, 'user_id');
            $users = Db::name('member')
                ->whereIn('id', $userIds)
                ->column('id,nickname,avatar', 'id');
            foreach ($list as &$item) {
                $item['author'] = $users[$item['user_id']] ?? null;
            }
        }

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 获取内容详情
     */
    public function getPostDetail(int $id): ?array
    {
        $post = Db::name('content')->where('id', $id)->where('is_dev_post', 1)->find();
        if (!$post) {
            return null;
        }

        $post['extra_data'] = json_decode($post['extra_data'] ?? '{}', true);
        $post['tags'] = json_decode($post['tags'] ?? '[]', true);

        // 增加浏览量
        Db::name('content')->where('id', $id)->inc('view_count')->update();

        // 获取作者信息
        $author = Db::name('member')->where('id', $post['user_id'])->find();
        $post['author'] = $author;

        // 获取回答/评论
        $post['answers'] = $this->getAnswers($id);

        return $post;
    }

    /**
     * 发布回答/评论
     */
    public function addAnswer(int $postId, int $userId, string $content, ?string $codeSnippet = ''): array
    {
        try {
            $answerId = Db::name('comment')->insertGetId([
                'content_id'  => $postId,
                'user_id'     => $userId,
                'content'     => $content,
                'extra_data'  => json_encode(['code_snippet' => $codeSnippet], JSON_UNESCAPED_UNICODE),
                'is_dev_answer' => 1,
                'status'      => 1,
                'create_time' => time(),
            ]);

            // 更新评论数
            Db::name('content')->where('id', $postId)->inc('comment_count')->update();

            Cache::clear();

            return ['success' => true, 'answer_id' => $answerId];
        } catch (\Throwable $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 采纳回答
     */
    public function acceptAnswer(int $answerId, int $userId): array
    {
        $answer = Db::name('comment')->where('id', $answerId)->where('is_dev_answer', 1)->find();
        if (!$answer) {
            return ['success' => false, 'msg' => '回答不存在'];
        }

        // 验证是否为提问者
        $post = Db::name('content')->where('id', $answer['content_id'])->find();
        if (!$post || (int) $post['user_id'] !== $userId) {
            return ['success' => false, 'msg' => '无权操作'];
        }

        // 标记为已采纳
        Db::name('comment')->where('id', $answerId)->update(['is_accepted' => 1, 'update_time' => time()]);

        // 奖励回答者积分
        $this->addPoints($answer['user_id'], self::POINTS['answer_accepted'], 'answer_accepted');

        Cache::clear();

        return ['success' => true];
    }

    /**
     * 点赞回答
     */
    public function upvoteAnswer(int $answerId, int $userId): array
    {
        $answer = Db::name('comment')->where('id', $answerId)->find();
        if (!$answer) {
            return ['success' => false, 'msg' => '回答不存在'];
        }

        if ((int) $answer['user_id'] === $userId) {
            return ['success' => false, 'msg' => '不能给自己的回答点赞'];
        }

        // 检查是否已点赞
        $existing = Db::name('member_like')
            ->where('user_id', $userId)
            ->where('target_type', 'dev_answer')
            ->where('target_id', $answerId)
            ->find();

        if ($existing) {
            return ['success' => false, 'msg' => '已点赞过'];
        }

        Db::name('member_like')->insert([
            'user_id'     => $userId,
            'target_type' => 'dev_answer',
            'target_id'   => $answerId,
            'create_time' => time(),
        ]);

        Db::name('comment')->where('id', $answerId)->inc('like_count')->update();
        $this->addPoints($answer['user_id'], self::POINTS['answer_upvoted'], 'answer_upvoted');

        Cache::clear();

        return ['success' => true];
    }

    /**
     * 获取回答列表
     */
    public function getAnswers(int $postId): array
    {
        $answers = Db::name('comment')
            ->where('content_id', $postId)
            ->where('is_dev_answer', 1)
            ->where('status', 1)
            ->order('is_accepted', 'desc')
            ->order('like_count', 'desc')
            ->order('create_time', 'asc')
            ->select()
            ->toArray();

        if (!empty($answers)) {
            $userIds = array_column($answers, 'user_id');
            $users = Db::name('member')->whereIn('id', $userIds)->column('id,nickname,avatar', 'id');
            foreach ($answers as &$answer) {
                $answer['author'] = $users[$answer['user_id']] ?? null;
                $answer['extra_data'] = json_decode($answer['extra_data'] ?? '{}', true);
            }
        }

        return $answers;
    }

    /**
     * 获取开发者排行榜
     */
    public function getLeaderboard(int $limit = 20): array
    {
        $cacheKey = 'dev_leaderboard_' . $limit;

        return Cache::remember($cacheKey, function () use ($limit) {
            return Db::name('dev_points_log')
                ->field('user_id, SUM(points) as total_points')
                ->group('user_id')
                ->order('total_points', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();
        }, self::CACHE_TTL);
    }

    /**
     * 获取社区统计
     */
    public function getStats(): array
    {
        return Cache::remember('dev_stats', function () {
            try {
                $totalPosts = Db::name('content')->where('is_dev_post', 1)->count();
                $totalAnswers = Db::name('comment')->where('is_dev_answer', 1)->count();
                $totalDevs = Db::name('dev_points_log')->distinct(true)->column('user_id');
                $acceptedCount = Db::name('comment')->where('is_dev_answer', 1)->where('is_accepted', 1)->count();

                $byType = Db::name('content')
                    ->where('is_dev_post', 1)
                    ->field('type, count(*) as count')
                    ->group('type')
                    ->select()
                    ->toArray();

                return [
                    'total_posts'   => $totalPosts,
                    'total_answers' => $totalAnswers,
                    'total_devs'    => count($totalDevs),
                    'accepted_count'=> $acceptedCount,
                    'by_type'       => $byType,
                ];
            } catch (\Throwable) {
                return [
                    'total_posts'    => 0,
                    'total_answers'  => 0,
                    'total_devs'     => 0,
                    'accepted_count' => 0,
                    'by_type'        => [],
                ];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 添加开发者积分
     */
    protected function addDevPoints(int $userId, string $type): void
    {
        $action = match ($type) {
            self::TYPE_QA         => 'post_qa',
            self::TYPE_TECH_SHARE => 'post_tech',
            self::TYPE_DOC        => 'post_doc',
            self::TYPE_EXAMPLE    => 'post_example',
            default               => null,
        };

        if ($action && isset(self::POINTS[$action])) {
            $this->addPoints($userId, self::POINTS[$action], $action);
        }
    }

    /**
     * 添加积分（通用）
     */
    protected function addPoints(int $userId, int $points, string $action): void
    {
        try {
            Db::name('dev_points_log')->insert([
                'user_id'     => $userId,
                'points'      => $points,
                'action'      => $action,
                'create_time' => time(),
            ]);

            // 更新开发者积分汇总
            $existing = Db::name('member')->where('id', $userId)->find();
            if ($existing) {
                Db::name('member')->where('id', $userId)->inc('dev_points', $points)->update();
            }
        } catch (\Throwable $e) {
            Log::error('[DevCommunity] 积分添加失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 获取管理后台内容列表
     */
    public function getAdminList(array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $type = $params['type'] ?? null;
        $status = $params['status'] ?? null;

        $query = Db::name('content')->where('is_dev_post', 1);

        if ($type) {
            $query->where('type', $type);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 审核社区内容
     */
    public function reviewPost(int $id, int $status, string $note = ''): array
    {
        $result = Db::name('content')->where('id', $id)->where('is_dev_post', 1)->update([
            'status'      => $status,
            'review_note' => $note,
            'update_time' => time(),
        ]);

        Cache::clear();

        return ['success' => $result > 0];
    }

    /**
     * 删除社区内容
     */
    public function deletePost(int $id): array
    {
        Db::name('content')->where('id', $id)->where('is_dev_post', 1)->update([
            'status'      => 0,
            'is_deleted'  => 1,
            'update_time' => time(),
        ]);

        Cache::clear();

        return ['success' => true];
    }
}
