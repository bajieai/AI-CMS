<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateReview;
use app\common\model\TemplateReviewReport;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板评价管理服务 — V2.9.28 M-2
 */
class TemplateReviewAdminService
{
    private const CACHE_TAG = 'template_review_admin';

    /**
     * 评价列表（支持多维度筛选）
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = TemplateReview::with(['store', 'member']);

        if (!empty($params['status']) && $params['status'] !== 'all') {
            $query->where('is_audited', (int)$params['status']);
        }
        if (!empty($params['keyword'])) {
            $query->where('content', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['store_id'])) {
            $query->where('store_id', (int)$params['store_id']);
        }
        if (!empty($params['rating'])) {
            $query->where('rating', (int)$params['rating']);
        }
        if (!empty($params['start_date'])) {
            $query->where('create_time', '>=', strtotime($params['start_date'] . ' 00:00:00'));
        }
        if (!empty($params['end_date'])) {
            $query->where('create_time', '<=', strtotime($params['end_date'] . ' 23:59:59'));
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 管理员回复评价
     */
    public function reply(int $reviewId, string $replyContent): array
    {
        $review = TemplateReview::find($reviewId);
        if (!$review) {
            return ['success' => false, 'message' => '评价不存在'];
        }

        $review->reply = $replyContent;
        $review->reply_time = time();
        $review->save();

        Cache::clear();
        return ['success' => true, 'message' => '回复成功'];
    }

    /**
     * 审核评价
     */
    public function audit(int $reviewId, int $status): array
    {
        $review = TemplateReview::find($reviewId);
        if (!$review) {
            return ['success' => false, 'message' => '评价不存在'];
        }

        $review->is_audited = $status;
        $review->save();

        // 更新模板评分统计
        $this->updateStoreRating($review->store_id);
        Cache::clear();
        return ['success' => true, 'message' => '审核成功'];
    }

    /**
     * 处理举报
     */
    public function handleReport(int $reportId, int $status, string $adminRemark = ''): array
    {
        $report = TemplateReviewReport::find($reportId);
        if (!$report) {
            return ['success' => false, 'message' => '举报记录不存在'];
        }

        $report->status = $status;
        $report->admin_remark = $adminRemark;
        $report->process_time = time();
        $report->save();

        // 如果通过举报，隐藏对应评价
        if ($status == TemplateReviewReport::STATUS_APPROVED) {
            $review = TemplateReview::find($report->review_id);
            if ($review) {
                $review->is_audited = TemplateReview::AUDIT_REJECT;
                $review->save();
                $this->updateStoreRating($review->store_id);
            }
        }

        Cache::clear();
        return ['success' => true, 'message' => '举报已处理'];
    }

    /**
     * 评价统计
     */
    public function getStats(int $storeId = 0): array
    {
        $cacheKey = 'review_stats_' . $storeId;
        return Cache::remember($cacheKey, function() use ($storeId) {
            $query = TemplateReview::where('is_audited', TemplateReview::AUDIT_PASS);
            if ($storeId > 0) {
                $query->where('store_id', $storeId);
            }

            $total = $query->count();
            $avgRating = $total > 0 ? (float)$query->avg('rating') : 0;

            // 评分分布
            $distribution = [];
            for ($i = 5; $i >= 1; $i--) {
                $count = (clone $query)->where('rating', $i)->count();
                $distribution[$i] = $count;
            }

            // 举报待处理数
            $pendingReports = TemplateReviewReport::where('status', TemplateReviewReport::STATUS_PENDING)->count();

            return [
                'total' => $total,
                'avg_rating' => round($avgRating, 1),
                'distribution' => $distribution,
                'pending_reports' => $pendingReports,
            ];
        }, 300);
    }

    /**
     * 更新模板评分统计
     */
    private function updateStoreRating(int $storeId): void
    {
        $stats = TemplateReview::where('store_id', $storeId)
            ->where('is_audited', TemplateReview::AUDIT_PASS)
            ->field('COUNT(*) as count, AVG(rating) as avg_rating')
            ->find();

        TemplateStore::where('id', $storeId)->update([
            'rating_avg' => $stats ? round((float)$stats['avg_rating'], 1) : 0,
            'rating_count' => $stats ? (int)$stats['count'] : 0,
        ]);
    }
}
