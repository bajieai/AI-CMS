<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Comment as CommentModel;
use think\facade\Cache;

/**
 * 评论服务
 */
class CommentService
{
    /**
     * 提交评论
     */
    public function submit(array $data): array
    {
        $comment = new CommentModel;
        $comment->save([
            'content_id' => $data['content_id'],
            'member_id'  => $data['member_id'] ?? 0,
            'nickname'   => $data['nickname'] ?? '游客',
            'email'      => $data['email'] ?? '',
            'content'    => strip_tags($data['content']),
            'parent_id'  => $data['parent_id'] ?? 0,
            'status'     => config('comment.comment_auto_approve') ? 1 : 0,
            'ip'         => request()->ip(),
        ]);

        return ['success' => true, 'msg' => '评论提交成功', 'data' => ['id' => $comment->id]];
    }

    /**
     * 获取内容评论列表
     */
    public function getList(int $contentId, int $status = 1, int $page = 1, int $limit = 10): array
    {
        $cacheKey = "comment_list_{$contentId}_{$status}_{$page}_{$limit}";
        return Cache::tag(CacheService::TAG_COMMENT)->remember($cacheKey, function () use ($contentId, $status, $page, $limit) {
            $list = CommentModel::where('content_id', $contentId)
                ->where('status', $status)
                ->where('parent_id', 0)
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();

            foreach ($list as &$item) {
                $item['replies'] = CommentModel::where('parent_id', $item['id'])
                    ->where('status', $status)
                    ->order('create_time', 'asc')
                    ->limit(5)
                    ->select();
            }

            return $list->toArray();
        });
    }

    /**
     * 审核评论
     */
    public function audit(int $commentId, int $status): array
    {
        $comment = CommentModel::find($commentId);
        if (!$comment) {
            return ['success' => false, 'msg' => '评论不存在'];
        }

        $oldStatus = $comment->status;
        $comment->status = $status;
        $comment->save();

        // 状态从非通过变为通过，或反向变化时，模型事件会自动维护计数
        // 但手动修改状态需要触发计数更新
        if ($oldStatus != $status) {
            if ($status == 1) {
                \think\facade\Db::name('content')->where('id', $comment->content_id)->inc('comment_count')->update();
            } elseif ($oldStatus == 1) {
                \think\facade\Db::name('content')->where('id', $comment->content_id)->dec('comment_count')->update();
            }
        }

        Cache::tag(CacheService::TAG_COMMENT)->clear();
        return ['success' => true, 'msg' => '审核操作成功'];
    }

    /**
     * 删除评论
     */
    public function delete(int $commentId): array
    {
        $comment = CommentModel::find($commentId);
        if (!$comment) {
            return ['success' => false, 'msg' => '评论不存在'];
        }

        // onAfterDelete会自动维护comment_count
        $comment->delete();
        Cache::tag(CacheService::TAG_COMMENT)->clear();
        return ['success' => true, 'msg' => '删除成功'];
    }

    /**
     * 批量删除评论
     */
    public function batchDelete(array $ids): array
    {
        $count = 0;
        foreach ($ids as $id) {
            $comment = CommentModel::find($id);
            if ($comment) {
                $comment->delete();
                $count++;
            }
        }
        Cache::tag(CacheService::TAG_COMMENT)->clear();
        return ['success' => true, 'msg' => "已删除{$count}条评论"];
    }
}