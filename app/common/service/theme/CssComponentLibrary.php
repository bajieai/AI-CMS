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

namespace app\common\service\theme;

/**
 * V2.9.8 A-2: CSS组件模式规则库
 *
 * 独立封装，与AiThemeGenerateService分离
 * 每个组件模式为一个独立方法，按行业选择子集
 * 评分器双线制：新模板65分/历史模板60分
 */
class CssComponentLibrary
{
    /**
     * V2.9.9 A-1/A-4: 行业→组件映射（8行业）
     */
    protected const INDUSTRY_COMPONENTS = [
        'corporate' => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'footer'],
        'ecommerce' => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'price', 'footer'],
        'blog'      => ['vars', 'card', 'button', 'nav', 'grid', 'spacing', 'article', 'footer'],
        'portal'    => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'footer'],
        'medical'   => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'footer', 'sidebar'],
        'education' => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'footer', 'header'],
        'catering'  => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'price', 'footer'],
        'finance'   => ['vars', 'hero', 'card', 'button', 'nav', 'grid', 'spacing', 'footer', 'header'],
    ];

    /**
     * 获取某行业的组件名称列表（供Prompt动态构建使用）
     */
    public function getComponentNamesForIndustry(string $industry): array
    {
        $components = self::INDUSTRY_COMPONENTS[$industry] ?? self::INDUSTRY_COMPONENTS['corporate'];
        $names = [];
        $labelMap = [
            'vars'    => 'CSS变量体系',
            'hero'    => 'Hero全宽渐变区',
            'card'    => '卡片组件',
            'button'  => '按钮系统',
            'nav'     => '固定导航栏',
            'grid'    => '响应式网格',
            'spacing' => '间距体系',
            'footer'  => '多列页脚',
            'price'   => '价格/促销标签',
            'article' => '文章/博客组件',
            'header'  => '顶部信息条',
            'sidebar' => '侧边栏组件',
        ];
        foreach ($components as $comp) {
            $names[] = $labelMap[$comp] ?? $comp;
        }
        return $names;
    }

    /**
     * 按行业获取CSS组件规则集
     */
    public function getComponentsForIndustry(string $industry): array
    {
        $components = self::INDUSTRY_COMPONENTS[$industry] ?? self::INDUSTRY_COMPONENTS['corporate'];
        $cssBlocks = [];

        foreach ($components as $comp) {
            $method = 'render' . ucfirst($comp);
            if (method_exists($this, $method)) {
                $cssBlocks[$comp] = $this->$method();
            }
        }

        return $cssBlocks;
    }

    /**
     * 获取所有组件CSS（合并输出）
     */
    public function getAllCssForIndustry(string $industry): string
    {
        $blocks = $this->getComponentsForIndustry($industry);
        $css = "/* V2.9.8 A-2: Component Library — {$industry} style */\n\n";

        foreach ($blocks as $block) {
            $css .= $block . "\n\n";
        }

        return $css;
    }

    /**
     * 组件1：CSS变量体系
     */
    protected function renderVars(): string
    {
        // V2.9.11: 与getThemeCssVars()完全对齐的25个变量
        return <<<CSS
/* === CSS Variables (V2.9.11 Unified 25 vars) === */
:root {
    --primary: #2563EB;
    --primary-light: #DBEAFE;
    --primary-dark: #1E40AF;
    --secondary: #64748B;
    --accent: #F59E0B;
    --bg: #FFFFFF;
    --bg-secondary: #F8FAFC;
    --bg-section: #F1F5F9;
    --text: #1E293B;
    --text-secondary: #64748B;
    --text-inverse: #FFFFFF;
    --border: #E2E8F0;
    --radius: 8px;
    --radius-lg: 12px;
    --radius-sm: 4px;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-hover: 0 4px 12px rgba(0,0,0,0.15);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
    --font-heading: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --font-body: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --transition: all 0.2s ease;
    --transition-slow: all 0.3s ease;
    --max-width: 1200px;
    --sidebar-pos: left;
    --header-style: full;
}
CSS;
    }

    /**
     * 组件2：Hero区域
     */
    protected function renderHero(): string
    {
        return <<<CSS
/* === Hero Section === */
.hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 80px 0;
    color: var(--text-inverse);
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
}
.hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    line-height: 1.2;
    position: relative;
}
.hero p {
    font-size: 1.125rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 2rem;
    position: relative;
}
.hero .btn-outline {
    border-color: var(--text-inverse);
    color: var(--text-inverse);
}
.hero .btn-outline:hover {
    background: var(--text-inverse);
    color: var(--primary);
}

@media (max-width: 768px) {
    .hero { padding: 40px 0; }
    .hero h1 { font-size: 1.75rem; }
    .hero p { font-size: 1rem; }
}
CSS;
    }

    /**
     * 组件3：卡片组件
     */
    protected function renderCard(): string
    {
        return <<<CSS
/* === Card Component === */
.card {
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 24px;
    transition: var(--transition);
    border: 1px solid var(--border);
}
.card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}
.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text);
}
.card-text {
    font-size: 0.938rem;
    line-height: 1.7;
    color: var(--text-muted);
}
.card-img {
    width: 100%;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    object-fit: cover;
}
.card-footer {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}
CSS;
    }

    /**
     * 组件4：按钮系统
     */
    protected function renderButton(): string
    {
        return <<<CSS
/* === Button System === */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: var(--radius);
    border: none;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    line-height: 1.5;
}
.btn-primary {
    background: var(--primary);
    color: var(--text-inverse);
}
.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-hover);
}
.btn-primary:active {
    transform: translateY(0);
}
.btn-outline {
    border: 2px solid var(--primary);
    color: var(--primary);
    background: transparent;
}
.btn-outline:hover {
    background: var(--primary);
    color: var(--text-inverse);
}
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.813rem;
}
.btn-lg {
    padding: 0.875rem 1.75rem;
    font-size: 1rem;
}
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
CSS;
    }

    /**
     * 组件5：导航栏
     */
    protected function renderNav(): string
    {
        return <<<CSS
/* === Navigation === */
.navbar {
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
}
.nav-container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.nav-brand {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    text-decoration: none;
}
.nav-links {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}
.nav-link {
    color: var(--text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
    padding: 0.25rem 0;
    border-bottom: 2px solid transparent;
}
.nav-link:hover {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
.nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    font-weight: 600;
}

@media (max-width: 768px) {
    .nav-links { gap: 1rem; font-size: 0.813rem; }
}
CSS;
    }

    /**
     * 组件6：响应式网格
     */
    protected function renderGrid(): string
    {
        return <<<CSS
/* === Responsive Grid === */
.grid-2, .grid-3, .grid-4 {
    display: grid;
    gap: 1.5rem;
}
.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
.grid-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 1024px) {
    .grid-4 { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
}
CSS;
    }

    /**
     * 组件7：间距体系
     */
    protected function renderSpacing(): string
    {
        return <<<CSS
/* === Spacing System === */
.section {
    padding: 60px 0;
}
.section-dark {
    background: var(--bg-section);
}
.section-header {
    text-align: center;
    margin-bottom: 2.5rem;
}
.section-header h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.75rem;
}
.section-header p {
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto;
}
.container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 1rem;
}
.mt-xs { margin-top: 0.5rem; }
.mt-sm { margin-top: 1rem; }
.mt-md { margin-top: 1.5rem; }
.mt-lg { margin-top: 2rem; }
.mt-xl { margin-top: 3rem; }
.mb-xs { margin-bottom: 0.5rem; }
.mb-sm { margin-bottom: 1rem; }
.mb-md { margin-bottom: 1.5rem; }
.mb-lg { margin-bottom: 2rem; }
.mb-xl { margin-bottom: 3rem; }
CSS;
    }

    /**
     * 组件8：价格/促销标签（电商专用）
     */
    protected function renderPrice(): string
    {
        return <<<CSS
/* === Price & Sale Badges (E-commerce) === */
.price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #EF4444;
}
.price-original {
    font-size: 0.875rem;
    color: var(--text-muted);
    text-decoration: line-through;
    margin-left: 0.5rem;
}
.badge-sale {
    display: inline-block;
    background: #EF4444;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.125rem 0.5rem;
    border-radius: var(--radius-sm);
    text-transform: uppercase;
}
.badge-new {
    display: inline-block;
    background: var(--primary);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.125rem 0.5rem;
    border-radius: var(--radius-sm);
}
.product-card {
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
}
.product-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}
.product-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.product-info {
    padding: 1rem;
}
CSS;
    }

    /**
     * 组件9：评论/文章区（博客专用）
     */
    protected function renderArticle(): string
    {
        return <<<CSS
/* === Article & Blog Components === */
.article-card {
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
}
.article-card:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}
.article-body {
    padding: 1.5rem;
}
.article-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-muted);
    font-size: 0.813rem;
    margin-bottom: 0.75rem;
}
.article-excerpt {
    color: var(--text-muted);
    line-height: 1.7;
    font-size: 0.938rem;
}
.author-box {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-top: 1px solid var(--border);
}
.author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}
.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.tag-badge {
    display: inline-block;
    background: var(--bg-secondary);
    color: var(--text-muted);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.813rem;
    text-decoration: none;
    transition: var(--transition);
}
.tag-badge:hover {
    background: var(--primary-light);
    color: var(--primary);
}
.sidebar-widget {
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.sidebar-widget h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--primary);
}
CSS;
    }

    /**
     * 组件11：顶部信息条（金融/教育等行业）
     */
    protected function renderHeader(): string
    {
        return <<<CSS
/* === Top Header Bar === */
.top-header {
    background: var(--primary-dark);
    color: var(--text-inverse);
    font-size: 0.875rem;
    padding: 0.5rem 0;
}
.top-header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.top-header-info {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}
.top-header-info span {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    opacity: 0.9;
}
.top-header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}
.top-header-actions a {
    color: var(--text-inverse);
    text-decoration: none;
    opacity: 0.85;
    transition: var(--transition);
}
.top-header-actions a:hover {
    opacity: 1;
    text-decoration: underline;
}
@media (max-width: 768px) {
    .top-header { display: none; }
}
CSS;
    }

    /**
     * 组件12：侧边栏组件（医疗等行业）
     */
    protected function renderSidebar(): string
    {
        return <<<CSS
/* === Sidebar Component === */
.sidebar-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 2rem 1rem;
}
.sidebar {
    background: var(--bg);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 80px;
}
.sidebar-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--primary);
}
.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar-menu li {
    margin-bottom: 0.25rem;
}
.sidebar-menu a {
    display: block;
    padding: 0.5rem 0.75rem;
    border-radius: var(--radius-sm);
    color: var(--text);
    text-decoration: none;
    font-size: 0.875rem;
    transition: var(--transition);
}
.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: var(--primary-light);
    color: var(--primary);
}
.sidebar-contact {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}
.sidebar-contact p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}
.sidebar-contact .phone {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--primary);
}
@media (max-width: 1024px) {
    .sidebar-layout {
        grid-template-columns: 1fr;
    }
    .sidebar {
        position: static;
        order: 2;
    }
}
CSS;
    }

    /**
     * 组件10：页脚多列
     */
    protected function renderFooter(): string
    {
        return <<<CSS
/* === Footer === */
.site-footer {
    background: var(--primary-dark, #1E293B);
    color: rgba(255,255,255,0.8);
    padding: 3rem 0 1.5rem;
}
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}
.footer-col h4 {
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
.footer-link {
    display: block;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 0.875rem;
    padding: 0.25rem 0;
    transition: var(--transition);
}
.footer-link:hover {
    color: #fff;
    padding-left: 4px;
}
.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 1.5rem;
    text-align: center;
    font-size: 0.813rem;
    color: rgba(255,255,255,0.5);
}
.social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
    margin-right: 0.5rem;
    transition: var(--transition);
}
.social-icon:hover {
    background: var(--primary);
    transform: translateY(-2px);
}
CSS;
    }
}
