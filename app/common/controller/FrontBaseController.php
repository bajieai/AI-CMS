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

namespace app\common\controller;

use app\common\model\Config as ConfigModel;
use app\common\model\CustomVar;
use app\common\model\Member as MemberModel;
use app\common\model\Module;
use app\common\service\CacheService;
use app\common\service\LanguageService;
use app\common\service\PrivateMessageService;
use app\common\service\TemplateService;
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Db;

/**
 * 前台基类控制器
 * 所有home应用的控制器继承此类
 */
abstract class FrontBaseController extends \think\BaseController
{
    /** @var array|null 当前登录会员信息 */
    protected ?array $memberInfo = null;

    /** @var bool 是否已登录会员 */
    protected bool $isMemberLogin = false;

    /** @var bool 是否启用整页缓存 */
    protected bool $enablePageCache = true;

    /** @var string|null 当前页面缓存Key */
    protected ?string $pageCacheKey = null;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    protected function initialize(): void
    {
        // V2.9.7 AI模板兼容：模板中访问空数组key不抛异常
        

        // 检测预览模式（由ThemePreviewMiddleware设置）
        $isPreview = $this->request->middleware('is_preview', false);

        // T0: 提前获取当前主题名（用于缓存隔离，不依赖configs加载）
        // getActiveTheme() 自身有 try/catch + Cache::remember + fallback default
        $activeTheme = TemplateService::getActiveTheme();

        // 预览模式：禁用整页缓存，避免污染生产缓存
        if ($isPreview) {
            $this->enablePageCache = false;
        }

        // T0.5: 获取当前语言（缓存key需包含语言维度）
        $currentLang = 'zh-CN';
        try {
            $currentLang = LanguageService::getCurrentLang();
        } catch (\Throwable) {
            // LanguageService 依赖可能未就绪，降级使用默认值
        }

        // T1: 整页缓存命中检查（缓存key包含主题名+语言，确保不同主题/语言缓存隔离）
        if ($this->enablePageCache
            && !$this->app->isDebug()
            && $this->request->isGet()
            && !Cookie::has('member_token')
        ) {
            $cacheKey = 'page_html_' . $activeTheme . '_' . $currentLang . '_' . md5($this->request->url(true));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->pageCacheKey = null; // 命中后不再写入
                // V2.9.5 修复：缓存命中直出路径补全Vary:Cookie和Cache-Control头
                response($cached, 200, [
                    'Content-Type'  => 'text/html; charset=utf-8',
                    'Cache-Control' => 'public, max-age=3600',
                    'Vary'          => 'Cookie',
                ])->send();
                exit;
            }
            $this->pageCacheKey = $cacheKey;
        }

        // 加载数据库系统配置到ThinkPHP Config（容错）
        try {
            load_cms_configs();
        } catch (\Throwable) {
            // 配置表可能尚未创建，降级跳过
        }

        // 设置前台模板路径（TemplateService 动态解析：3级降级链）
        $frontendPath = TemplateService::getFrontendPath();
        $this->app->config->set([
            'view_path' => $frontendPath,
        ], 'view');
        // 强制视图引擎重新读取配置（驱动实例会缓存配置，必须刷新）
        $this->app->view->engine()->config(['view_path' => $frontendPath]);

        // 站点配置缓存（容错：表不存在时返回空数组）
        try {
            $configs = Cache::tag(CacheService::TAG_CONFIG)->remember('site_configs', function () {
                return ConfigModel::column('value', 'name');
            }, 3600);
        } catch (\Throwable) {
            $configs = [];
        }

        // 自定义变量缓存（容错：表不存在时返回空数组）
        try {
            $customVars = Cache::tag(CacheService::TAG_CONFIG)->remember('custom_vars', function () {
                return CustomVar::column('value', 'name');
            }, 3600);
        } catch (\Throwable) {
            $customVars = [];
        }

        // 已启用模块列表（容错：表不存在时返回空数组）
        try {
            $enabledModules = Cache::tag(CacheService::TAG_CONFIG)->remember('enabled_modules', function () {
                return Module::where('is_enabled', 1)->column('code');
            }, 3600);
        } catch (\Throwable) {
            $enabledModules = [];
        }

        // 解析会员登录状态
        $this->resolveMember();

        // 会员登录后禁用整页缓存（个性化内容不应缓存）
        if ($this->isMemberLogin) {
            $this->pageCacheKey = null;
        }

        // 获取启用语言列表（容错）
        $enabledLanguages = [];
        try {
            $enabledLanguages = LanguageService::getEnabledLanguages();
        } catch (\Throwable) {
            // Language 表可能未创建
        }

        // V2.9.1 M14c: CDN配置注入
        $cdnEnabled = (bool) Config::get('cdn.enabled', false);
        $cdnDomain = trim(Config::get('cdn.domain', ''));

        // V2.9.1: 多语言开关 — 关闭时清空语言列表，前台自动隐藏切换器
        $langSwitcherEnabled = (bool) ($configs['language_switcher_enabled'] ?? true);
        if (!$langSwitcherEnabled) {
            $enabledLanguages = [];
        }
        $langSitewide = (bool) ($configs['language_sitewide'] ?? false);

        $this->app->view->assign([
            'site_name'        => $configs['site_name'] ?? 'AI-CMS',
            'site_keywords'    => $configs['site_keywords'] ?? 'AI,CMS,内容管理',
            'site_description' => $configs['site_description'] ?? 'AI驱动的企业信息管理系统',
            'isMemberLogin'    => $this->isMemberLogin,
            'is_member_login'  => $this->isMemberLogin, // 兼容layout.html等使用下划线命名
            'member_info'      => $this->memberInfo,
            // V2.9.9-R5 消息未读数
            'member_unread_count' => ($this->isMemberLogin && $this->memberInfo) ? (PrivateMessageService::getUnreadCount((int) $this->memberInfo['id']) + PrivateMessageService::getSystemUnreadCount((int) $this->memberInfo['id'])) : 0,
            'seo_title'        => '',
            'seo_keywords'     => '',
            'seo_description'  => '',
            'custom'           => $customVars,
            'enabled_modules'  => $enabledModules,
            // V2.4 多模板风格：注入主题变量
            'active_theme'     => $activeTheme,
            'theme_assets'     => '/template/themes/' . $activeTheme . '/',
            // V2.6 静态资源分离：skin目录指向public/skin/，按pc/mobile区分
            'skin'             => '/skin/themes/' . $activeTheme . '/' . TemplateService::getDeviceType() . '/',
            // V2.9 多语言：注入语言变量供模板使用
            'current_lang'     => $currentLang,
            'enabled_languages'=> $enabledLanguages,
            // V2.9 M11 模板可视化：注入主题CSS变量供layout.html消费
            'theme_css_vars'   => $this->getThemeCssVars($activeTheme),
            // V2.9.1 M14c: CDN变量供模板主动替换
            'cdn_enabled'      => $cdnEnabled,
            'cdn_domain'       => $cdnDomain,
            // V2.9.1: 多语言开关（供模板/逻辑判断）
            'lang_switcher_enabled' => $langSwitcherEnabled,
            'lang_sitewide'    => $langSitewide,
            // V2.9.10: 积分商城开关
            'points_shop_enabled' => (bool) ($configs['points_shop_enabled'] ?? true),
            // V2.9.2 注入网站Logo及相关配置到前台视图
            'site_logo'        => $configs['site_logo'] ?? '',
            'logo_icon_only'   => ($configs['logo_icon_only'] ?? '') === '1',
            'logo_name'        => $configs['logo_name'] ?? '',
            'brand_name'       => !empty($configs['logo_name']) ? $configs['logo_name'] : '八界AI-CMS',
            // 知识产权保护 Layer4：前台版权信息
            'powered_by'       => 'Powered by <a href="https://www.i8j.cn" target="_blank">八界AI-CMS</a>',
            'copyright_info'   => '© 2026 湖北八界智能技术有限公司',
            // V2.9.7 AI主题兼容：导航高亮/分页高亮用
            'current_page'     => '',
            'current'          => '',

                            ]);

        // V2.9.7 AI主题变量兜底（手动）
        $this->app->view->assign([
            'year'             => date('Y'),
            'page'             => 1,
            'total_pages'     => 1,
        ]);

        // V2.9.7 AI主题兼容：自动注入AI模板中缺失变量的默认值
        $this->injectAiTemplateFallbacks($activeTheme);
    }

    protected function resolveMember(): void
    {
        $token = Cookie::get('member_token');
        if (empty($token)) {
            return;
        }

        $hash = sha1($token);
        $cacheKey = 'i8j_member_token_' . $hash;
        $memberData = Cache::get($cacheKey);

        if (!empty($memberData) && is_array($memberData)) {
            $this->memberInfo = $memberData;
            $this->isMemberLogin = true;
            return;
        }

        // Cache未命中，查库并写入缓存（Token中存储member_id）
        $memberId = Cache::get($cacheKey . '_id');
        if (empty($memberId)) {
            return;
        }

        // 使用Db查询构造器绕过模型字段缓存（避免fields not exists报错）
        $member = Db::name('member')->where('id', $memberId)->find();
        if ($member && $member['status'] == 1) {
            $this->memberInfo = [
                'id'       => (int) $member['id'],
                'username' => $member['username'],
                'nickname' => $member['nickname'],
                'email'    => $member['email'],
                'avatar'   => $member['avatar'],
            ];
            $this->isMemberLogin = true;
            Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey, $this->memberInfo, 7200);
        }
    }

    /**
     * 设置页面SEO元数据
     */
    protected function setSeo(string $title = '', string $keywords = '', string $description = ''): void
    {
        $this->app->view->assign([
            'seo_title'       => $title,
            'seo_keywords'    => $keywords,
            'seo_description' => $description,
        ]);
    }

    /**
     * 重写视图渲染，支持整页HTML缓存写入
     * 缓存命中检查已前移到initialize()，此处仅负责写入
     * 仅对未登录会员的GET请求且非调试模式生效
     */
    protected function view(string $template = '', array $vars = [], int $code = 200, callable $filter = null): \think\Response
    {
        $response = parent::view($template, $vars, $code, $filter);

        // V2.9.1 M14c: 响应层CDN兜底 — 仅对src="/uploads/ 和 href="/uploads/ 做轻量str_replace
        $response = $this->applyCdnFallback($response);

        // V2.9.4 修复：浏览器HTTP缓存需按登录状态区分，避免登录后仍显示游客缓存页面
        if ($this->isMemberLogin) {
            // 已登录用户：禁止浏览器缓存，每次请求都验证
            $response->header([
                'Cache-Control' => 'private, no-cache, must-revalidate',
                'Vary'          => 'Cookie',
            ]);
        } else {
            // 游客：允许公共缓存，但按 Cookie 区分缓存条目
            $response->header([
                'Cache-Control' => 'public, max-age=3600',
                'Vary'          => 'Cookie',
            ]);
        }

        if (!empty($this->pageCacheKey)) {
            Cache::tag(CacheService::TAG_PAGE_CACHE)->set(
                $this->pageCacheKey,
                $response->getContent(),
                3600
            );
        }

        return $response;
    }

    /**
     * V2.9.1 M14c: 响应层CDN兜底替换
     * 仅匹配 src="/uploads/ 和 href="/uploads/ 两种模式，避免误伤JS字符串
     */
    protected function applyCdnFallback(\think\Response $response): \think\Response
    {
        $cdnEnabled = (bool) Config::get('cdn.enabled', false);
        $cdnDomain = trim(Config::get('cdn.domain', ''));
        if (!$cdnEnabled || empty($cdnDomain)) {
            return $response;
        }

        $contentType = $response->getHeader('Content-Type');
        $isHtml = false;
        if (is_array($contentType)) {
            $contentType = implode(';', $contentType);
        }
        if (is_string($contentType) && stripos($contentType, 'text/html') !== false) {
            $isHtml = true;
        }

        if (!$isHtml) {
            return $response;
        }

        $html = $response->getContent();
        if (empty($html)) {
            return $response;
        }

        $cdnDomain = rtrim($cdnDomain, '/');

        // 仅替换两种明确模式，不做全HTML正则（避免误伤JS代码块中的路径字符串）
        $replacements = [
            'src="/uploads/'  => 'src="https://' . $cdnDomain . '/uploads/',
            'href="/uploads/' => 'href="https://' . $cdnDomain . '/uploads/',
            "src='/uploads/"  => "src='https://" . $cdnDomain . "/uploads/",
            "href='/uploads/" => "href='https://" . $cdnDomain . "/uploads/",
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);
        $response->content($html);

        return $response;
    }

    /**
     * 成功响应
     */
    protected function success(string $msg = '操作成功', mixed $data = [], int $code = 0): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], 200, [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 失败响应
     */
    protected function error(string $msg = '操作失败', int $code = 1, mixed $data = []): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], 200, [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取主题CSS变量配置 - V2.9 M11模板可视化
     * 从i8j_config表读取theme_vars_{theme}配置，与默认值合并
     */
    protected function getThemeCssVars(string $theme): array
    {
        // V2.9.11: 统一25个CSS变量（三套命名体系统一），全部无前缀
        $defaults = [
            // === 核心品牌色（5个）===
            '--primary'        => '#2563EB',
            '--primary-light'  => '#DBEAFE',
            '--primary-dark'   => '#1E40AF',
            '--secondary'      => '#64748B',
            '--accent'         => '#F59E0B',
            // === 背景色（3个）===
            '--bg'             => '#FFFFFF',
            '--bg-secondary'   => '#F8FAFC',
            '--bg-section'     => '#F1F5F9',
            // === 文字色（3个）===
            '--text'           => '#1E293B',
            '--text-secondary' => '#64748B',
            '--text-inverse'   => '#FFFFFF',
            // === 边框与效果（7个）===
            '--border'         => '#E2E8F0',
            '--radius'         => '8px',
            '--radius-lg'      => '12px',
            '--radius-sm'      => '4px',
            '--shadow'         => '0 1px 3px rgba(0,0,0,0.1)',
            '--shadow-hover'   => '0 4px 12px rgba(0,0,0,0.15)',
            '--shadow-lg'      => '0 10px 25px rgba(0,0,0,0.1)',
            // === 字体（2个）===
            '--font-heading'   => "'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            '--font-body'      => "'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            // === 动画（2个）===
            '--transition'     => 'all 0.2s ease',
            '--transition-slow'=> 'all 0.3s ease',
            // === 布局（3个）===
            '--max-width'      => '1200px',
            '--sidebar-pos'    => 'left',
            '--header-style'   => 'full',
        ];

        try {
            $configKey = 'theme_vars_' . $theme;
            $saved = ConfigModel::where('name', $configKey)->value('value');
            if ($saved) {
                $decoded = json_decode($saved, true);
                if (is_array($decoded)) {
                    return array_merge($defaults, $decoded);
                }
            }
        } catch (\Throwable) {
            // 配置表不可用时使用默认值
        }

        return $defaults;
    }

    /**
     * V2.9.7 AI主题兼容：自动注入AI模板中缺失变量的默认值（空数组）
     *
     * AI生成的模板常使用CMS不存在的自定义变量（如$flash_sale_products等），
     * 该方法通过扫描主题模板文件中的{volist name="..."}提取变量名，
     * 为未定义变量注入空数组，防止Undefined variable报错。
     */
    protected function injectAiTemplateFallbacks(string $themeName): void
    {
        $device = TemplateService::getDeviceType();
        $themeDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName
            . DIRECTORY_SEPARATOR . $device;

        if (!is_dir($themeDir)) {
            return;
        }

        // 两种变量采集：
        // $loopVars  → {volist name="xxx"} — 需要注入 [] 以便空循环不报错
        // $outputVars → {$xxx} / {$xxx.yyy} — 不注入 (|default= 已处理)
        $loopVars = [];
        $files = glob($themeDir . DIRECTORY_SEPARATOR . '*.html');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // 1. 采集 {volist name="xxx"} 的变量
            if (preg_match_all('/\{volist\s+name\s*=\s*"([^"]+)"[^}]*\}/i', $content, $matches)) {
                foreach ($matches[1] as $expr) {
                    $parts = explode('.', $expr);
                    $rootVar = $parts[0];
                    if (!empty($rootVar)) {
                        $loopVars[$rootVar] = true;
                    }
                }
            }
        }

        // 排除CMS系统已定义的变量
        $systemVars = ['site_name', 'site_keywords', 'site_description', 'site_logo',
            'isMemberLogin', 'is_member_login', 'member_info',
            'seo_title', 'seo_keywords', 'seo_description',
            'custom', 'enabled_modules', 'active_theme', 'theme_assets', 'skin',
            'current_lang', 'enabled_languages', 'theme_css_vars',
            'cdn_enabled', 'cdn_domain', 'lang_switcher_enabled', 'lang_sitewide',
            'logo_icon_only', 'logo_name', 'brand_name',
            'i', 'field', 'key', 'vo', '__LIST__', '__NOLAYOUT__',
            '__CONTENT__', '__BLOCK__', 'Think', 'category', 'product',
            'info', 'pages', 'member', 'config'];

        foreach ($systemVars as $v) {
            unset($loopVars[$v]);
        }

        // 注入空数组（仅限 {volist} 变量，跳过已存在的）
        $assignData = [];
        foreach (array_keys($loopVars) as $varName) {
            if (!isset($this->app->view->{$varName})) {
                $assignData[$varName] = [];
            }
        }

        if (!empty($assignData)) {
            $this->app->view->assign($assignData);
        }
    }
}
