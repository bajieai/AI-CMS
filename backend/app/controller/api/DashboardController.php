<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 仪表盘控制器 - 提供聚合统计数据
 */
class DashboardController extends BaseController
{
    /**
     * 获取仪表盘统计数据
     */
    public function stats(): \think\Response
    {
        // 信息统计
        $totalContents = Db::name('articles')->count();
        $publishedContents = Db::name('articles')->where('status', '=', 2)->count();
        $draftContents = Db::name('articles')->where('status', '=', 0)->count();

        // 今日新增信息（基于created_at）
        $todayContents = Db::name('articles')
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        // 总浏览量
        $totalViews = (int)Db::name('articles')->sum('view_count');

        // AI使用统计
        $aiStats = $this->getAiUsageSummary();

        // 最近7天趋势
        $weeklyTrend = $this->getWeeklyContentTrend();

        // 分类分布
        $categoryDistribution = $this->getCategoryDistribution();

        // 媒体统计
        $totalMedia = Db::name('media')->count();

        return $this->success([
            'total_contents' => $totalContents,
            'published_contents' => $publishedContents,
            'draft_contents' => $draftContents,
            'today_new_contents' => $todayContents,
            'total_views' => $totalViews,
            'ai_usage_count' => $aiStats['task_count'] ?? 0,
            'ai_usage_cost' => $aiStats['total_cost'] ?? 0,
            'weekly_trend' => $weeklyTrend,
            'category_distribution' => $categoryDistribution,
        ]);
    }

    /**
     * 获取最近动态/操作日志
     */
    public function recentActivities(): \think\Response
    {
        $limit = (int) $this->request->param('limit', 10);

        $activities = Db::name('operation_logs')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        $result = array_map(function ($log) {
            return [
                'id' => $log['id'],
                'action' => $log['action'] ?? $log['description'] ?? '',
                'target' => $log['description'] ?? $log['content'] ?? '',
                'user' => $log['username'] ?? ($log['user_id'] ? "用户#{$log['user_id']}" : 'system'),
                'time' => $log['create_time'] ?? $log['created_at'] ?? '',
                'module' => $log['module'] ?? '',
            ];
        }, $activities);

        return $this->success($result);
    }

    /**
     * 获取AI使用汇总
     */
    protected function getAiUsageSummary(): array
    {
        try {
            return Db::name('ai_usage_stats')
                ->field('SUM(task_count) as task_count, SUM(total_cost) as total_cost, SUM(success_count) as success_count')
                ->find() ?: [];
        } catch (\Exception $e) {
            return ['task_count' => 0, 'total_cost' => 0];
        }
    }

    /**
     * 获取最近7天信息发布趋势
     */
    protected function getWeeklyContentTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayName = date('w', strtotime("-{$i} days"));
            $dayLabels = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];

            $count = Db::name('articles')
                ->where('created_at', '>=', $date . ' 00:00:00')
                ->where('created_at', '<=', $date . ' 23:59:59')
                ->count();

            $trend[] = [
                'date' => $date,
                'day' => $dayLabels[(int)$dayName],
                'count' => $count,
            ];
        }
        return $trend;
    }

    /**
     * 获取分类分布数据
     */
    protected function getCategoryDistribution(): array
    {
        try {
            // 由于数据库可能没有 content_count 列，简化查询
            $categories = Db::name('categories')
                ->field('id, name')
                ->where('status', '=', 1)
                ->order('id', 'desc')
                ->limit(8)
                ->select()
                ->toArray();

            return array_map(function ($cat) {
                return [
                    'name' => $cat['name'],
                    'value' => 0, // 暂不计算内容数量
                ];
            }, $categories);
        } catch (\Exception $e) {
            return [];
        }
    }
}
