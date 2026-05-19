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

namespace app\common\service;

use app\common\model\Comment as CommentModel;
use app\common\model\Member;
use app\common\model\MemberLevel;
use think\facade\Cache;

/**
 * 评论服务 - V2.5增强
 * 新增：会员等级评论免审核(allow_comment_no_review)
 */
class CommentService
{
    /**
     * 提交评论
     * V2.5增强：会员等级allow_comment_no_review免审逻辑
     */
    public function submit(array $data): array
    {
        // V2.5：判断会员是否评论免审
        $autoApprove = (bool) config('comment.comment_auto_approve');
        $memberId = $data['member_id'] ?? 0;
        if ($memberId > 0 && !$autoApprove) {
            $autoApprove = $this->isMemberCommentNoReview($memberId);
        }

        $comment = new CommentModel;
        $comment->save([
            'content_id' => $data['content_id'],
            'member_id'  => $memberId,
            'nickname'   => $data['nickname'] ?? '游客',
            'email'      => $data['email'] ?? '',
            'content'    => strip_tags($data['content']),
            'parent_id'  => $data['parent_id'] ?? 0,
            'status'     => $autoApprove ? 1 : 0,
            'ip'         => request()->ip(),
        ]);

        $result = ['success' => true, 'msg' => '评论提交成功', 'data' => ['id' => $comment->id]];

        // V2.4: 评论积分奖励（仅会员）
        $memberId = $data['member_id'] ?? 0;
        if ($memberId > 0) {
            try {
                if (PointsService::checkDailyLimit('comment', $memberId)) {
                    $commentPoints = PointsService::getConfig('comment', 2);
                    if ($commentPoints > 0) {
                        PointsService::add($memberId, $commentPoints, 'comment', $comment->id, '评论积分');
                    }
                }
            } catch (\Throwable) {
                // 积分添加失败不影响评论流程
            }
        }

        return $result;
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

    /**
     * V2.5：检查会员是否拥有评论免审核权限
     * 根据会员等级的allow_comment_no_review字段判断
     */
    protected function isMemberCommentNoReview(int $memberId): bool
    {
        try {
            $member = Member::find($memberId);
            if (!$member || empty($member->level_id)) {
                return false;
            }
            $level = MemberLevel::find($member->level_id);
            return $level && !empty($level->allow_comment_no_review);
        } catch (\Throwable) {
            return false;
        }
    }
}