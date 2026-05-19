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

use app\common\model\Content;
use app\common\model\Review;
use app\common\service\CacheService;
use think\facade\Config;

/**
 * 审核服务
 */
class ReviewService
{
    /**
     * 获取待审内容列表
     */
    public function getPendingList(array $params = [], int $pageSize = 20)
    {
        try {
            $query = Content::with('cate,user')->where('status', 1);

            if (!empty($params['type'])) {
                $query->where('type', (int) $params['type']);
            }
            if (!empty($params['keyword'])) {
                $query->where('title', 'like', '%' . $params['keyword'] . '%');
            }
            if (!empty($params['user_id'])) {
                $query->where('user_id', (int) $params['user_id']);
            }

            return $query->order('id', 'desc')->paginate($pageSize);
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                return new \think\Collection([]);
            }
            throw $e;
        }
    }

    /**
     * 获取审核历史
     */
    public function getHistory(int $contentId)
    {
        return Review::with('user')
            ->where('content_id', $contentId)
            ->order('id', 'desc')
            ->select();
    }

    /**
     * 审核通过
     */
    public function approve(int $contentId, string $remark = ''): bool
    {
        $content = Content::find($contentId);
        if (empty($content) || $content->status !== 1) {
            return false;
        }

        $content->status = 2;
        $content->update_time = time();
        $content->save();

        $this->recordReview($contentId, 'approve', $remark);

        // 清除缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        return true;
    }

    /**
     * 审核驳回
     */
    public function reject(int $contentId, string $remark = ''): bool
    {
        $content = Content::find($contentId);
        if (empty($content) || $content->status !== 1) {
            return false;
        }

        $content->status = 0; // 驳回后回退为草稿
        $content->update_time = time();
        $content->save();

        $this->recordReview($contentId, 'reject', $remark);

        return true;
    }

    /**
     * 记录审核操作
     */
    protected function recordReview(int $contentId, string $action, string $remark): void
    {
        $review = new Review();
        $review->content_id = $contentId;
        $review->user_id = session('user_id') ?: 0;
        $review->action = $action;
        $review->remark = $remark;
        $review->create_time = time();
        $review->save();
    }

    /**
     * 获取审核统计
     */
    public function getStats(): array
    {
        try {
            $total = Review::count();
            $approveCount = Review::where('action', 'approve')->count();
            $rejectCount = Review::where('action', 'reject')->count();
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                $total = $approveCount = $rejectCount = 0;
            } else {
                throw $e;
            }
        }

        return [
            'total' => $total,
            'approve' => $approveCount,
            'reject' => $rejectCount,
            'approve_rate' => $total > 0 ? round($approveCount / $total * 100, 2) : 0,
            'pending' => Content::where('status', 1)->count(),
        ];
    }
}
