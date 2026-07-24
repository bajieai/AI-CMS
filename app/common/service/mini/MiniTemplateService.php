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

namespace app\common\service\mini;

use app\common\model\MiniConfig;
use think\facade\Cache;

/**
 * MINI-4 模板适配
 * 页面组件配置/组件列表/页面渲染/主题配置
 */
class MiniTemplateService
{
    protected const TAG = 'mini_template';

    /**
     * 获取页面组件配置
     */
    public function getPageConfig(string $page = 'index'): array
    {
        $cacheKey = 'mini:page_config:' . $page;

        return Cache::remember($cacheKey, function () use ($page) {
            // 从mini_config读取页面配置JSON
            $configJson = MiniConfig::getValue('page_' . $page, '');
            if (empty($configJson)) {
                // 返回默认配置
                return $this->getDefaultPageConfig($page);
            }
            $config = json_decode($configJson, true);
            return is_array($config) ? $config : $this->getDefaultPageConfig($page);
        }, 1800);
    }

    /**
     * 保存页面配置
     */
    public function savePageConfig(string $page, array $config): array
    {
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        $key = 'page_' . $page;

        $item = MiniConfig::where('config_key', $key)->find();
        if ($item) {
            $item->config_value = $configJson;
            $item->save();
        } else {
            MiniConfig::create([
                'config_key'       => $key,
                'config_value'     => $configJson,
                'config_group'     => 'page',
                'config_description' => $page . '页面配置',
            ]);
        }

        Cache::clear();

        return ['code' => 0, 'msg' => '保存成功', 'data' => ['page' => $page]];
    }

    /**
     * 获取可用组件列表
     */
    public function getComponents(): array
    {
        return [
            ['name' => 'banner',    'title' => '轮播图',   'icon' => 'image'],
            ['name' => 'nav',       'title' => '导航宫格', 'icon' => 'grid'],
            ['name' => 'article',   'title' => '文章列表', 'icon' => 'list'],
            ['name' => 'product',   'title' => '产品列表', 'icon' => 'box'],
            ['name' => 'category',  'title' => '分类导航', 'icon' => 'folder'],
            ['name' => 'search',    'title' => '搜索栏',   'icon' => 'search'],
            ['name' => 'notice',    'title' => '公告通知', 'icon' => 'bell'],
            ['name' => 'tabbar',    'title' => '底部导航', 'icon' => 'menu'],
            ['name' => 'custom',    'title' => '自定义HTML', 'icon' => 'code'],
            ['name' => 'image',     'title' => '单张图片', 'icon' => 'photo'],
        ];
    }

    /**
     * 渲染页面数据 (适配小程序输出)
     */
    public function renderPage(string $page, array $data): array
    {
        $pageConfig = $this->getPageConfig($page);
        $themeConfig = $this->getThemeConfig();

        $components = [];
        foreach ($pageConfig['components'] ?? [] as $comp) {
            $compData = $data[$comp['name']] ?? [];
            $components[] = [
                'name'    => $comp['name'],
                'type'    => $comp['type'] ?? $comp['name'],
                'props'   => $comp['props'] ?? [],
                'data'    => $compData,
                'style'   => $comp['style'] ?? [],
            ];
        }

        return [
            'page'      => $page,
            'title'     => $pageConfig['title'] ?? $page,
            'components'=> $components,
            'theme'     => $themeConfig,
            'timestamp' => time(),
        ];
    }

    /**
     * 主题配置
     */
    public function getThemeConfig(): array
    {
        return Cache::remember('mini:theme_config', function () {
            return [
                'theme_color'     => MiniConfig::getValue('theme_color', '#0d6efd'),
                'background_color' => '#ffffff',
                'text_color'       => '#333333',
                'font_size'        => 14,
                'enable_comment'   => (int) MiniConfig::getValue('enable_comment', '1'),
                'enable_favorite'  => (int) MiniConfig::getValue('enable_favorite', '1'),
                'enable_like'      => (int) MiniConfig::getValue('enable_like', '1'),
            ];
        }, 1800);
    }

    /**
     * 默认页面配置
     */
    protected function getDefaultPageConfig(string $page): array
    {
        $configs = [
            'index' => [
                'title' => '首页',
                'components' => [
                    ['name' => 'banner',   'type' => 'banner',  'props' => []],
                    ['name' => 'nav',      'type' => 'nav',     'props' => []],
                    ['name' => 'article',  'type' => 'article', 'props' => ['limit' => 10]],
                ],
            ],
            'list' => [
                'title' => '列表页',
                'components' => [
                    ['name' => 'search',   'type' => 'search',  'props' => []],
                    ['name' => 'article',  'type' => 'article', 'props' => ['limit' => 20]],
                ],
            ],
            'user' => [
                'title' => '个人中心',
                'components' => [
                    ['name' => 'userinfo', 'type' => 'userinfo', 'props' => []],
                    ['name' => 'tabbar',   'type' => 'tabbar',   'props' => []],
                ],
            ],
        ];

        return $configs[$page] ?? ['title' => $page, 'components' => []];
    }
}
