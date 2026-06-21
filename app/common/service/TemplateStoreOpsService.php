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

namespace app\common\service;

use app\common\model\TemplateBanner;
use app\common\model\TemplateRecommend;
use app\common\model\TemplateInstallLog;
use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use think\facade\Cache;

/**
 * 模板商店运营服务 - V2.9.24 G-1~G-5
 * Banner管理 / 推荐位配置 / 统计看板
 */
class TemplateStoreOpsService
{
    // 缓存标签
    private const CACHE_TAG = 'template_store_ops';

    // ==================== G-1: Banner管理 ====================

    /**
     * 获取Banner列表（后台管理）
     */
    public function getBannerList(array $params = []): array
    {
        $query = TemplateBanner::order('sort', 'asc')->order('id', 'desc');

        if (!empty($params['status']) && $params['status'] !== 'all') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['keyword'])) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * 获取前台可用Banner（缓存）
     */
    public function getActiveBanners(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('active_banners', function () {
            return TemplateBanner::with(['targetTemplate', 'targetCategory'])
                ->active()
                ->select()
                ->toArray();
        }, 300);
    }

    /**
     * 保存Banner
     */
    public function saveBanner(array $data, int $id = 0): array
    {
        if (empty($data['title'])) {
            return ['success' => false, 'message' => '标题不能为空'];
        }
        if (empty($data['image'])) {
            return ['success' => false, 'message' => '请上传Banner图片'];
        }

        $data['sort'] = (int)($data['sort'] ?? 0);
        $data['status'] = (int)($data['status'] ?? 1);
        $data['target_type'] = (int)($data['target_type'] ?? TemplateBanner::TARGET_URL);
        $data['start_time'] = !empty($data['start_time']) ? strtotime($data['start_time']) : 0;
        $data['end_time'] = !empty($data['end_time']) ? strtotime($data['end_time']) : 0;

        if ($id > 0) {
            $banner = TemplateBanner::find($id);
            if (!$banner) {
                return ['success' => false, 'message' => 'Banner不存在'];
            }
            $banner->save($data);
        } else {
            TemplateBanner::create($data);
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '保存成功'];
    }

    /**
     * 删除Banner
     */
    public function deleteBanner(int $id): array
    {
        $banner = TemplateBanner::find($id);
        if (!$banner) {
            return ['success' => false, 'message' => 'Banner不存在'];
        }
        $banner->delete();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * 批量排序Banner
     */
    public function sortBanners(array $ids): array
    {
        foreach ($ids as $index => $id) {
            TemplateBanner::where('id', $id)->update(['sort' => $index + 1]);
        }
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '排序已更新'];
    }

    // ==================== G-2: 推荐位配置 ====================

    /**
     * 获取推荐位列表
     */
    public function getRecommendList(array $params = []): array
    {
        $query = TemplateRecommend::with('template')
            ->order('position', 'asc')
            ->order('sort', 'asc');

        if (!empty($params['position']) && $params['position'] !== 'all') {
            $query->where('position', (int)$params['position']);
        }
        if (!empty($params['status']) && $params['status'] !== 'all') {
            $query->where('status', (int)$params['status']);
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * 获取前台推荐位数据（缓存）
     */
    public function getRecommendByPosition(int $position, int $limit = 6): array
    {
        $cacheKey = "recommend_pos_{$position}_{$limit}";
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function () use ($position, $limit) {
            $list = TemplateRecommend::with('template')
                ->byPosition($position)
                ->active()
                ->limit($limit)
                ->select()
                ->toArray();

            // 自动推荐类型处理
            foreach ($list as &$item) {
                if ($item['recommend_type'] == TemplateRecommend::TYPE_AUTO_HOT && empty($item['template_id'])) {
                    $item['auto_templates'] = TemplateStore::online()
                        ->order('install_count', 'desc')
                        ->limit($limit)
                        ->select()
                        ->toArray();
                } elseif ($item['recommend_type'] == TemplateRecommend::TYPE_AUTO_NEW && empty($item['template_id'])) {
                    $item['auto_templates'] = TemplateStore::online()
                        ->order('create_time', 'desc')
                        ->limit($limit)
                        ->select()
                        ->toArray();
                }
            }

            return $list;
        }, 300);
    }

    /**
     * 保存推荐位
     */
    public function saveRecommend(array $data, int $id = 0): array
    {
        if (empty($data['position'])) {
            return ['success' => false, 'message' => '请选择推荐位置'];
        }

        $data['position'] = (int)$data['position'];
        $data['sort'] = (int)($data['sort'] ?? 0);
        $data['status'] = (int)($data['status'] ?? 1);
        $data['recommend_type'] = (int)($data['recommend_type'] ?? TemplateRecommend::TYPE_MANUAL);
        $data['template_id'] = !empty($data['template_id']) ? (int)$data['template_id'] : 0;
        $data['start_time'] = !empty($data['start_time']) ? strtotime($data['start_time']) : 0;
        $data['end_time'] = !empty($data['end_time']) ? strtotime($data['end_time']) : 0;

        if ($id > 0) {
            $rec = TemplateRecommend::find($id);
            if (!$rec) {
                return ['success' => false, 'message' => '推荐位不存在'];
            }
            $rec->save($data);
        } else {
            TemplateRecommend::create($data);
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '保存成功'];
    }

    /**
     * 删除推荐位
     */
    public function deleteRecommend(int $id): array
    {
        $rec = TemplateRecommend::find($id);
        if (!$rec) {
            return ['success' => false, 'message' => '推荐位不存在'];
        }
        $rec->delete();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    // ==================== G-4: 统计看板 ====================

    /**
     * 获取看板统计数据
     */
    public function getDashboardStats(string $startDate = '', string $endDate = ''): array
    {
        $start = $startDate ?: date('Y-m-d', strtotime('-30 days'));
        $end = $endDate ?: date('Y-m-d');
        $startTs = strtotime($start . ' 00:00:00');
        $endTs = strtotime($end . ' 23:59:59');

        // 安装趋势（N-1增强：含卸载量）
        $installTrend = TemplateInstallLog::whereBetween('create_time', [$startTs, $endTs])
            ->whereIn('action', [TemplateInstallLog::ACTION_INSTALL, TemplateInstallLog::ACTION_UNINSTALL])
            ->field("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') as date, action, COUNT(*) as count")
            ->group('date, action')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        // 整理为日期=>安装/卸载的格式
        $trendData = [];
        foreach ($installTrend as $row) {
            $date = $row['date'];
            if (!isset($trendData[$date])) {
                $trendData[$date] = ['date' => $date, 'count' => 0, 'uninstall_count' => 0, 'net' => 0];
            }
            if ($row['action'] == TemplateInstallLog::ACTION_INSTALL) {
                $trendData[$date]['count'] = $row['count'];
            } elseif ($row['action'] == TemplateInstallLog::ACTION_UNINSTALL) {
                $trendData[$date]['uninstall_count'] = $row['count'];
            }
        }
        foreach ($trendData as &$td) {
            $td['net'] = $td['count'] - $td['uninstall_count'];
        }
        $installTrend = array_values($trendData);

        // N-1: 安装下钻（按模板）
        $installByTemplate = TemplateInstallLog::whereBetween('create_time', [$startTs, $endTs])
            ->where('action', TemplateInstallLog::ACTION_INSTALL)
            ->field('template_id, COUNT(*) as count')
            ->group('template_id')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // N-1: 卸载分析（按模板）
        $uninstallByTemplate = TemplateInstallLog::whereBetween('create_time', [$startTs, $endTs])
            ->where('action', TemplateInstallLog::ACTION_UNINSTALL)
            ->field('template_id, COUNT(*) as count')
            ->group('template_id')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 热门模板TOP10
        $hotTemplates = TemplateStore::field('id, name, banner_url, install_count, rating_avg')
            ->order('install_count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        // 分类分布
        $categoryDist = TemplateStore::field('category_id, COUNT(*) as count')
            ->group('category_id')
            ->select()
            ->toArray();

        // 汇总数据
        $totalInstalls = TemplateInstallLog::where('action', TemplateInstallLog::ACTION_INSTALL)->count();
        $totalUninstalls = TemplateInstallLog::where('action', TemplateInstallLog::ACTION_UNINSTALL)->count();
        $totalTemplates = TemplateStore::count();
        $onlineTemplates = TemplateStore::where('status', TemplateStore::STATUS_ONLINE)->count();

        // 基线迁移数据（V2.9.24新增）
        $migrateCount = TemplateInstallLog::where('action', TemplateInstallLog::ACTION_MIGRATE)->count();

        // N-5: 7指标卡片数据
        $periodInstalls = TemplateInstallLog::whereBetween('create_time', [$startTs, $endTs])
            ->where('action', TemplateInstallLog::ACTION_INSTALL)->count();
        $periodUninstalls = TemplateInstallLog::whereBetween('create_time', [$startTs, $endTs])
            ->where('action', TemplateInstallLog::ACTION_UNINSTALL)->count();
        $retentionRate = $totalInstalls > 0 ? round((1 - $totalUninstalls / $totalInstalls) * 100, 1) : 100;

        return [
            'date_range' => ['start' => $start, 'end' => $end],
            'summary' => [
                'total_installs' => $totalInstalls,
                'total_uninstalls' => $totalUninstalls,
                'total_templates' => $totalTemplates,
                'online_templates' => $onlineTemplates,
                'migrate_count' => $migrateCount,
                'period_installs' => $periodInstalls,
                'period_uninstalls' => $periodUninstalls,
                'retention_rate' => $retentionRate,
            ],
            'install_trend' => $installTrend,
            'hot_templates' => $hotTemplates,
            'category_distribution' => $categoryDist,
            'install_by_template' => $installByTemplate,
            'uninstall_by_template' => $uninstallByTemplate,
        ];
    }

    /**
     * 导出CSV
     */
    public function exportCsv(array $params): string
    {
        $stats = $this->getDashboardStats($params['start'] ?? '', $params['end'] ?? '');
        $trend = $stats['install_trend'];

        $csv = "日期,安装量\n";
        foreach ($trend as $item) {
            $csv .= "{$item['date']},{$item['count']}\n";
        }

        return $csv;
    }

    /**
     * 基线迁移（V2.9.24 Q4方案C+A）
     * 将现有模板安装数据标记为基线
     */
    public function migrateBaseline(): array
    {
        $templates = TemplateStore::field('id, install_count')->select();
        $count = 0;

        foreach ($templates as $t) {
            if ($t->install_count > 0) {
                TemplateInstallLog::create([
                    'template_id' => $t->id,
                    'member_id' => 0,
                    'action' => TemplateInstallLog::ACTION_MIGRATE,
                    'source' => TemplateInstallLog::SOURCE_STORE,
                    'ip' => '',
                    'extra' => json_encode(['baseline_count' => $t->install_count]),
                    'create_time' => time(),
                ]);
                $count++;
            }
        }

        return ['success' => true, 'message' => "基线迁移完成，共 {$count} 条记录"];
    }

    // ==================== G-3: 分类管理 ====================

    /**
     * 获取分类列表（后台管理）
     */
    public function getCategoryList(array $params = []): array
    {
        $query = TemplateStoreCategory::order('sort', 'asc')->order('id', 'asc');

        if (isset($params['is_enabled']) && $params['is_enabled'] !== 'all') {
            $query->where('is_enabled', (int)$params['is_enabled']);
        }
        if (isset($params['is_visible']) && $params['is_visible'] !== 'all') {
            $query->where('is_visible', (int)$params['is_visible']);
        }
        if (!empty($params['keyword'])) {
            $query->where('name|slug', 'like', '%' . $params['keyword'] . '%');
        }

        $list = $query->select()->toArray();

        // 附加每个分类下的模板数量
        foreach ($list as &$item) {
            $item['template_count'] = TemplateStore::where('category_id', $item['id'])->count();
        }

        return ['list' => $list];
    }

    /**
     * 保存分类
     */
    public function saveCategory(array $data, int $id = 0): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'message' => '分类名称不能为空'];
        }
        if (empty($data['slug'])) {
            return ['success' => false, 'message' => '分类标识不能为空'];
        }

        // 检查 slug 唯一性
        $exists = TemplateStoreCategory::where('slug', $data['slug'])
            ->where('id', '<>', $id)
            ->find();
        if ($exists) {
            return ['success' => false, 'message' => '分类标识已存在'];
        }

        $data['sort'] = (int)($data['sort'] ?? 0);
        $data['is_enabled'] = (int)($data['is_enabled'] ?? 1);
        $data['is_visible'] = (int)($data['is_visible'] ?? 1);

        if ($id > 0) {
            $category = TemplateStoreCategory::find($id);
            if (!$category) {
                return ['success' => false, 'message' => '分类不存在'];
            }
            $category->save($data);
        } else {
            TemplateStoreCategory::create($data);
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '保存成功'];
    }

    /**
     * 删除分类
     */
    public function deleteCategory(int $id): array
    {
        $category = TemplateStoreCategory::find($id);
        if (!$category) {
            return ['success' => false, 'message' => '分类不存在'];
        }

        // 检查是否有模板关联
        $count = TemplateStore::where('category_id', $id)->count();
        if ($count > 0) {
            return ['success' => false, 'message' => "该分类下有 {$count} 个模板，请先转移模板"];
        }

        $category->delete();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * 批量排序分类
     */
    public function sortCategories(array $ids): array
    {
        foreach ($ids as $index => $id) {
            TemplateStoreCategory::where('id', $id)->update(['sort' => $index + 1]);
        }
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '排序已更新'];
    }

    /**
     * 切换分类可见性
     */
    public function toggleCategoryVisible(int $id): array
    {
        $category = TemplateStoreCategory::find($id);
        if (!$category) {
            return ['success' => false, 'message' => '分类不存在'];
        }
        $category->is_visible = $category->is_visible ? 0 : 1;
        $category->save();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '可见性已切换', 'is_visible' => $category->is_visible];
    }

    /**
     * 切换分类启用状态
     */
    public function toggleCategoryEnabled(int $id): array
    {
        $category = TemplateStoreCategory::find($id);
        if (!$category) {
            return ['success' => false, 'message' => '分类不存在'];
        }
        $category->is_enabled = $category->is_enabled ? 0 : 1;
        $category->save();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '状态已切换', 'is_enabled' => $category->is_enabled];
    }
}
