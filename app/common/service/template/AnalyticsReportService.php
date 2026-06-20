<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use app\common\model\TemplateRecommendStats;
use app\common\model\TemplateInstallLog;
use think\facade\Cache;
use think\facade\Db;

/**
 * 模板商店数据报表服务 — V2.9.26 P-7
 *
 * 提供商店运营数据看板：模板总量/安装趋势/分类分布/收入统计/Top模板
 */
class AnalyticsReportService
{
    public const CACHE_TAG = 'template_analytics';
    public const CACHE_TTL = 600;

    /**
     * 获取运营看板数据
     */
    public function getDashboard(int $days = 30): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('dashboard_' . $days, function () use ($days) {
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 模板总量
            $totalTemplates = TemplateStore::where('status', 1)->count();
            $publishedTemplates = TemplateStore::where('status', 1)->where('is_published', 1)->count();

            // 安装总量
            $totalInstalls = TemplateStore::sum('install_count');

            // 近N天安装趋势
            $installTrend = TemplateInstallLog::where('created_at', '>=', $startDate)
                ->field(['DATE(created_at) as date', 'COUNT(*) as count'])
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 分类分布
            $categoryDist = TemplateStore::where('status', 1)
                ->where('category_id', '>', 0)
                ->field(['category_id', 'COUNT(*) as count'])
                ->group('category_id')
                ->select()
                ->toArray();

            // Top 10 模板（按安装量）
            $topTemplates = TemplateStore::where('status', 1)
                ->where('is_published', 1)
                ->order('install_count', 'desc')
                ->limit(10)
                ->field(['id', 'name', 'install_count', 'rating', 'price'])
                ->select()
                ->toArray();

            // 收入统计（简化）
            $totalRevenue = TemplateStore::sum('install_count * price');

            return [
                'total_templates'     => $totalTemplates,
                'published_templates' => $publishedTemplates,
                'total_installs'      => $totalInstalls,
                'total_revenue'       => round((float)$totalRevenue, 2),
                'install_trend'       => $installTrend,
                'category_dist'       => $categoryDist,
                'top_templates'       => $topTemplates,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 获取分类分析报表
     */
    public function getCategoryReport(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('category_report', function () {
            $categories = TemplateStoreCategory::order('sort', 'asc')->select()->toArray();
            $report = [];
            foreach ($categories as $cat) {
                $templateCount = TemplateStore::where('category_id', $cat['id'])->where('status', 1)->count();
                $installCount = TemplateStore::where('category_id', $cat['id'])->sum('install_count');
                $report[] = [
                    'id'             => $cat['id'],
                    'name'           => $cat['name'],
                    'template_count' => $templateCount,
                    'install_count'  => $installCount,
                ];
            }
            return $report;
        }, self::CACHE_TTL);
    }

    /**
     * 导出报表数据（CSV格式）
     */
    public function exportCsv(string $type = 'dashboard'): string
    {
        switch ($type) {
            case 'category':
                $data = $this->getCategoryReport();
                $headers = ['分类ID', '分类名称', '模板数', '安装总量'];
                $rows = array_map(fn($r) => [$r['id'], $r['name'], $r['template_count'], $r['install_count']], $data);
                break;
            default:
                $data = $this->getDashboard(30);
                $headers = ['模板ID', '模板名称', '安装量', '评分', '价格'];
                $rows = array_map(fn($r) => [$r['id'], $r['name'], $r['install_count'], $r['rating'], $r['price']], $data['top_templates'] ?? []);
                break;
        }

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', $row) . "\n";
        }
        return "\xEF\xBB\xBF" . $csv; // BOM for Excel
    }
}
