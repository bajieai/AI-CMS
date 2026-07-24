<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 移动端安装后配置引导 — V2.9.33 CUS3-3
 * 5步引导流程
 */
class MobileOnboardingService
{
    private const CACHE_TAG = 'mobile_onboarding';

    /**
     * 获取引导配置
     */
    public function getGuideConfig(): array
    {
        return Cache::remember('onboarding_config', function () {
            return [
                'enabled' => true,
                'steps' => [
                    [
                        'step' => 1,
                        'title' => '欢迎使用新模板',
                        'description' => '我们为您准备了快速配置引导，帮助您快速上手',
                        'type' => 'welcome',
                    ],
                    [
                        'step' => 2,
                        'title' => '选择配色方案',
                        'description' => '推荐3套配色方案，点击即可应用',
                        'type' => 'color',
                        'options' => [
                            ['name' => '经典蓝', 'primary' => '#0d6efd', 'secondary' => '#6c757d'],
                            ['name' => '活力橙', 'primary' => '#fd7e14', 'secondary' => '#ffc107'],
                            ['name' => '商务灰', 'primary' => '#495057', 'secondary' => '#adb5bd'],
                        ],
                    ],
                    [
                        'step' => 3,
                        'title' => '选择布局方案',
                        'description' => '推荐2种布局方案，点击即可应用',
                        'type' => 'layout',
                        'options' => [
                            ['name' => '单栏布局', 'value' => 'single'],
                            ['name' => '双栏布局', 'value' => 'double'],
                        ],
                    ],
                    [
                        'step' => 4,
                        'title' => '内容适配检测',
                        'description' => '自动检测内容适配情况，提示调整',
                        'type' => 'check',
                    ],
                    [
                        'step' => 5,
                        'title' => '配置完成',
                        'description' => '展示配置效果预览，点击开始使用',
                        'type' => 'complete',
                    ],
                ],
            ];
        }, 3600);
    }

    /**
     * 记录引导完成
     */
    public function completeGuide(int $memberId): array
    {
        $key = "onboarding_done_{$memberId}";
        Cache::set($key, true, 0);
        return ['success' => true];
    }

    /**
     * 检查是否已完成引导
     */
    public function hasCompleted(int $memberId): bool
    {
        return (bool) Cache::get("onboarding_done_{$memberId}");
    }
}
