<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateComponent;
use think\facade\Cache;

/**
 * 模板组件库 — V2.9.33 CUS3-4
 * 10种预置组件类型
 */
class TemplateComponentService
{
    private const CACHE_TAG = 'template_component';

    /** 预置断点 */
    public const BREAKPOINTS = [
        'mobile'  => ['name' => '手机', 'max_width' => 767],
        'tablet'  => ['name' => '平板', 'min_width' => 768, 'max_width' => 1023],
        'desktop' => ['name' => '桌面', 'min_width' => 1024],
    ];

    /**
     * 获取所有组件
     */
    public function getAll(string $type = ''): array
    {
        $this->initDefaultComponents();
        $query = TemplateComponent::where('status', 1);
        if (!empty($type)) $query->where('type', $type);
        return $query->order('is_system', 'desc')->order('sort', 'asc')->select()->toArray();
    }

    /**
     * 获取单个组件
     */
    public function get(int $id): ?array
    {
        return TemplateComponent::find($id)?->toArray();
    }

    /**
     * 初始化默认组件（6个核心 + 4个扩展 = 10个）
     */
    public function initDefaultComponents(): void
    {
        if (TemplateComponent::where('is_system', 1)->count() > 0) return;

        $components = [
            $this->makeComponent('导航栏', 'navbar', '<nav class="navbar">...</nav>', '.navbar{display:flex;justify-content:space-between;}'),
            $this->makeComponent('页脚', 'footer', '<footer class="footer">...</footer>', '.footer{padding:2rem 0;}'),
            $this->makeComponent('轮播图', 'carousel', '<div class="carousel">...</div>', '.carousel{overflow:hidden;}'),
            $this->makeComponent('产品卡片', 'card', '<div class="card">...</div>', '.card{border:1px solid #eee;border-radius:8px;}'),
            $this->makeComponent('按钮', 'button', '<button class="btn">点击</button>', '.btn{padding:.5rem 1.5rem;}'),
            $this->makeComponent('联系表单', 'form', '<form class="contact-form">...</form>', '.contact-form{max-width:500px;}'),
            $this->makeComponent('文章列表', 'list', '<ul class="article-list">...</ul>', '.article-list{list-style:none;}'),
            $this->makeComponent('社交图标', 'icon', '<div class="social-icons">...</div>', '.social-icons{display:flex;gap:1rem;}'),
            $this->makeComponent('分割线', 'divider', '<hr class="divider">', '.divider{border:none;border-top:1px solid #eee;}'),
            $this->makeComponent('标题', 'heading', '<h2 class="section-title">标题</h2>', '.section-title{font-size:1.5rem;}'),
        ];

        foreach ($components as $comp) {
            TemplateComponent::create($comp);
        }
        Cache::clear();
    }

    private function makeComponent(string $name, string $type, string $html, string $css): array
    {
        return [
            'name' => $name,
            'type' => $type,
            'description' => $name . '组件',
            'component_data' => json_encode(['html' => $html, 'css' => $css], JSON_UNESCAPED_UNICODE),
            'config_schema' => json_encode(['color' => ['type' => 'color', 'default' => '#333'], 'padding' => ['type' => 'text', 'default' => '1rem']], JSON_UNESCAPED_UNICODE),
            'version' => 'v1.0.0',
            'is_system' => 1,
            'status' => 1,
        ];
    }
}
