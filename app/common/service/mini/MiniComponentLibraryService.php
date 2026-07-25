<?php
declare(strict_types=1);

namespace app\common\service\mini;

/**
 * 移动端模板组件库服务
 * V2.9.37 MINI-FULL-4
 */
class MiniComponentLibraryService
{
    /**
     * 组件分类列表
     */
    public function getCategories(): array
    {
        return [
            ['key' => 'display', 'name' => '展示组件', 'icon' => 'bi bi-image'],
            ['key' => 'navigation', 'name' => '导航组件', 'icon' => 'bi bi-menu-button'],
            ['key' => 'form', 'name' => '表单组件', 'icon' => 'bi bi-input-cursor-text'],
            ['key' => 'feedback', 'name' => '反馈组件', 'icon' => 'bi bi-info-circle'],
            ['key' => 'interaction', 'name' => '交互组件', 'icon' => 'bi bi-hand-index'],
            ['key' => 'business', 'name' => '业务组件', 'icon' => 'bi bi-building'],
        ];
    }

    /**
     * 获取组件列表
     */
    public function getComponents(string $category = ''): array
    {
        $all = $this->getAllComponents();
        if ($category && $category !== 'all') {
            return array_values(array_filter($all, fn($c) => $c['category'] === $category));
        }
        return $all;
    }

    /**
     * 获取组件详情
     */
    public function getComponentDetail(string $type): ?array
    {
        $all = $this->getAllComponents();
        foreach ($all as $c) {
            if ($c['type'] === $type) {
                return $c;
            }
        }
        return null;
    }

    /**
     * 获取预设模板
     */
    public function getTemplates(string $type = ''): array
    {
        return [
            'carousel_banner' => [
                'name' => 'Banner轮播', 'type' => 'carousel',
                'props' => ['autoplay' => true, 'interval' => 3000, 'indicator' => 'dots'],
            ],
            'carousel_card' => [
                'name' => '卡片轮播', 'type' => 'carousel',
                'props' => ['autoplay' => false, 'indicator' => 'none', 'style' => 'card'],
            ],
            'list_image_text' => [
                'name' => '图文列表', 'type' => 'content_list',
                'props' => ['style' => 'image_text', 'limit' => 10],
            ],
            'list_card' => [
                'name' => '卡片列表', 'type' => 'content_list',
                'props' => ['style' => 'card', 'limit' => 6],
            ],
            'list_waterfall' => [
                'name' => '瀑布流', 'type' => 'content_list',
                'props' => ['style' => 'waterfall', 'limit' => 12],
            ],
            'grid_2' => [
                'name' => '2列网格', 'type' => 'grid',
                'props' => ['columns' => 2, 'limit' => 4],
            ],
            'grid_3' => [
                'name' => '3列网格', 'type' => 'grid',
                'props' => ['columns' => 3, 'limit' => 6],
            ],
        ];
    }

    /**
     * 预览组件
     */
    public function previewComponent(string $type): array
    {
        $detail = $this->getComponentDetail($type);
        if (!$detail) {
            return [];
        }
        return [
            'component' => $detail,
            'preview_html' => $this->renderPreviewHtml($type),
        ];
    }

    /**
     * 所有内置组件定义
     */
    private function getAllComponents(): array
    {
        return [
            // 展示组件
            ['type' => 'carousel', 'name' => '轮播组件', 'category' => 'display',
             'props' => ['images' => ['type' => 'array', 'default' => []], 'autoplay' => ['type' => 'boolean', 'default' => true], 'interval' => ['type' => 'number', 'default' => 3000], 'indicator' => ['type' => 'string', 'default' => 'dots']]],
            ['type' => 'image', 'name' => '图片组件', 'category' => 'display',
             'props' => ['src' => ['type' => 'string', 'default' => ''], 'mode' => ['type' => 'string', 'default' => 'widthFix'], 'radius' => ['type' => 'number', 'default' => 0]]],
            ['type' => 'card', 'name' => '卡片组件', 'category' => 'display',
             'props' => ['title' => ['type' => 'string', 'default' => ''], 'content' => ['type' => 'string', 'default' => ''], 'image' => ['type' => 'string', 'default' => '']]],
            ['type' => 'content_list', 'name' => '内容列表', 'category' => 'display',
             'props' => ['model' => ['type' => 'string', 'default' => 'article'], 'limit' => ['type' => 'number', 'default' => 10], 'style' => ['type' => 'string', 'default' => 'list'], 'order' => ['type' => 'string', 'default' => 'sort']]],
            ['type' => 'grid', 'name' => '网格组件', 'category' => 'display',
             'props' => ['columns' => ['type' => 'number', 'default' => 3], 'model' => ['type' => 'string', 'default' => 'product'], 'limit' => ['type' => 'number', 'default' => 6]]],
            ['type' => 'waterfall', 'name' => '瀑布流', 'category' => 'display',
             'props' => ['model' => ['type' => 'string', 'default' => 'case'], 'limit' => ['type' => 'number', 'default' => 10]]],
            ['type' => 'tag_cloud', 'name' => '标签云', 'category' => 'display',
             'props' => ['limit' => ['type' => 'number', 'default' => 20]]],
            // 导航组件
            ['type' => 'top_nav', 'name' => '顶部导航', 'category' => 'navigation',
             'props' => ['title' => ['type' => 'string', 'default' => ''], 'back' => ['type' => 'boolean', 'default' => true]]],
            ['type' => 'bottom_tab', 'name' => '底部Tab', 'category' => 'navigation',
             'props' => ['tabs' => ['type' => 'array', 'default' => []]]],
            ['type' => 'breadcrumb', 'name' => '面包屑', 'category' => 'navigation',
             'props' => ['items' => ['type' => 'array', 'default' => []]]],
            ['type' => 'pagination', 'name' => '分页', 'category' => 'navigation',
             'props' => ['current' => ['type' => 'number', 'default' => 1], 'total' => ['type' => 'number', 'default' => 0]]],
            // 表单组件
            ['type' => 'search_box', 'name' => '搜索框', 'category' => 'form',
             'props' => ['placeholder' => ['type' => 'string', 'default' => '搜索...']]],
            ['type' => 'contact_form', 'name' => '留言表单', 'category' => 'form',
             'props' => ['fields' => ['type' => 'array', 'default' => ['name', 'phone', 'content']]]],
            ['type' => 'comment_box', 'name' => '评论框', 'category' => 'form',
             'props' => ['contentId' => ['type' => 'number', 'default' => 0]]],
            // 反馈组件
            ['type' => 'loading', 'name' => '加载中', 'category' => 'feedback',
             'props' => ['text' => ['type' => 'string', 'default' => '加载中...']]],
            ['type' => 'empty_state', 'name' => '空状态', 'category' => 'feedback',
             'props' => ['text' => ['type' => 'string', 'default' => '暂无数据'], 'icon' => ['type' => 'string', 'default' => 'empty']]],
            ['type' => 'error_state', 'name' => '错误状态', 'category' => 'feedback',
             'props' => ['text' => ['type' => 'string', 'default' => '加载失败']]],
            // 交互组件
            ['type' => 'back_to_top', 'name' => '返回顶部', 'category' => 'interaction',
             'props' => ['threshold' => ['type' => 'number', 'default' => 200]]],
            ['type' => 'share', 'name' => '分享', 'category' => 'interaction',
             'props' => ['title' => ['type' => 'string', 'default' => ''], 'image' => ['type' => 'string', 'default' => '']]],
            ['type' => 'contact', 'name' => '联系方式', 'category' => 'interaction',
             'props' => ['phone' => ['type' => 'string', 'default' => ''], 'address' => ['type' => 'string', 'default' => '']]],
            ['type' => 'map', 'name' => '地图', 'category' => 'interaction',
             'props' => ['latitude' => ['type' => 'number', 'default' => 0], 'longitude' => ['type' => 'number', 'default' => 0]]],
            // 业务组件
            ['type' => 'company_intro', 'name' => '企业介绍', 'category' => 'business',
             'props' => ['title' => ['type' => 'string', 'default' => '关于我们'], 'content' => ['type' => 'string', 'default' => '']]],
            ['type' => 'copyright', 'name' => '版权信息', 'category' => 'business',
             'props' => ['text' => ['type' => 'string', 'default' => ''], 'icp' => ['type' => 'string', 'default' => '']]],
            ['type' => 'ad_banner', 'name' => '广告位', 'category' => 'business',
             'props' => ['image' => ['type' => 'string', 'default' => ''], 'link' => ['type' => 'string', 'default' => '']]],
        ];
    }

    private function renderPreviewHtml(string $type): string
    {
        return '<div class="component-preview" data-type="' . $type . '"><div class="preview-placeholder">' . $type . ' 预览</div></div>';
    }
}
