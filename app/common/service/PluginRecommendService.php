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

use app\common\model\PluginPackage;
use app\common\model\PluginCategory;
use think\facade\Cache;

/**
 * 插件推荐服务 (V2.9.29 F-3)
 * 
 * 按分类/标签/用户共现推荐
 */
class PluginRecommendService
{
    private const CACHE_TAG = 'plugin_recommend';
    private const CACHE_TTL = 600;

    /**
     * 获取相关推荐插件
     * 
     * @param int $pluginId 插件ID
     * @param int $limit 返回数量
     * @return array
     */
    public function getRelatedPlugins(int $pluginId, int $limit = 5): array
    {
        return Cache::remember(
            'related_plugins_' . $pluginId . '_' . $limit,
            function () use ($pluginId, $limit) {
                // 1. 获取同分类的插件
                $plugin = PluginPackage::find($pluginId);
                if (!$plugin) {
                    return [];
                }

                $related = PluginPackage::where('id', '<>', $pluginId)
                    ->where('status', 1)
                    ->where('category_id', $plugin->category_id)
                    ->order('download_count', 'desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();

                // 2. 如果不够，补充热门插件
                if (count($related) < $limit) {
                    $excludeIds = array_merge([$pluginId], array_column($related, 'id'));
                    $extra = PluginPackage::where('id', 'not in', $excludeIds)
                        ->where('status', 1)
                        ->order('download_count', 'desc')
                        ->limit($limit - count($related))
                        ->select()
                        ->toArray();
                    $related = array_merge($related, $extra);
                }

                return $related;
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取评分分布
     */
    public function getRatingDistribution(int $pluginId): array
    {
        return Cache::remember(
            'rating_dist_' . $pluginId,
            function () use ($pluginId) {
                $dist = [];
                for ($star = 1; $star <= 5; $star++) {
                    $dist[$star] = \think\facade\Db::name('plugin_rating')
                        ->where('plugin_id', $pluginId)
                        ->where('rating', $star)
                        ->count();
                }
                return $dist;
            },
            self::CACHE_TTL
        );
    }

    /**
     * 检查兼容性
     */
    public function checkCompatibility(int $pluginId, string $cmsVersion): array
    {
        $plugin = PluginPackage::find($pluginId);
        if (!$plugin) {
            return ['compatible' => false, 'reason' => '插件不存在'];
        }

        // 解析兼容版本范围
        $minVersion = $plugin->min_version ?? '';
        $maxVersion = $plugin->max_version ?? '';

        $compatible = true;
        $reason = '';

        if ($minVersion && version_compare($cmsVersion, $minVersion, '<')) {
            $compatible = false;
            $reason = "当前CMS版本{$cmsVersion}低于最低要求{$minVersion}";
        }

        if ($maxVersion && version_compare($cmsVersion, $maxVersion, '>')) {
            $compatible = false;
            $reason = "当前CMS版本{$cmsVersion}高于最高支持{$maxVersion}";
        }

        return [
            'compatible' => $compatible,
            'reason' => $reason,
            'min_version' => $minVersion,
            'max_version' => $maxVersion,
            'current_version' => $cmsVersion,
        ];
    }
}
