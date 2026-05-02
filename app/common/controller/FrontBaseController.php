<?php
declare(strict_types=1);

namespace app\common\controller;

use app\common\model\Config as ConfigModel;
use app\common\model\CustomVar;
use app\common\model\Member as MemberModel;
use app\common\model\Module;
use app\common\service\CacheService;
use app\common\service\TemplateService;
use think\App;
use think\facade\Cache;
use think\facade\Cookie;

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
        // T0: 提前获取当前主题名（用于缓存隔离，不依赖configs加载）
        // getActiveTheme() 自身有 try/catch + Cache::remember + fallback default
        $activeTheme = TemplateService::getActiveTheme();

        // T1: 整页缓存命中检查（缓存key包含主题名，确保不同主题缓存隔离）
        if ($this->enablePageCache
            && !$this->app->isDebug()
            && $this->request->isGet()
            && !Cookie::has('member_token')
        ) {
            $cacheKey = 'page_html_' . $activeTheme . '_' . md5($this->request->url(true));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->pageCacheKey = null; // 命中后不再写入
                response($cached, 200, ['Content-Type' => 'text/html; charset=utf-8'])->send();
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

        // 注入视图全局变量（含主题变量供模板引用资源路径）
        $this->app->view->assign([
            'site_name'        => $configs['site_name'] ?? 'AI-CMS',
            'site_keywords'    => $configs['site_keywords'] ?? 'AI,CMS,内容管理',
            'site_description' => $configs['site_description'] ?? 'AI驱动的企业信息管理系统',
            'is_member_login'  => $this->isMemberLogin,
            'member_info'      => $this->memberInfo,
            'seo_title'        => '',
            'seo_keywords'     => '',
            'seo_description'  => '',
            'custom'           => $customVars,
            'enabled_modules'  => $enabledModules,
            // V2.4 多模板风格：注入主题变量
            'active_theme'     => $activeTheme,
            'theme_assets'     => '/template/themes/' . $activeTheme . '/',
        ]);
    }

    /**
     * 从Cookie Token解析当前会员
     */
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

        $member = MemberModel::find($memberId);
        if ($member && $member->status == 1) {
            $this->memberInfo = [
                'id'       => $member->id,
                'username' => $member->username,
                'nickname' => $member->nickname,
                'avatar'   => $member->avatar,
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
     * 成功响应
     */
    protected function success(string $msg = '操作成功', mixed $data = [], int $code = 0): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
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
        ]);
    }
}
