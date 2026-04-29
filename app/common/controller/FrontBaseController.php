<?php
declare(strict_types=1);

namespace app\common\controller;

use app\common\model\Config as ConfigModel;
use app\common\model\CustomVar;
use app\common\model\Member as MemberModel;
use app\common\model\Module;
use app\common\service\CacheService;
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
        // 加载数据库系统配置到ThinkPHP Config（容错）
        try {
            load_cms_configs();
        } catch (\Throwable) {
            // 配置表可能尚未创建，降级跳过
        }

        // 设置前台模板路径（template/pc/default/）
        $this->app->config->set([
            'view.view_path' => root_path() . 'template' . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR,
        ], 'view');

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

        // 注入视图全局变量
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
     * 重写视图渲染，支持整页HTML缓存
     * 仅对未登录会员的GET请求且非调试模式生效
     */
    protected function view(string $template = '', array $vars = [], int $code = 200, callable $filter = null): \think\Response
    {
        if ($this->enablePageCache
            && !$this->isMemberLogin
            && $this->request->isGet()
            && !$this->app->isDebug()
        ) {
            $cacheKey = 'page_html_' . md5($this->request->url(true));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response($cached, 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }
            $this->pageCacheKey = $cacheKey;
        }

        $response = parent::view($template, $vars, $code, $filter);

        if (!empty($this->pageCacheKey)) {
            Cache::tag(CacheService::TAG_CONTENT)->set(
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
