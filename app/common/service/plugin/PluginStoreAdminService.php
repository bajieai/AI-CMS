<?php

declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Db;
use think\facade\Cache;

/**
 * PLUG-SHOP-5: 插件商店后台服务 — V2.9.36
 */
class PluginStoreAdminService
{
    private const CACHE_TAG = 'plugin_store';

    /**
     * 总览
     */
    public function getOverview(): array
    {
        $pluginCount = Db::name('plugin')->count();
        $developerCount = Db::name('plugin')->where('developer_id', '>', 0)->distinct('developer_id')->count('developer_id');
        $orderCount = Db::name('plugin_order')->count();
        $totalRevenue = Db::name('plugin_order')->where('pay_status', 'paid')->sum('price');

        $pendingAudit = Db::name('plugin')->where('is_enabled', 2)->count();
        $pendingPayout = Db::name('plugin_payout')->where('payout_status', 'pending')->sum('developer_amount');

        return [
            'plugin_count'    => $pluginCount,
            'developer_count' => $developerCount,
            'order_count'     => $orderCount,
            'total_revenue'   => round((float) $totalRevenue, 2),
            'pending_audit'   => $pendingAudit,
            'pending_payout'  => round((float) $pendingPayout, 2),
        ];
    }

    /**
     * 管理插件列表
     */
    public function getPluginList(int $page = 1, array $filter = []): array
    {
        $query = Db::name('plugin');

        if (!empty($filter['keyword'])) {
            $kw = trim($filter['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('code', 'like', "%{$kw}%");
            });
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('is_enabled', (int) $filter['status']);
        }
        if (!empty($filter['category_id'])) {
            $query->where('category_id', (int) $filter['category_id']);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, 20)
            ->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 审核/上架/下架
     */
    public function auditPlugin(int $id, string $action): array
    {
        $plugin = Db::name('plugin')->find($id);
        if (!$plugin) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        $actionMap = [
            'approve'  => 1,  // 上架
            'reject'   => 0,  // 下架
            'pending'  => 2,  // 审核中
        ];

        if (!isset($actionMap[$action])) {
            return ['code' => 1, 'msg' => '无效操作'];
        }

        Db::name('plugin')->where('id', $id)->update([
            'is_enabled' => $actionMap[$action],
        ]);

        Cache::clear();

        $msgMap = ['approve' => '上架', 'reject' => '下架', 'pending' => '审核中'];
        return ['code' => 0, 'msg' => '已' . $msgMap[$action]];
    }

    /**
     * 设置精选
     */
    public function setFeatured(int $id, bool $featured): array
    {
        $plugin = Db::name('plugin')->find($id);
        if (!$plugin) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        Db::name('plugin')->where('id', $id)->update([
            'is_featured' => $featured ? 1 : 0,
        ]);

        Cache::clear();

        return ['code' => 0, 'msg' => $featured ? '已设为精选' : '已取消精选'];
    }

    /**
     * 订单统计
     */
    public function getOrderStats(): array
    {
        $total = Db::name('plugin_order')->count();
        $paid = Db::name('plugin_order')->where('pay_status', 'paid')->count();
        $pending = Db::name('plugin_order')->where('pay_status', 'pending')->count();
        $refunded = Db::name('plugin_order')->where('pay_status', 'refunded')->count();
        $revenue = Db::name('plugin_order')->where('pay_status', 'paid')->sum('price');

        return [
            'total'    => $total,
            'paid'     => $paid,
            'pending'  => $pending,
            'refunded' => $refunded,
            'revenue'  => round((float) $revenue, 2),
        ];
    }

    /**
     * 开发者列表
     */
    public function getDeveloperList(int $page = 1): array
    {
        $pageSize = 20;
        $query = Db::name('plugin')
            ->where('developer_id', '>', 0)
            ->field('developer_id, COUNT(*) as plugin_count, SUM(download_count) as total_downloads, SUM(install_count) as total_installs')
            ->group('developer_id');

        $total = $query->count();
        $list = $query->order('total_downloads', 'desc')
            ->page($page, $pageSize)
            ->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 评价管理列表
     */
    public function getRatingAdminList(int $page = 1): array
    {
        $pageSize = 20;
        $query = Db::name('plugin_rating')->order('id', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        // 关联插件名
        foreach ($list as &$item) {
            $plugin = Db::name('plugin')->field('name,code')->find($item['plugin_id']);
            $item['plugin_name'] = $plugin['name'] ?? '';
            $item['plugin_code'] = $plugin['code'] ?? '';
        }

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 趋势统计
     */
    public function getStoreStats(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        // 每日订单趋势
        $orderTrend = Db::name('plugin_order')
            ->where('create_time', '>=', $startDate)
            ->field("DATE(create_time) as date, COUNT(*) as count, SUM(CASE WHEN pay_status='paid' THEN price ELSE 0 END) as revenue")
            ->group('date')
            ->order('date', 'asc')
            ->select()->toArray();

        // 每日下载趋势
        $downloadTrend = Db::name('plugin')
            ->where('update_time', '>=', $startDate)
            ->field("DATE(update_time) as date, SUM(download_count) as downloads")
            ->group('date')
            ->order('date', 'asc')
            ->select()->toArray();

        // 热门插件 Top10
        $topPlugins = Db::name('plugin')
            ->field('id, name, code, download_count, install_count, rating, rating_count')
            ->order('download_count', 'desc')
            ->limit(10)
            ->select()->toArray();

        return [
            'order_trend'    => $orderTrend,
            'download_trend' => $downloadTrend,
            'top_plugins'    => $topPlugins,
            'days'           => $days,
        ];
    }
}
