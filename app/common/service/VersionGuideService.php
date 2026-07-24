<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Member;
use think\facade\Cache;

/**
 * 版本升级引导页 — V2.9.33 OPS-4
 */
class VersionGuideService
{
    private const CACHE_TAG = 'version_guide';
    private const CURRENT_VERSION = 'v2.9.33';

    /**
     * 获取引导页内容
     */
    public function getGuideContent(): array
    {
        return Cache::remember('guide_content', function () {
            return [
                'version' => self::CURRENT_VERSION,
                'title'   => 'V2.9.33 升级指南',
                'features' => [
                    [
                        'name'        => 'AI内容质量评分',
                        'description' => '5维度智能评分引擎，自动检测内容质量并给出改进建议',
                        'icon'        => 'bi-star-half',
                        'url'         => '/admin/content_quality/dashboard',
                    ],
                    [
                        'name'        => 'AI内容修复管线',
                        'description' => '自动修复低分内容，检测→修复→验证闭环',
                        'icon'        => 'bi-wrench-adjustable',
                        'url'         => '/admin/content_quality/dashboard',
                    ],
                    [
                        'name'        => '模板推荐算法',
                        'description' => '基于用户行为的智能推荐，5种策略混合',
                        'icon'        => 'bi-stars',
                        'url'         => '/admin/template_store/index',
                    ],
                    [
                        'name'        => '模板促销引擎',
                        'description' => '限时折扣/满减/优惠券/捆绑销售/新用户专享',
                        'icon'        => 'bi-megaphone',
                        'url'         => '/admin/template_promotion_activity/index',
                    ],
                    [
                        'name'        => '开发者工具',
                        'description' => 'CLI打包工具+开发者控制台+API开放平台',
                        'icon'        => 'bi-code-square',
                        'url'         => '/admin/developer_console/dashboard',
                    ],
                ],
                'published_at' => date('Y-m-d'),
            ];
        }, 86400);
    }

    /**
     * 检查用户是否需要看引导
     */
    public function needsGuide(int $userId): bool
    {
        $key = 'guide_viewed_' . $userId . '_' . self::CURRENT_VERSION;
        return !Cache::get($key);
    }

    /**
     * 标记用户已查看
     */
    public function markViewed(int $userId): void
    {
        $key = 'guide_viewed_' . $userId . '_' . self::CURRENT_VERSION;
        Cache::set($key, 1, 86400 * 365); // 1年
    }

    /**
     * 引导页统计
     */
    public function getStats(): array
    {
        return [
            'total_views'    => 0,
            'completion_rate'=> 0,
            'feature_clicks' => [],
        ];
    }
}
