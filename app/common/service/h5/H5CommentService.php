<?php
declare(strict_types=1);

namespace app\common\service\h5;

use think\facade\Db;
use think\facade\Cache;

/**
 * H5评论服务 - V2.9.40
 * 提供评论CRUD、点赞、频率限制、XSS过滤、敏感词过滤
 */
class H5CommentService
{
    /**
     * 默认敏感词列表（可扩展）
     */
    protected static array $sensitiveWords = [
        '广告', '色情', '赌博', '诈骗', '毒品', '枪支', '炸弹', '反动',
    ];

    /**
     * 频率限制：60秒内最多5条
     */
    protected const RATE_LIMIT_WINDOW = 60;
    protected const RATE_LIMIT_MAX = 5;

    /**
     * 获取评论列表
     *
     * @param int $contentId 内容ID
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string $sort 排序方式(latest/hottest)
     * @return array
     */
    public static function getComments(int $contentId, int $page = 1, int $pageSize = 10, string $sort = 'latest'): array
    {
        $cacheKey = 'h5_comments_' . $contentId . '_' . $sort . '_' . $page . '_' . $pageSize;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        $query = Db::name('comment')
            ->alias('c')
            ->leftJoin('member m', 'c.user_id = m.id')
            ->where('c.content_id', $contentId)
            ->where('c.status', 1)
            ->whereNull('c.deleted_at');
        if ($sort === 'hottest') {
            $query->order('c.likes', 'desc')->order('c.create_time', 'desc');
        } else {
            $query->order('c.create_time', 'desc');
        }
        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)
            ->field('c.id, c.content_id, c.parent_id, c.content, c.likes, c.create_time, c.user_id, m.username, m.nickname, m.avatar')
            ->select()
            ->toArray();
        // 加载每条评论的回复（仅一级回复）
        $commentIds = array_column($list, 'id');
        $replies = [];
        if (!empty($commentIds)) {
            $replyRows = Db::name('comment')
                ->alias('c')
                ->leftJoin('member m', 'c.user_id = m.id')
                ->whereIn('c.parent_id', $commentIds)
                ->where('c.status', 1)
                ->whereNull('c.deleted_at')
                ->order('c.create_time', 'asc')
                ->field('c.id, c.content_id, c.parent_id, c.content, c.likes, c.create_time, c.user_id, m.username, m.nickname, m.avatar')
                ->select()
                ->toArray();
            foreach ($replyRows as $reply) {
                $replies[$reply['parent_id']][] = $reply;
            }
        }
        foreach ($list as &$item) {
            $item['replies'] = $replies[$item['id']] ?? [];
            $item['reply_count'] = count($replies[$item['id']] ?? []);
        }
        unset($item);
        $result = [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'page_size' => $pageSize,
        ];
        Cache::set($cacheKey, $result, 300);
        return $result;
    }

    /**
     * 发表评论
     *
     * @param int $memberId 会员ID
     * @param int $contentId 内容ID
     * @param string $content 评论内容
     * @param int $parentId 父评论ID
     * @return array [success, msg, data]
     */
    public static function postComment(int $memberId, int $contentId, string $content, int $parentId = 0): array
    {
        if ($memberId <= 0) {
            return [false, '请先登录', null];
        }
        // 频率限制检查
        if (!self::checkRateLimit($memberId)) {
            return [false, '评论过于频繁，请稍后再试', null];
        }
        $content = trim($content);
        if ($content === '') {
            return [false, '评论内容不能为空', null];
        }
        if (mb_strlen($content) > 1000) {
            return [false, '评论内容不能超过1000字', null];
        }
        // XSS过滤
        $content = self::filterXSS($content);
        // 敏感词过滤
        if (self::containsSensitiveWords($content)) {
            return [false, '评论包含敏感内容，请修改后重试', null];
        }
        // 验证内容是否存在
        $contentRow = Db::name('content')->where('id', $contentId)->where('status', 1)->find();
        if (!$contentRow) {
            return [false, '内容不存在或已下架', null];
        }
        // 验证父评论
        if ($parentId > 0) {
            $parent = Db::name('comment')->where('id', $parentId)->where('content_id', $contentId)->where('status', 1)->find();
            if (!$parent) {
                return [false, '父评论不存在', null];
            }
        }
        $now = time();
        $commentId = Db::name('comment')->insertGetId([
            'content_id' => $contentId,
            'parent_id'  => $parentId,
            'user_id'    => $memberId,
            'content'    => $content,
            'likes'      => 0,
            'status'     => 1,
            'ip_address' => request()->ip(),
            'create_time' => $now,
        ]);
        // 更新内容评论数
        Db::name('content')->where('id', $contentId)->inc('comment_count')->update();
        // 清除缓存
        Cache::clear();
        // 记录频率
        self::recordRateLimit($memberId);
        return [true, '评论成功', [
            'id'          => $commentId,
            'content_id'  => $contentId,
            'parent_id'   => $parentId,
            'content'     => $content,
            'likes'       => 0,
            'create_time' => $now,
        ]];
    }

    /**
     * 点赞评论
     *
     * @param int $commentId 评论ID
     * @param int $memberId 会员ID
     * @return array [success, msg, data]
     */
    public static function likeComment(int $commentId, int $memberId): array
    {
        if ($memberId <= 0) {
            return [false, '请先登录', null];
        }
        $comment = Db::name('comment')->where('id', $commentId)->where('status', 1)->find();
        if (!$comment) {
            return [false, '评论不存在', null];
        }
        // 防重复点赞（简单实现：用缓存标记）
        $likeKey = 'h5_comment_like_' . $commentId . '_' . $memberId;
        if (Cache::get($likeKey)) {
            return [false, '您已点赞过此评论', null];
        }
        Db::name('comment')->where('id', $commentId)->inc('likes')->update();
        Cache::set($likeKey, 1, 86400 * 30); // 30天内不可重复点赞
        // 清除评论列表缓存
        Cache::clear();
        $newLikes = Db::name('comment')->where('id', $commentId)->value('likes');
        return [true, '点赞成功', ['likes' => (int)$newLikes]];
    }

    /**
     * 删除评论（仅作者或管理员可删除，软删除）
     *
     * @param int $commentId 评论ID
     * @param int $memberId 会员ID
     * @return bool
     */
    public static function deleteComment(int $commentId, int $memberId): bool
    {
        if ($memberId <= 0) {
            return false;
        }
        $comment = Db::name('comment')->where('id', $commentId)->find();
        if (!$comment) {
            return false;
        }
        // 仅作者可删除
        if ((int)$comment['user_id'] !== $memberId) {
            return false;
        }
        Db::name('comment')->where('id', $commentId)->update([
            'deleted_at' => time(),
        ]);
        // 减少内容评论数
        if ((int)$comment['status'] === 1) {
            Db::name('content')->where('id', $comment['content_id'])->where('comment_count', '>', 0)->dec('comment_count')->update();
        }
        // 清除缓存
        Cache::clear();
        return true;
    }

    /**
     * 频率限制检查
     *
     * @param int $memberId 会员ID
     * @return bool 是否允许发布
     */
    public static function checkRateLimit(int $memberId): bool
    {
        $key = 'h5_comment_rate_' . $memberId;
        $count = (int)Cache::get($key, 0);
        return $count < self::RATE_LIMIT_MAX;
    }

    /**
     * 记录频率限制
     *
     * @param int $memberId 会员ID
     * @return void
     */
    protected static function recordRateLimit(int $memberId): void
    {
        $key = 'h5_comment_rate_' . $memberId;
        $count = (int)Cache::get($key, 0);
        if ($count === 0) {
            Cache::set($key, 1, self::RATE_LIMIT_WINDOW);
        } else {
            Cache::inc($key);
        }
    }

    /**
     * XSS过滤
     *
     * @param string $content
     * @return string
     */
    protected static function filterXSS(string $content): string
    {
        $cleaned = strip_tags($content);
        return htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 敏感词检测
     *
     * @param string $content
     * @return bool
     */
    protected static function containsSensitiveWords(string $content): bool
    {
        foreach (self::$sensitiveWords as $word) {
            if (mb_strpos($content, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 添加自定义敏感词
     *
     * @param array $words
     * @return void
     */
    public static function addSensitiveWords(array $words): void
    {
        self::$sensitiveWords = array_unique(array_merge(self::$sensitiveWords, $words));
    }
}
