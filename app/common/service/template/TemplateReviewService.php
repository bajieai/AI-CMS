<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateInstall;
use think\facade\Db;
use think\facade\Cache;

/**
 * 模板评分评论Service - V2.9.32 T4-1
 */
class TemplateReviewService
{
    private const CACHE_TAG = 'template_review';

    public function submitReview(int $userId, int $templateId, array $data): array
    {
        $installed = TemplateInstall::where('store_id', $templateId)->where('member_id', $userId)->find();
        if (!$installed) return ['success' => false, 'message' => '您未安装该模板，无法评分'];

        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $exists = Db::table($prefix . 'template_review_v2')->where('user_id', $userId)->where('template_id', $templateId)->find();
        if ($exists) return ['success' => false, 'message' => '您已评分过此模板'];

        Db::table($prefix . 'template_review_v2')->insert([
            'user_id' => $userId, 'template_id' => $templateId,
            'rating_overall' => (int)($data['rating_overall'] ?? 0),
            'rating_ease' => (int)($data['rating_ease'] ?? 0),
            'rating_design' => (int)($data['rating_design'] ?? 0),
            'rating_feature' => (int)($data['rating_feature'] ?? 0),
            'rating_performance' => (int)($data['rating_performance'] ?? 0),
            'content' => $data['content'] ?? '',
            'images' => !empty($data['images']) ? json_encode($data['images'], JSON_UNESCAPED_UNICODE) : null,
            'status' => 0, 'create_time' => time(), 'update_time' => time(),
        ]);
        Cache::clear();
        return ['success' => true, 'message' => '评分提交成功，等待审核'];
    }

    public function getReviews(int $templateId, int $page = 1, int $limit = 10): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $list = Db::table($prefix . 'template_review_v2')->where('template_id', $templateId)->where('status', 1)->order('id', 'desc')->page($page, $limit)->select()->toArray();
        $total = Db::table($prefix . 'template_review_v2')->where('template_id', $templateId)->where('status', 1)->count();
        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function getRating(int $templateId): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $reviews = Db::table($prefix . 'template_review_v2')->where('template_id', $templateId)->where('status', 1)->select()->toArray();
        if (empty($reviews)) return ['avg' => 0, 'count' => 0, 'distribution' => []];
        $total = count($reviews);
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) { $distribution[$i] = count(array_filter($reviews, fn($r) => $r['rating_overall'] == $i)); }
        return [
            'avg' => round(array_sum(array_column($reviews, 'rating_overall')) / $total, 1),
            'count' => $total,
            'avg_ease' => round(array_sum(array_column($reviews, 'rating_ease')) / $total, 1),
            'avg_design' => round(array_sum(array_column($reviews, 'rating_design')) / $total, 1),
            'avg_feature' => round(array_sum(array_column($reviews, 'rating_feature')) / $total, 1),
            'avg_performance' => round(array_sum(array_column($reviews, 'rating_performance')) / $total, 1),
            'distribution' => $distribution,
        ];
    }

    public function auditReview(int $reviewId, bool $approve): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        Db::table($prefix . 'template_review_v2')->where('id', $reviewId)->update(['status' => $approve ? 1 : 2, 'audit_time' => time(), 'update_time' => time()]);
        Cache::clear();
        // 更新模板平均评分
        if ($approve) { $review = Db::table($prefix . 'template_review_v2')->find($reviewId); if ($review) $this->updateTemplateRating($review['template_id']); }
        return ['success' => true, 'message' => $approve ? '已通过' : '已驳回'];
    }

    public function likeReview(int $reviewId): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        Db::table($prefix . 'template_review_v2')->where('id', $reviewId)->inc('likes')->update();
        Cache::clear();
        return ['success' => true, 'message' => '点赞成功'];
    }

    public function updateTemplateRating(int $templateId): void
    {
        $rating = $this->getRating($templateId);
        TemplateStore::where('id', $templateId)->update(['avg_rating' => $rating['avg'], 'review_count' => $rating['count']]);
    }
}
