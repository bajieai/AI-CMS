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
// AI-CMS V2.0 路由配置 - 后台路由
// 完整命名空间类名需包含 Controller 后缀

use think\facade\Route;

// 后台首页
Route::get('$', '\app\admin\controller\IndexController@index');

// 登录相关
Route::rule('login$', '\app\admin\controller\LoginController@index', 'GET|POST');
Route::rule('logout$', '\app\admin\controller\LoginController@logout', 'GET|POST');
Route::get('login/captcha$', '\app\admin\controller\LoginController@captcha');

// 统计报表
Route::get('stats/index$', '\app\admin\controller\StatsController@index');

// 内容管理
Route::rule('content/index$', '\app\admin\controller\ContentController@index', 'GET');
Route::rule('content/add$', '\app\admin\controller\ContentController@add', 'GET|POST');
Route::rule('content/edit/:id$', '\app\admin\controller\ContentController@edit', 'GET|POST');
Route::post('content/delete/:id$', '\app\admin\controller\ContentController@delete');
Route::post('content/publish/:id$', '\app\admin\controller\ContentController@publish');
Route::get('content/getExtFields$', '\app\admin\controller\ContentController@getExtFields');
Route::get('content/getCates$', '\app\admin\controller\ContentController@getCates');
Route::get('content/recycleBin$', '\app\admin\controller\ContentController@recycleBin');
Route::post('content/restore/:id$', '\app\admin\controller\ContentController@restore');
Route::post('content/forceDelete/:id$', '\app\admin\controller\ContentController@forceDelete');
Route::post('content/copy/:id$', '\app\admin\controller\ContentController@copy');
Route::post('content/batchPublish$', '\app\admin\controller\ContentController@batchPublish');
Route::post('content/batchDelete$', '\app\admin\controller\ContentController@batchDelete');
Route::post('content/batchMoveCate$', '\app\admin\controller\ContentController@batchMoveCate');
// V3.1: 批量SEO优化
Route::post('content/batchSeoOptimize$', '\app\admin\controller\ContentController@batchSeoOptimize');
Route::post('content/autoSave/:id$', '\app\admin\controller\ContentController@autoSave');
Route::get('content/versions/:id$', '\app\admin\controller\ContentController@versions');
Route::post('content/rollback/:versionId$', '\app\admin\controller\ContentController@rollback');
// V2.9.9: AI-GEO评分
Route::get('content/geoScore/:id$', '\app\admin\controller\ContentController@geoScore');
// V2.9.13: 运营数据看板
Route::get('data_dashboard/index$', '\app\admin\controller\DataDashboardController@index');
Route::get('data_dashboard/overview$', '\app\admin\controller\DataDashboardController@overview');
Route::get('data_dashboard/trend$', '\app\admin\controller\DataDashboardController@trend');
Route::get('data_dashboard/category$', '\app\admin\controller\DataDashboardController@category');
Route::get('data_dashboard/hotContent$', '\app\admin\controller\DataDashboardController@hotContent');
Route::get('data_dashboard/report$', '\app\admin\controller\DataDashboardController@report');
// V2.9.13: AI内容增强补完
Route::post('content/aiImageGenerate/:id$', '\app\admin\controller\ContentController@aiImageGenerate');
Route::get('content/aiImagePoll/:id$', '\app\admin\controller\ContentController@aiImagePoll');
Route::post('content/aiImageConfirm/:id$', '\app\admin\controller\ContentController@aiImageConfirm');
Route::post('content/aiSeoOptimize/:id$', '\app\admin\controller\ContentController@aiSeoOptimize');
Route::post('content/aiSeoApply/:id$', '\app\admin\controller\ContentController@aiSeoApply');
Route::post('content/generateByStyle/:id$', '\app\admin\controller\ContentController@generateByStyle');
Route::get('content/getWritingStyles$', '\app\admin\controller\ContentController@getWritingStyles');
// V2.9.14: AI进度SSE + 批量SEO控制
Route::get('ai_progress/stream/:bizKey$', '\app\admin\controller\AiProgressController@stream');
Route::post('ai_progress/batchSeoStart$', '\app\admin\controller\AiProgressController@batchSeoStart');
Route::post('ai_progress/batchSeoPause$', '\app\admin\controller\AiProgressController@batchSeoPause');
Route::post('ai_progress/batchSeoResume$', '\app\admin\controller\AiProgressController@batchSeoResume');
Route::get('ai_progress/batchSeoStatus/:bizKey$', '\app\admin\controller\AiProgressController@batchSeoStatus');
Route::get('ai_progress/batchSeoPoll/:bizKey$', '\app\admin\controller\AiProgressController@batchSeoPoll');

// V2.7 章节管理
Route::get('content/getChapters/:parentId$', '\app\admin\controller\ContentController@getChapters');
Route::post('content/saveChapter$', '\app\admin\controller\ContentController@saveChapter');
Route::post('content/deleteChapter/:id$', '\app\admin\controller\ContentController@deleteChapter');
Route::post('content/sortChapters$', '\app\admin\controller\ContentController@sortChapters');

// V2.7 头条号OAuth
Route::get('toutiao/oauth$', '\app\admin\controller\PublishPlatformController@toutiaoOauth');

// 分类管理
Route::rule('cate/index$', '\app\admin\controller\CateController@index', 'GET');
Route::rule('cate/add$', '\app\admin\controller\CateController@add', 'GET|POST');
Route::rule('cate/edit/:id$', '\app\admin\controller\CateController@edit', 'GET|POST');
Route::post('cate/delete/:id$', '\app\admin\controller\CateController@delete');

// 标签管理
Route::rule('tag/index$', '\app\admin\controller\TagController@index', 'GET');
Route::rule('tag/add$', '\app\admin\controller\TagController@add', 'GET|POST');
Route::rule('tag/edit/:id$', '\app\admin\controller\TagController@edit', 'GET|POST');
Route::post('tag/delete/:id$', '\app\admin\controller\TagController@delete');

// 用户管理
Route::rule('user/index$', '\app\admin\controller\UserController@index', 'GET');
Route::rule('user/add$', '\app\admin\controller\UserController@add', 'GET|POST');
Route::rule('user/edit/:id$', '\app\admin\controller\UserController@edit', 'GET|POST');
Route::post('user/delete/:id$', '\app\admin\controller\UserController@delete');
Route::rule('user/profile$', '\app\admin\controller\UserController@profile', 'GET|POST');

// V2.9.23 A-4: 模板缓存管理
Route::rule('system/config$', '\app\admin\controller\SystemController@config', 'GET|POST');
Route::rule('system/cache$', '\app\admin\controller\SystemController@cache', 'GET|POST');
Route::post('system/checkTemplateCache$', '\app\admin\controller\SystemController@checkTemplateCache');
Route::post('system/clearTemplateCache$', '\app\admin\controller\SystemController@clearTemplateCacheAjax');
Route::rule('system/customVar$', '\app\admin\controller\SystemController@customVar', 'GET|POST');
Route::post('system/customVarSave$', '\app\admin\controller\SystemController@customVarSave');
Route::post('system/customVarDelete$', '\app\admin\controller\SystemController@customVarDelete');
Route::rule('system/moduleControl$', '\app\admin\controller\SystemController@moduleControl', 'GET');
Route::post('system/moduleToggle$', '\app\admin\controller\SystemController@moduleToggle');

// V2.9.5: 主题管理路由（AJAX接口）
Route::get('system/templates$', '\app\admin\controller\SystemController@templates');
Route::post('system/setTheme$', '\app\admin\controller\SystemController@setTheme');
Route::get('system/adminTemplates$', '\app\admin\controller\SystemController@adminTemplates');
Route::post('system/setAdminTheme$', '\app\admin\controller\SystemController@setAdminTheme');
Route::get('system/allTemplates$', '\app\admin\controller\SystemController@allTemplates');

// V2.9.24 H-2: 移动端导航管理
Route::rule('system/mobileNav$', '\app\admin\controller\SystemController@mobileNav', 'GET');
Route::rule('system/mobileNavEdit/:id$', '\app\admin\controller\SystemController@mobileNavEdit', 'GET|POST');
Route::post('system/mobileNavDelete/:id$', '\app\admin\controller\SystemController@mobileNavDelete');
Route::post('system/mobileNavSort$', '\app\admin\controller\SystemController@mobileNavSort');

// V2.9.24 J-1: 缓存仪表盘增强
Route::post('system/saveCacheConfig$', '\app\admin\controller\SystemController@saveCacheConfig');
Route::post('system/resetHitRate$', '\app\admin\controller\SystemController@resetHitRate');
// 头条号OAuth
Route::get('toutiaoOAuth/authorize$', '\app\admin\controller\ToutiaoOAuthController@authorize');
Route::get('toutiaoOAuth/callback$', '\app\admin\controller\ToutiaoOAuthController@callback');
Route::rule('log/index$', '\app\admin\controller\LogController@index', 'GET');
Route::get('log/export$', '\app\admin\controller\LogController@export');
Route::post('log/cleanup$', '\app\admin\controller\LogController@cleanup');

// 媒体资源库
Route::rule('media/index$', '\app\admin\controller\MediaController@index', 'GET');
Route::post('media/upload$', '\app\admin\controller\MediaController@upload');
Route::rule('media/edit/:id$', '\app\admin\controller\MediaController@edit', 'GET|POST');
Route::post('media/delete/:id$', '\app\admin\controller\MediaController@delete');
Route::get('media/select$', '\app\admin\controller\MediaController@select');

// 轮播图管理
Route::rule('banner/index$', '\app\admin\controller\BannerController@index', 'GET');
Route::rule('banner/add$', '\app\admin\controller\BannerController@add', 'GET|POST');
Route::rule('banner/edit/:id$', '\app\admin\controller\BannerController@edit', 'GET|POST');
Route::post('banner/delete/:id$', '\app\admin\controller\BannerController@delete');

// 友情链接管理
Route::rule('link/index$', '\app\admin\controller\LinkController@index', 'GET');
Route::rule('link/add$', '\app\admin\controller\LinkController@add', 'GET|POST');
Route::rule('link/edit/:id$', '\app\admin\controller\LinkController@edit', 'GET|POST');
Route::post('link/delete/:id$', '\app\admin\controller\LinkController@delete');

// 内容审核
Route::rule('review/index$', '\app\admin\controller\ReviewController@index', 'GET');
Route::post('review/approve/:id$', '\app\admin\controller\ReviewController@approve');
Route::post('review/reject/:id$', '\app\admin\controller\ReviewController@reject');
Route::get('review/history/:id$', '\app\admin\controller\ReviewController@history');

// 数据库备份
Route::rule('backup/index$', '\app\admin\controller\BackupController@index', 'GET');
Route::post('backup/create$', '\app\admin\controller\BackupController@create');
Route::post('backup/restore$', '\app\admin\controller\BackupController@restore');
Route::post('backup/delete$', '\app\admin\controller\BackupController@delete');
Route::get('backup/download$', '\app\admin\controller\BackupController@download');
Route::post('backup/cleanup$', '\app\admin\controller\BackupController@cleanup');

// V2.3 评论管理
Route::get('comment/index$', '\app\admin\controller\CommentController@index');
Route::post('comment/audit$', '\app\admin\controller\CommentController@audit');
Route::post('comment/delete$', '\app\admin\controller\CommentController@delete');
Route::post('comment/batch$', '\app\admin\controller\CommentController@batch');

// V2.3 API令牌管理
Route::get('token/index$', '\app\admin\controller\TokenController@index');
Route::rule('token/create$', '\app\admin\controller\TokenController@create', 'GET|POST');
Route::post('token/revoke$', '\app\admin\controller\TokenController@revoke');

// V2.3 SEO管理
Route::get('seo/index$', '\app\admin\controller\SeoController@index');
Route::post('seo/sitemap$', '\app\admin\controller\SeoController@generateSitemap');
Route::post('seo/robots$', '\app\admin\controller\SeoController@saveRobots');

// V2.9.16: SEO诊断引擎
Route::get('seo_diagnose/index$', '\app\admin\controller\SeoDiagnoseController@index');
Route::get('seo_diagnose/run$', '\app\admin\controller\SeoDiagnoseController@run');

// SEO关键词管理
Route::get('seo_keyword/index$', '\app\admin\controller\SeoKeywordController@index');
Route::get('seo_keyword/add$', '\app\admin\controller\SeoKeywordController@add');
Route::rule('seo_keyword/edit/:id$', '\app\admin\controller\SeoKeywordController@edit', 'GET|POST');
Route::post('seo_keyword/save$', '\app\admin\controller\SeoKeywordController@save');
Route::post('seo_keyword/delete$', '\app\admin\controller\SeoKeywordController@delete');
Route::post('seo_keyword/import$', '\app\admin\controller\SeoKeywordController@import');
// SEO关键词分组管理
Route::get('seo_keyword/group$', '\app\admin\controller\SeoKeywordController@group');
Route::post('seo_keyword/saveGroup$', '\app\admin\controller\SeoKeywordController@saveGroup');
Route::post('seo_keyword/deleteGroup$', '\app\admin\controller\SeoKeywordController@deleteGroup');

// V2.3 通知管理
Route::get('notification/index$', '\app\admin\controller\NotificationController@index');
Route::post('notification/read$', '\app\admin\controller\NotificationController@read');

// V2.3 数据导出
Route::rule('export/index$', '\app\admin\controller\ExportController@index', 'GET|POST');

// V2.3 后台会员管理
Route::get('member/index$', '\app\admin\controller\MemberController@index');
Route::get('member/detail/:id$', '\app\admin\controller\MemberController@detail');
Route::rule('member/edit/:id$', '\app\admin\controller\MemberController@edit', 'GET|POST');
Route::post('member/toggleStatus/:id$', '\app\admin\controller\MemberController@toggleStatus');

// V2.3 广告管理
Route::get('ad/index$', '\app\admin\controller\AdController@index');
Route::rule('ad/add$', '\app\admin\controller\AdController@add', 'GET|POST');
Route::rule('ad/edit/:id$', '\app\admin\controller\AdController@edit', 'GET|POST');
Route::post('ad/delete/:id$', '\app\admin\controller\AdController@delete');
Route::get('ad/stat$', '\app\admin\controller\AdController@stat');

Route::get('ad_position/index$', '\app\admin\controller\AdController@positionIndex');
Route::rule('ad_position/add$', '\app\admin\controller\AdController@positionAdd', 'GET|POST');
Route::rule('ad_position/edit/:id$', '\app\admin\controller\AdController@positionEdit', 'GET|POST');
Route::post('ad_position/delete/:id$', '\app\admin\controller\AdController@positionDelete');

// V2.3 友链分组管理
Route::get('link_group/index$', '\app\admin\controller\LinkGroupController@index');
Route::rule('link_group/add$', '\app\admin\controller\LinkGroupController@add', 'GET|POST');
Route::rule('link_group/edit/:id$', '\app\admin\controller\LinkGroupController@edit', 'GET|POST');
Route::post('link_group/delete/:id$', '\app\admin\controller\LinkGroupController@delete');
Route::post('link_group/toggleStatus/:id$', '\app\admin\controller\LinkGroupController@toggleStatus');

// V2.7 表单管理（含可视化编辑器）
Route::get('form/index$', '\app\admin\controller\FormController@index');
Route::rule('form/add$', '\app\admin\controller\FormController@add', 'GET|POST');
Route::rule('form/edit/:id$', '\app\admin\controller\FormController@edit', 'GET|POST');
Route::get('form/editor/:id$', '\app\admin\controller\FormController@editor');
Route::post('form/save$', '\app\admin\controller\FormController@save');
Route::post('form/saveEditor$', '\app\admin\controller\FormController@saveEditor');
Route::post('form/delete$', '\app\admin\controller\FormController@delete');
Route::post('form/toggleEnabled$', '\app\admin\controller\FormController@toggleEnabled');
Route::get('form/dataIndex$', '\app\admin\controller\FormController@dataIndex');

// V2.9.21: 模板分类管理
Route::get('template_category/index$', '\app\admin\controller\TemplateCategoryController@index');
Route::get('template_category/add$', '\app\admin\controller\TemplateCategoryController@add');
Route::get('template_category/edit/:id$', '\app\admin\controller\TemplateCategoryController@edit');
Route::post('template_category/save$', '\app\admin\controller\TemplateCategoryController@save');
Route::post('template_category/delete$', '\app\admin\controller\TemplateCategoryController@delete');
Route::post('template_category/toggleStatus$', '\app\admin\controller\TemplateCategoryController@toggleStatus');

// V2.7 积分商城
Route::get('points_product/index$', '\app\admin\controller\PointsProductController@index');
Route::rule('points_product/edit/:id$', '\app\admin\controller\PointsProductController@edit', 'GET|POST');
Route::rule('points_product/edit$', '\app\admin\controller\PointsProductController@edit', 'GET|POST');
Route::post('points_product/save$', '\app\admin\controller\PointsProductController@save');
Route::post('points_product/delete$', '\app\admin\controller\PointsProductController@delete');
Route::get('points_exchange/index$', '\app\admin\controller\PointsExchangeController@index');
Route::post('points_exchange/audit$', '\app\admin\controller\PointsExchangeController@audit');
Route::get('points_exchange/detail/:id$', '\app\admin\controller\PointsExchangeController@detail');

// V2.5 多语言管理
Route::get('language/index$', '\app\admin\controller\LanguageController@index');
Route::rule('language/add$', '\app\admin\controller\LanguageController@add', 'GET|POST');
Route::rule('language/edit/:id$', '\app\admin\controller\LanguageController@edit', 'GET|POST');
Route::post('language/save$', '\app\admin\controller\LanguageController@save');
Route::post('language/delete$', '\app\admin\controller\LanguageController@delete');
Route::rule('language/translate$', '\app\admin\controller\LanguageController@translate', 'GET|POST');
Route::post('language/aiTranslate$', '\app\admin\controller\LanguageController@aiTranslate');

// V2.9.3 M28: 发布平台同步与Token刷新
Route::post('publish/sync$', '\app\admin\controller\PublishPlatformController@sync');
Route::post('publish/refreshTokens$', '\app\admin\controller\PublishPlatformController@refreshTokens');

// V2.9.4: 发布状态看板
Route::get('publish_log/index$', '\app\admin\controller\PublishLogController@index');
Route::post('publish_log/retry$', '\app\admin\controller\PublishLogController@retry');
Route::get('publish_log/summary$', '\app\admin\controller\PublishLogController@summary');

// V2.9.4: 内容质量检测
Route::post('quality_check/check$', '\app\admin\controller\QualityCheckController@check');

// V2.9.4: 支付配置
Route::get('pay_config/index$', '\app\admin\controller\PayConfigController@index');
Route::post('pay_config/save$', '\app\admin\controller\PayConfigController@save');

// V2.9.4: 订单管理
Route::get('order/index$', '\app\admin\controller\OrderController@index');
Route::get('order/detail/:id$', '\app\admin\controller\OrderController@detail');
Route::post('order/close$', '\app\admin\controller\OrderController@close');

// V2.9.4: 许可证管理
Route::get('license/index$', '\app\admin\controller\LicenseController@index');
Route::post('license/issue$', '\app\admin\controller\LicenseController@issue');
Route::post('license/revoke$', '\app\admin\controller\LicenseController@revoke');
Route::post('license/activate$', '\app\admin\controller\LicenseController@activate');

// V2.9.3 M25: 插件商店
Route::get('plugin_market/index$', '\app\admin\controller\PluginMarketController@index');
Route::get('plugin_market/detail/:code$', '\app\admin\controller\PluginMarketController@detail');
Route::post('plugin_market/install$', '\app\admin\controller\PluginMarketController@install');
Route::get('plugin_market/checkUpdates$', '\app\admin\controller\PluginMarketController@checkUpdates');
// V2.9.4: 插件评分
Route::post('plugin_market/rate$', '\app\admin\controller\PluginMarketController@rate');
Route::get('plugin_market/getRating$', '\app\admin\controller\PluginMarketController@getRating');

// V3.0 Phase 2/3: AI主题生成路由
Route::get('ai_theme/index$', '\app\admin\controller\AiThemeController@index');
Route::get('ai_theme/generate$', '\app\admin\controller\AiThemeController@generate');
Route::post('ai_theme/doGenerate$', '\app\admin\controller\AiThemeController@doGenerate');
Route::get('ai_theme/progress$', '\app\admin\controller\AiThemeController@progress');
Route::get('ai_theme/detail/:id$', '\app\admin\controller\AiThemeController@detail');
Route::post('ai_theme/approve$', '\app\admin\controller\AiThemeController@approve');
Route::post('ai_theme/publish$', '\app\admin\controller\AiThemeController@publish');
Route::post('ai_theme/reject$', '\app\admin\controller\AiThemeController@reject');
Route::post('ai_theme/retry$', '\app\admin\controller\AiThemeController@retry');
Route::get('ai_theme/preview_url$', '\app\admin\controller\AiThemeController@preview_url');
Route::get('ai_theme/tweak/:id$', '\app\admin\controller\AiThemeController@tweak');
Route::post('ai_theme/save_tweak$', '\app\admin\controller\AiThemeController@save_tweak');
Route::post('ai_theme/reset_tweak$', '\app\admin\controller\AiThemeController@reset_tweak');
// V3.0 Phase 3: AI模板增强路由
Route::post('ai_theme/chat$', '\app\admin\controller\AiThemeController@chat');
Route::post('ai_theme/regenerateFile$', '\app\admin\controller\AiThemeController@regenerateFile');
Route::post('ai_theme/rollback$', '\app\admin\controller\AiThemeController@rollback');
Route::get('ai_theme/versionHistory/:id$', '\app\admin\controller\AiThemeController@versionHistory');
Route::get('ai_theme/versionDiff$', '\app\admin\controller\AiThemeController@versionDiff');
Route::get('ai_theme/exportTheme/:id$', '\app\admin\controller\AiThemeController@exportTheme');
Route::post('ai_theme/importTheme$', '\app\admin\controller\AiThemeController@importTheme');
Route::get('ai_theme/manage$', '\app\admin\controller\AiThemeController@manage');
// V3.1-下一阶段 Sprint 14: 批量生成路由
Route::get('ai_theme/batch_generate$', '\app\admin\controller\AiThemeController@batchGenerate');
Route::post('ai_theme/doBatchGenerate$', '\app\admin\controller\AiThemeController@doBatchGenerate');
Route::get('ai_theme/batchProgress$', '\app\admin\controller\AiThemeController@batchProgress');
Route::post('ai_theme/qualityScore$', '\app\admin\controller\AiThemeController@qualityScore');

// V3.1 Sprint 15: 模板市场路由
Route::get('theme_market/index$', '\app\admin\controller\ThemeMarketController@index');
Route::get('theme_market/list$', '\app\admin\controller\ThemeMarketController@list');
Route::post('theme_market/refresh$', '\app\admin\controller\ThemeMarketController@refresh');
Route::post('theme_market/install$', '\app\admin\controller\ThemeMarketController@install');
Route::post('theme_market/switch$', '\app\admin\controller\ThemeMarketController@switch');
Route::post('theme_market/uninstall$', '\app\admin\controller\ThemeMarketController@uninstall');
Route::get('theme_market/previewUrl$', '\app\admin\controller\ThemeMarketController@previewUrl');
// V2.9.9 F-3: 本地模板市场API
Route::get('theme_market/localList$', '\app\admin\controller\ThemeMarketController@localList');
Route::get('theme_market/localDetail$', '\app\admin\controller\ThemeMarketController@localDetail');
// V2.9.9-R4: zip上传安装
Route::post('theme_market/uploadAndInstall$', '\app\admin\controller\ThemeMarketController@uploadAndInstall');
Route::get('theme_market/backups$', '\app\admin\controller\ThemeMarketController@backups');
Route::post('theme_market/rollback$', '\app\admin\controller\ThemeMarketController@rollback');
Route::post('theme_market/scan$', '\app\admin\controller\ThemeMarketController@scan');
Route::get('theme_market/checkUpdate$', '\app\admin\controller\ThemeMarketController@checkUpdate');
// V3.1 Sprint 16: 评分收藏+版本检测+日志+分类+详情
Route::post('theme_market/rate$', '\app\admin\controller\ThemeMarketController@rate');
Route::post('theme_market/favorite$', '\app\admin\controller\ThemeMarketController@favorite');
Route::get('theme_market/rateStats$', '\app\admin\controller\ThemeMarketController@rateStats');
Route::get('theme_market/updateBadge$', '\app\admin\controller\ThemeMarketController@updateBadge');
Route::get('theme_market/updateCheck$', '\app\admin\controller\ThemeMarketController@updateCheck');
Route::get('theme_market/detail$', '\app\admin\controller\ThemeMarketController@detail');
Route::get('theme_market/logs$', '\app\admin\controller\ThemeMarketController@logs');
Route::get('theme_market/logList$', '\app\admin\controller\ThemeMarketController@logList');
Route::get('theme_market/categories$', '\app\admin\controller\ThemeMarketController@categories');
Route::post('theme_market/saveCategory$', '\app\admin\controller\ThemeMarketController@saveCategory');
Route::post('theme_market/deleteCategory$', '\app\admin\controller\ThemeMarketController@deleteCategory');

// V2.9.7 Phase 1: 主题定制路由
Route::get('theme_custom/defaults$', '\app\admin\controller\ThemeCustomController@defaults');
Route::get('theme_custom/customization$', '\app\admin\controller\ThemeCustomController@customization');
Route::post('theme_custom/save$', '\app\admin\controller\ThemeCustomController@save');
Route::post('theme_custom/activate$', '\app\admin\controller\ThemeCustomController@activate');
Route::post('theme_custom/reset$', '\app\admin\controller\ThemeCustomController@reset');
Route::post('theme_custom/saveAs$', '\app\admin\controller\ThemeCustomController@saveAs');
Route::get('theme_custom/variants$', '\app\admin\controller\ThemeCustomController@variants');
Route::get('theme_custom/presets$', '\app\admin\controller\ThemeCustomController@presets');
Route::get('theme_custom/colorPresets$', '\app\admin\controller\ThemeCustomController@colorPresets');
Route::post('theme_custom/preview$', '\app\admin\controller\ThemeCustomController@preview');
Route::get('theme_custom/panel$', '\app\admin\controller\ThemeCustomController@panel');
Route::post('theme_custom/uploadLogo$', '\app\admin\controller\ThemeCustomController@uploadLogo');
Route::get('theme_custom/export$', '\app\admin\controller\ThemeCustomController@export');
Route::get('theme_custom/previewExport$', '\app\admin\controller\ThemeCustomController@previewExport');
Route::get('theme_custom/checkConflict$', '\app\admin\controller\ThemeCustomController@checkConflict');
Route::get('theme_custom/recommendPreset$', '\app\admin\controller\ThemeCustomController@recommendPreset');
Route::get('theme_custom/defaultVars$', '\app\admin\controller\ThemeCustomController@defaultVars');

// V2.9.7 Phase 3: 主题数据分析路由
Route::get('theme_analysis/index$', '\app\admin\controller\ThemeAnalysisController@index');
Route::get('theme_analysis/installRanking$', '\app\admin\controller\ThemeAnalysisController@installRanking');
Route::get('theme_analysis/installTrend$', '\app\admin\controller\ThemeAnalysisController@installTrend');
Route::get('theme_analysis/installTrendRange$', '\app\admin\controller\ThemeAnalysisController@installTrendRange');
Route::get('theme_analysis/customPreference$', '\app\admin\controller\ThemeAnalysisController@customPreference');
Route::get('theme_analysis/scoreDistribution$', '\app\admin\controller\ThemeAnalysisController@scoreDistribution');
Route::get('theme_analysis/exportCsv$', '\app\admin\controller\ThemeAnalysisController@exportCsv');

// V3.1: 数据看板来源分析
// 数据看板 API（DashboardController）
Route::get('dashboard/getSourceAnalysis$', '\app\admin\controller\DashboardController@getSourceAnalysis');
Route::get('dashboard/categoryStats$', '\app\admin\controller\DashboardController@categoryStats');
Route::get('dashboard/getRevenueStats$', '\app\admin\controller\DashboardController@getRevenueStats');
Route::get('dashboard/overview$', '\app\admin\controller\DashboardController@overview');
Route::get('dashboard/trend$', '\app\admin\controller\DashboardController@trend');
Route::get('dashboard/topContent$', '\app\admin\controller\DashboardController@topContent');
Route::get('dashboard/getMemberGrowth$', '\app\admin\controller\DashboardController@getMemberGrowth');
Route::get('dashboard/getContentRank$', '\app\admin\controller\DashboardController@getContentRank');
Route::get('dashboard/getDeadLinkStats$', '\app\admin\controller\DashboardController@getDeadLinkStats');
Route::get('dashboard/exportExcel$', '\app\admin\controller\DashboardController@exportExcel');

// V2.9.9 B-1/E-2: 运营报表 + CSV导出
Route::get('dashboard/dataOperations$', '\app\admin\controller\DashboardController@dataOperations');
Route::get('dashboard/getOperationsReport$', '\app\admin\controller\DashboardController@getOperationsReport');
Route::get('dashboard/exportOperationsCsv$', '\app\admin\controller\DashboardController@exportOperationsCsv');
Route::get('dashboard/getDauMau$', '\app\admin\controller\DashboardController@getDauMau');

// V2.9.9 B-2: 流量增强
Route::get('dashboard/getBounceRate$', '\app\admin\controller\DashboardController@getBounceRate');
Route::get('dashboard/getBrowserStats$', '\app\admin\controller\DashboardController@getBrowserStats');
Route::get('dashboard/getTopContentWithDuration$', '\app\admin\controller\DashboardController@getTopContentWithDuration');
Route::get('dashboard/getMetricTrend$', '\app\admin\controller\DashboardController@getMetricTrend');
Route::get('traffic/getBounceRate$', '\app\admin\controller\TrafficController@getBounceRate');
Route::get('traffic/getBrowserStats$', '\app\admin\controller\TrafficController@getBrowserStats');
Route::get('traffic/getTopContentWithDuration$', '\app\admin\controller\TrafficController@getTopContentWithDuration');
Route::get('traffic/getDauMau$', '\app\admin\controller\TrafficController@getDauMau');

// V2.9.9: 工作流审批路由（补全缺失）
Route::get('workflow/index$', '\app\admin\controller\WorkflowController@index');
Route::get('workflow/edit$', '\app\admin\controller\WorkflowController@edit');
Route::rule('workflow/edit/:id$', '\app\admin\controller\WorkflowController@edit', 'GET|POST');
Route::post('workflow/save$', '\app\admin\controller\WorkflowController@save');
Route::post('workflow/delete$', '\app\admin\controller\WorkflowController@delete');
Route::get('workflow/records$', '\app\admin\controller\WorkflowController@records');
Route::post('workflow/review$', '\app\admin\controller\WorkflowController@review');

// V2.9.9: 会员等级路由（补全缺失）
Route::get('member_level/index$', '\app\admin\controller\MemberLevelController@index');
Route::rule('member_level/add$', '\app\admin\controller\MemberLevelController@add', 'GET|POST');
Route::rule('member_level/edit/:id$', '\app\admin\controller\MemberLevelController@edit', 'GET|POST');
Route::post('member_level/save$', '\app\admin\controller\MemberLevelController@save');
Route::post('member_level/delete$', '\app\admin\controller\MemberLevelController@delete');

// V2.9.12: 模板商店路由（管理员）
Route::get('template_store/index$', '\app\admin\controller\TemplateStoreController@index');
Route::rule('template_store/add$', '\app\admin\controller\TemplateStoreController@add', 'GET|POST');
Route::rule('template_store/edit/:id$', '\app\admin\controller\TemplateStoreController@edit', 'GET|POST');
Route::post('template_store/delete/:id$', '\app\admin\controller\TemplateStoreController@delete');
Route::post('template_store/publish/:id$', '\app\admin\controller\TemplateStoreController@publish');
Route::post('template_store/unpublish/:id$', '\app\admin\controller\TemplateStoreController@unpublish');
Route::post('template_store/toggleFeatured/:id$', '\app\admin\controller\TemplateStoreController@toggleFeatured');
Route::get('template_store/categories$', '\app\admin\controller\TemplateStoreController@categories');
Route::post('template_store/saveCategory$', '\app\admin\controller\TemplateStoreController@saveCategory');
Route::post('template_store/saveTemplateCategories$', '\app\admin\controller\TemplateStoreController@saveTemplateCategories');
Route::post('template_store/deleteCategory/:id$', '\app\admin\controller\TemplateStoreController@deleteCategory');
// V2.9.12: 模板商店路由（网站主）
Route::get('template_store/market$', '\app\admin\controller\TemplateStoreController@market');
Route::get('template_store/list$', '\app\admin\controller\TemplateStoreController@list');
Route::get('template_store/detail/:id$', '\app\admin\controller\TemplateStoreController@detail');
Route::get('template_store/preview/:slug$', '\app\admin\controller\TemplateStoreController@preview');
Route::get('template_store/my_templates$', '\app\admin\controller\TemplateStoreController@my_templates');
Route::post('template_store/doInstall/:id$', '\app\admin\controller\TemplateStoreController@doInstall');
Route::post('template_store/doActivate/:id$', '\app\admin\controller\TemplateStoreController@doActivate');
Route::post('template_store/buy/:id$', '\app\admin\controller\TemplateStoreController@buy');
Route::post('template_store/generateVariants/:id$', '\app\admin\controller\TemplateStoreController@generateVariants');
// V2.9.12: 评分评论
Route::post('template_store/submitReview/:id$', '\app\admin\controller\TemplateStoreController@submitReview');
Route::get('template_store/reviews$', '\app\admin\controller\TemplateStoreController@reviews');
Route::post('template_store/auditReview/:id$', '\app\admin\controller\TemplateStoreController@auditReview');
Route::post('template_store/deleteReview/:id$', '\app\admin\controller\TemplateStoreController@deleteReview');
// V2.9.12: 备份还原
Route::get('template_store/backups$', '\app\admin\controller\TemplateStoreController@backups');
Route::post('template_store/doBackup$', '\app\admin\controller\TemplateStoreController@doBackup');
Route::post('template_store/doRollback$', '\app\admin\controller\TemplateStoreController@doRollback');
// V2.9.12: 打包导出 + 上传
Route::get('template_store/exportTheme/:id$', '\app\admin\controller\TemplateStoreController@exportTheme');
Route::post('template_store/uploadTheme$', '\app\admin\controller\TemplateStoreController@uploadTheme');
// V2.9.12: 版本管理
Route::get('template_store/versionHistory$', '\app\admin\controller\TemplateStoreController@versionHistory');
// ===== V2.9.33 Sprint AI5: 内容质量闭环 =====
Route::get('content_quality/score/:id$', '\app\admin\controller\ContentQualityController@score');
Route::post('content_quality/batch_score$', '\app\admin\controller\ContentQualityController@batchScore');
Route::post('content_quality/repair/:id$', '\app\admin\controller\ContentQualityController@repair');
Route::post('content_quality/batch_repair$', '\app\admin\controller\ContentQualityController@batchRepair');
Route::get('content_quality/dashboard$', '\app\admin\controller\ContentQualityController@dashboard');
Route::get('content_quality/export$', '\app\admin\controller\ContentQualityController@export');

// ===== V2.9.33 Sprint CUS3: 白名单 + 组件库 =====
Route::get('custom_whitelist/index$', '\app\admin\controller\CustomWhitelistController@index');
Route::post('custom_whitelist/save$', '\app\admin\controller\CustomWhitelistController@save');
Route::post('custom_whitelist/check$', '\app\admin\controller\CustomWhitelistController@check');
Route::post('custom_whitelist/delete/:id$', '\app\admin\controller\CustomWhitelistController@delete');
Route::get('template_component/index$', '\app\admin\controller\TemplateComponentController@index');
Route::post('template_component/save$', '\app\admin\controller\TemplateComponentController@save');

// ===== V2.9.33 Sprint OPS: 运营与数据增强 =====
Route::get('system_health/index$', '\app\admin\controller\SystemHealthController@index');
Route::get('version_guide/show$', '\app\admin\controller\VersionGuideController@show');
Route::post('version_guide/dismiss$', '\app\admin\controller\VersionGuideController@dismiss');

// ===== V2.9.33 Sprint T5: 模板商店运营增强 =====
Route::get('template_promotion_activity/index$', '\app\admin\controller\TemplatePromotionActivityController@index');
Route::post('template_promotion_activity/save$', '\app\admin\controller\TemplatePromotionActivityController@save');
Route::post('template_promotion_activity/terminate/:id$', '\app\admin\controller\TemplatePromotionActivityController@terminate');
Route::get('template_promotion_activity/effect/:id$', '\app\admin\controller\TemplatePromotionActivityController@effect');
Route::get('template_category/index$', '\app\admin\controller\TemplateCategoryController@index');
Route::post('template_category/save$', '\app\admin\controller\TemplateCategoryController@save');

// ===== V2.9.33 Sprint CUS3: 响应式编辑 =====
Route::get('template_responsive/edit/:templateId$', '\app\admin\controller\TemplateResponsiveController@edit');
Route::post('template_responsive/save/:templateId$', '\app\admin\controller\TemplateResponsiveController@save');

// ===== V2.9.34 Sprint ML: 多语言内容管理完善 =====
Route::get('lang_site/index$', '\app\admin\controller\LangSiteController@index');
Route::post('lang_site/save$', '\app\admin\controller\LangSiteController@save');
Route::post('lang_site/delete/:id$', '\app\admin\controller\LangSiteController@delete');
Route::post('lang_site/toggle$', '\app\admin\controller\LangSiteController@toggle');
Route::get('lang_seo/index$', '\app\admin\controller\LangSeoController@index');
Route::post('lang_seo/save$', '\app\admin\controller\LangSeoController@save');
Route::post('lang_seo/generate_sitemap$', '\app\admin\controller\LangSeoController@generateSitemap');
Route::get('translate_workbench/index$', '\app\admin\controller\TranslateWorkbenchController@index');
Route::post('translate_workbench/translate$', '\app\admin\controller\TranslateWorkbenchController@translate');
Route::post('translate_workbench/batch_translate$', '\app\admin\controller\TranslateWorkbenchController@batchTranslate');
Route::post('translate_workbench/save_translation$', '\app\admin\controller\TranslateWorkbenchController@saveTranslation');
Route::get('translate_workbench/sync_status$', '\app\admin\controller\TranslateWorkbenchController@syncStatus');
Route::post('translate_workbench/resolve_conflict$', '\app\admin\controller\TranslateWorkbenchController@resolveConflict');

// ===== V2.9.34 Sprint CD: 内容分发渠道增强 =====
Route::get('channel_config/index$', '\app\admin\controller\ChannelConfigController@index');
Route::post('channel_config/save_wechat$', '\app\admin\controller\ChannelConfigController@saveWechat');
Route::post('channel_config/save_platform$', '\app\admin\controller\ChannelConfigController@savePlatform');
Route::get('channel_config/test$', '\app\admin\controller\ChannelConfigController@test');
Route::get('distribution_log/index$', '\app\admin\controller\DistributionLogController@index');
Route::get('distribution_schedule/index$', '\app\admin\controller\DistributionScheduleController@index');
Route::post('distribution_schedule/create$', '\app\admin\controller\DistributionScheduleController@create');
Route::post('distribution_schedule/cancel/:id$', '\app\admin\controller\DistributionScheduleController@cancel');
Route::post('distribution_schedule/execute/:id$', '\app\admin\controller\DistributionScheduleController@execute');
Route::post('distribution_schedule/save_auto_rule$', '\app\admin\controller\DistributionScheduleController@saveAutoRule');
Route::post('distribution_schedule/publish_wechat$', '\app\admin\controller\DistributionScheduleController@publishWechat');
Route::post('distribution_schedule/publish_platform$', '\app\admin\controller\DistributionScheduleController@publishPlatform');

// ===== V2.9.34 Sprint MEM: 会员体系与内容付费 =====
Route::get('member_level/index$', '\app\admin\controller\MemberLevelController@index');
Route::post('member_level/save$', '\app\admin\controller\MemberLevelController@save');
Route::post('member_level/manual_adjust$', '\app\admin\controller\MemberLevelController@manualAdjust');
Route::get('member_level/calculate$', '\app\admin\controller\MemberLevelController@calculate');
Route::get('member_points/index$', '\app\admin\controller\MemberPointsController@index');
Route::post('member_points/adjust$', '\app\admin\controller\MemberPointsController@adjust');
Route::get('paid_content/index$', '\app\admin\controller\PaidContentController@index');
Route::post('paid_content/save$', '\app\admin\controller\PaidContentController@save');
Route::get('paid_content/preview$', '\app\admin\controller\PaidContentController@preview');
Route::get('paid_content/check_purchased$', '\app\admin\controller\PaidContentController@checkPurchased');
Route::get('subscription_manage/index$', '\app\admin\controller\SubscriptionManageController@index');
Route::get('subscription_manage/check_vip$', '\app\admin\controller\SubscriptionManageController@checkVip');
Route::post('subscription_manage/unsubscribe/:id$', '\app\admin\controller\SubscriptionManageController@unsubscribe');
Route::get('member_benefits/index$', '\app\admin\controller\MemberBenefitsController@index');
Route::get('member_benefits/usage_records$', '\app\admin\controller\MemberBenefitsController@usageRecords');

// ===== V2.9.34 Sprint DR: 数据报表增强 =====
Route::get('report/index$', '\app\admin\controller\ReportController@index');
Route::post('report/save$', '\app\admin\controller\ReportController@save');
Route::post('report/generate$', '\app\admin\controller\ReportController@generate');
Route::get('report/detail/:id$', '\app\admin\controller\ReportController@detail');
Route::post('report/publish/:id$', '\app\admin\controller\ReportController@publish');
Route::post('report/delete/:id$', '\app\admin\controller\ReportController@delete');
Route::get('report/analysis$', '\app\admin\controller\ContentAnalysisController@index');
Route::get('report/production$', '\app\admin\controller\ContentAnalysisController@production');
Route::get('report/consumption$', '\app\admin\controller\ContentAnalysisController@consumption');
Route::get('report/interaction$', '\app\admin\controller\ContentAnalysisController@interaction');
Route::get('report/seo$', '\app\admin\controller\ContentAnalysisController@seo');
Route::get('dashboard_screen/index$', '\app\admin\controller\DashboardScreenController@index');
Route::get('dashboard_screen/content$', '\app\admin\controller\DashboardScreenController@contentScreen');
Route::get('dashboard_screen/user$', '\app\admin\controller\DashboardScreenController@userScreen');
Route::get('dashboard_screen/revenue$', '\app\admin\controller\DashboardScreenController@revenueScreen');
Route::get('export_center/index$', '\app\admin\controller\ExportCenterController@index');
Route::post('export_center/export$', '\app\admin\controller\ExportCenterController@export');
Route::get('export_center/download$', '\app\admin\controller\ExportCenterController@download');
Route::post('export_center/create_scheduled$', '\app\admin\controller\ExportCenterController@createScheduledExport');

// ===== V2.9.34 Sprint OPS2: 内容运营中台 =====
Route::get('operation_workbench/index$', '\app\admin\controller\OperationWorkbenchController@index');
Route::get('operation_workbench/weekly_report$', '\app\admin\controller\OperationWorkbenchController@weeklyReport');
Route::get('operation_workbench/calendar$', '\app\admin\controller\OperationWorkbenchController@calendar');
Route::get('content_lifecycle/index$', '\app\admin\controller\ContentLifecycleController@index');
Route::post('content_lifecycle/transition$', '\app\admin\controller\ContentLifecycleController@transition');
Route::post('content_lifecycle/batch_transition$', '\app\admin\controller\ContentLifecycleController@batchTransition');
Route::post('content_lifecycle/archive$', '\app\admin\controller\ContentLifecycleController@archive');
Route::post('content_lifecycle/restore/:id$', '\app\admin\controller\ContentLifecycleController@restore');
Route::post('content_lifecycle/empty_trash$', '\app\admin\controller\ContentLifecycleController@emptyTrash');
Route::get('operation_task/index$', '\app\admin\controller\OperationTaskController@index');
Route::post('operation_task/save$', '\app\admin\controller\OperationTaskController@save');
Route::post('operation_task/assign$', '\app\admin\controller\OperationTaskController@assign');
Route::post('operation_task/update_status$', '\app\admin\controller\OperationTaskController@updateStatus');
Route::get('operation_assistant/index$', '\app\admin\controller\OperationAssistantController@index');
Route::get('operation_assistant/daily_report$', '\app\admin\controller\OperationAssistantController@dailyReport');
Route::get('operation_assistant/weekly_report$', '\app\admin\controller\OperationAssistantController@weeklyReport');
Route::get('operation_assistant/monthly_report$', '\app\admin\controller\OperationAssistantController@monthlyReport');
Route::get('operation_assistant/suggestions$', '\app\admin\controller\OperationAssistantController@suggestions');
Route::get('operation_assistant/smart_alerts$', '\app\admin\controller\OperationAssistantController@smartAlerts');
Route::get('operation_assistant/knowledge_base$', '\app\admin\controller\OperationAssistantController@knowledgeBase');

// V2.9.31 Sprint CUS: 布局预设 + 配色方案
Route::get('template_store/layout_presets$', '\app\admin\controller\TemplateStoreController@layoutPresets');
Route::post('template_store/apply_layout_preset$', '\app\admin\controller\TemplateStoreController@applyLayoutPreset');
Route::get('template_store/color_schemes$', '\app\admin\controller\TemplateStoreController@colorSchemes');
Route::post('template_store/apply_color_scheme$', '\app\admin\controller\TemplateStoreController@applyColorScheme');

// V2.9.31 Sprint T3: 推广活动 + 安装日志
Route::get('template_store/promotions$', '\app\admin\controller\TemplateStoreController@promotions');
Route::get('template_store/promotionAdd$', '\app\admin\controller\TemplateStoreController@promotionAdd');
Route::rule('template_store/promotionEdit/:id$', '\app\admin\controller\TemplateStoreController@promotionEdit', 'GET|POST');
Route::post('template_store/promotionSave$', '\app\admin\controller\TemplateStoreController@promotionSave');
Route::post('template_store/promotionToggle/:id$', '\app\admin\controller\TemplateStoreController@promotionToggle');
Route::post('template_store/promotionDelete/:id$', '\app\admin\controller\TemplateStoreController@promotionDelete');
Route::get('template_store/install_logs$', '\app\admin\controller\TemplateStoreController@installLogs');

// V2.9.20 A-2: 内容模型管理路由
Route::get('content_model/index$', '\app\admin\controller\ContentModelController@index');
Route::rule('content_model/edit/:id$', '\app\admin\controller\ContentModelController@edit', 'GET|POST');
Route::get('content_model/add$', '\app\admin\controller\ContentModelController@edit');
Route::post('content_model/delete/:id$', '\app\admin\controller\ContentModelController@delete');
Route::post('content_model/toggleStatus/:id$', '\app\admin\controller\ContentModelController@toggleStatus');
Route::post('content_model/saveField$', '\app\admin\controller\ContentModelController@saveField');
Route::post('content_model/deleteField/:id$', '\app\admin\controller\ContentModelController@deleteField');
Route::get('content_model/getFields/:modelId$', '\app\admin\controller\ContentModelController@getFields');

// V2.9.27 Sprint S: 内容模型差异化路由
Route::post('content/switchTemplate/:id$', '\app\admin\controller\ContentController@switchTemplate');
Route::get('content/getAvailableTemplates/:id$', '\app\admin\controller\ContentController@getAvailableTemplates');
Route::post('content/saveRelations/:id$', '\app\admin\controller\ContentController@saveRelations');
Route::get('content/getRelations/:id$', '\app\admin\controller\ContentController@getRelations');
// S-7 模型统计
Route::get('content_model_stats/index$', '\app\admin\controller\ContentModelStatsController@index');
Route::get('content_model_stats/getModelDetail$', '\app\admin\controller\ContentModelStatsController@getModelDetail');
Route::get('content_model_stats/getTrend$', '\app\admin\controller\ContentModelStatsController@getTrend');
Route::post('content_model_stats/refresh$', '\app\admin\controller\ContentModelStatsController@refresh');
// S-5 模型模板映射
Route::get('content_model_map/index$', '\app\admin\controller\ContentModelMapController@index');
Route::rule('content_model_map/edit/:id$', '\app\admin\controller\ContentModelMapController@edit', 'GET|POST');
Route::get('content_model_map/add$', '\app\admin\controller\ContentModelMapController@edit');
Route::post('content_model_map/delete/:id$', '\app\admin\controller\ContentModelMapController@delete');
Route::post('content_model_map/setDefault/:id$', '\app\admin\controller\ContentModelMapController@setDefault');
Route::post('content_model_map/toggleStatus/:id$', '\app\admin\controller\ContentModelMapController@toggleStatus');
// S-8 迁移工具
Route::get('content_model_migration/index$', '\app\admin\controller\ContentModelMigrationController@index');
Route::post('content_model_migration/batchAssign$', '\app\admin\controller\ContentModelMigrationController@batchAssign');
Route::post('content_model_migration/importFromType$', '\app\admin\controller\ContentModelMigrationController@importFromType');
Route::post('content_model_migration/initFields$', '\app\admin\controller\ContentModelMigrationController@initFields');

// V2.9.27 Sprint T: SSE监控路由
Route::get('sse_monitor/index$', '\app\admin\controller\SseMonitorController@index');
Route::post('sse_monitor/refresh$', '\app\admin\controller\SseMonitorController@refresh');
Route::post('sse_monitor/cleanup$', '\app\admin\controller\SseMonitorController@cleanup');
Route::get('sse_monitor/detail$', '\app\admin\controller\SseMonitorController@detail');

// V2.9.27 Sprint U: 模板商店商业化路由
Route::get('template_pricing/index$', '\app\admin\controller\TemplatePricingController@index');
Route::rule('template_pricing/edit/:templateId$', '\app\admin\controller\TemplatePricingController@edit', 'GET|POST');
Route::post('template_pricing/calculatePrice$', '\app\admin\controller\TemplatePricingController@calculatePrice');
Route::get('template_order_admin/index$', '\app\admin\controller\TemplateOrderAdminController@index');
Route::get('template_order_admin/detail/:id$', '\app\admin\controller\TemplateOrderAdminController@detail');
Route::post('template_order_admin/refund/:id$', '\app\admin\controller\TemplateOrderAdminController@refund');

// V2.9.27 Sprint V: 基础设施完善路由
Route::get('system_health/index$', '\app\admin\controller\SystemHealthController@index');

// V2.9.27 V-5: 备份定时配置路由
Route::get('backup/schedule$', '\app\admin\controller\BackupController@schedule');
Route::post('backup/saveSchedule$', '\app\admin\controller\BackupController@saveSchedule');
Route::post('backup/runScheduled$', '\app\admin\controller\BackupController@runScheduled');

// V2.9.20 B-3: 模板安装与分类管理路由
Route::get('template_install/index$', '\app\admin\controller\TemplateInstallController@index');
Route::get('template_install/market$', '\app\admin\controller\TemplateInstallController@market');
Route::get('template_install/search$', '\app\admin\controller\TemplateInstallController@search');
Route::get('template_install/hotTags$', '\app\admin\controller\TemplateInstallController@hotTags');
Route::get('template_install/my_templates$', '\app\admin\controller\TemplateInstallController@myTemplates');
Route::post('template_install/doInstall/:id$', '\app\admin\controller\TemplateInstallController@doInstall');
Route::post('template_install/doUninstall/:id$', '\app\admin\controller\TemplateInstallController@doUninstall');
Route::post('template_install/doActivate/:id$', '\app\admin\controller\TemplateInstallController@doActivate');
Route::get('template_install/categories$', '\app\admin\controller\TemplateInstallController@categories');
Route::post('template_install/saveCategory$', '\app\admin\controller\TemplateInstallController@saveCategory');
Route::post('template_install/deleteCategory/:id$', '\app\admin\controller\TemplateInstallController@deleteCategory');

// V2.9.9: AI内容模板路由（补全缺失）
Route::get('ai_template/index$', '\app\admin\controller\AiTemplateController@index');
Route::rule('ai_template/edit/:id$', '\app\admin\controller\AiTemplateController@edit', 'GET|POST');
Route::post('ai_template/save$', '\app\admin\controller\AiTemplateController@save');
Route::post('ai_template/delete/:id$', '\app\admin\controller\AiTemplateController@delete');
Route::get('ai_template/use/:id$', '\app\admin\controller\AiTemplateController@use');
Route::post('ai_template/preview$', '\app\admin\controller\AiTemplateController@preview');
Route::post('ai_template/submitBatch$', '\app\admin\controller\AiTemplateController@submitBatch');
Route::get('ai_template/progress$', '\app\admin\controller\AiTemplateController@progress');
Route::get('ai_template/ajaxProgress$', '\app\admin\controller\AiTemplateController@ajaxProgress');
// V2.9.9: AI自然语言生成字段配置
Route::post('ai_template/generateFields$', '\app\admin\controller\AiTemplateController@generateFields');

// V2.9.9: 社交分享管理路由
Route::get('social_share/index$', '\app\admin\controller\SocialShareController@index');
Route::get('social_share/stats$', '\app\admin\controller\SocialShareController@stats');
Route::get('social_share/config$', '\app\admin\controller\SocialShareController@config');
Route::post('social_share/saveConfig$', '\app\admin\controller\SocialShareController@saveConfig');

// V2.8: 邀请排行与明细
Route::get('invite/index$', '\app\admin\controller\InviteController@index');
Route::get('invite/detail/:id$', '\app\admin\controller\InviteController@detail');

// V2.9.10: 菜单管理
Route::get('menu_manager/index$', '\app\admin\controller\MenuManagerController@index');
Route::post('menu_manager/saveGroup$', '\app\admin\controller\MenuManagerController@saveGroup');
Route::post('menu_manager/saveItem$', '\app\admin\controller\MenuManagerController@saveItem');
Route::post('menu_manager/deleteGroup$', '\app\admin\controller\MenuManagerController@deleteGroup');
Route::post('menu_manager/deleteItem$', '\app\admin\controller\MenuManagerController@deleteItem');
Route::post('menu_manager/sort$', '\app\admin\controller\MenuManagerController@sort');
Route::post('menu_manager/toggleStatus$', '\app\admin\controller\MenuManagerController@toggleStatus');

// V2.9.12: 模板自定义路由
Route::get('template_customize/index/:slug$', '\app\admin\controller\TemplateCustomizeController@index');
Route::get('template_customize/index$', '\app\admin\controller\TemplateCustomizeController@index');
Route::post('template_customize/save$', '\app\admin\controller\TemplateCustomizeController@save');
Route::post('template_customize/saveLayout$', '\app\admin\controller\TemplateCustomizeController@saveLayout');
Route::get('template_customize/livePreview$', '\app\admin\controller\TemplateCustomizeController@livePreview');
Route::post('template_customize/uploadLogo$', '\app\admin\controller\TemplateCustomizeController@uploadLogo');
Route::get('template_customize/backupList/:slug$', '\app\admin\controller\TemplateCustomizeController@backupList');
Route::post('template_customize/createBackup$', '\app\admin\controller\TemplateCustomizeController@createBackup');
Route::post('template_customize/restore$', '\app\admin\controller\TemplateCustomizeController@restore');
Route::post('template_customize/deleteBackup$', '\app\admin\controller\TemplateCustomizeController@deleteBackup');
Route::post('template_customize/reset$', '\app\admin\controller\TemplateCustomizeController@reset');

// V2.9.15: AI翻译引擎路由
Route::post('translate/do/:id$', '\app\admin\controller\AiTranslateController@translate');
Route::post('translate/batch$', '\app\admin\controller\AiTranslateController@batchTranslate');
Route::get('translate/status/:id/:lang$', '\app\admin\controller\AiTranslateController@getStatus');
Route::post('translate/delete/:id/:lang$', '\app\admin\controller\AiTranslateController@delete');
Route::get('translate/list/:id$', '\app\admin\controller\AiTranslateController@list');

// V2.9.17 M-2: 翻译语言管理路由
Route::get('translate/languages$', '\app\admin\controller\TranslateLanguageController@index');
Route::get('translate/languages/list$', '\app\admin\controller\TranslateLanguageController@list');
Route::post('translate/languages/toggle$', '\app\admin\controller\TranslateLanguageController@toggle');
Route::post('translate/languages/batch$', '\app\admin\controller\TranslateLanguageController@batch');
Route::post('translate/languages/sort$', '\app\admin\controller\TranslateLanguageController@sort');
Route::post('translate/languages/custom$', '\app\admin\controller\TranslateLanguageController@custom');

// V2.9.17 E-2: 翻译SSE路由
Route::get('ai_translate/stream/:taskId$', '\app\admin\controller\AiTranslateController@stream');

// V2.9.12: 模板开发者审核路由（管理员）
Route::get('template_developer/index$', '\app\admin\controller\TemplateDeveloperAdminController@index');
Route::get('template_developer/detail/:id$', '\app\admin\controller\TemplateDeveloperAdminController@detail');
Route::post('template_developer/approve/:id$', '\app\admin\controller\TemplateDeveloperAdminController@approve');
Route::post('template_developer/reject/:id$', '\app\admin\controller\TemplateDeveloperAdminController@reject');
Route::post('template_developer/delete/:id$', '\app\admin\controller\TemplateDeveloperAdminController@delete');
// V2.9.13 H-1: 网站主上传入口
Route::rule('template_developer/upload$', '\app\admin\controller\TemplateDeveloperAdminController@uploadPage', 'GET|POST');

// ========== V2.9.18: 推送通道管理 ==========
Route::get('push/channel$', '\app\admin\controller\PushChannelController@index');
Route::get('push/channel/list$', '\app\admin\controller\PushChannelController@list');
Route::rule('push/channel/add$', '\app\admin\controller\PushChannelController@add', 'GET|POST');
Route::rule('push/channel/edit/:id$', '\app\admin\controller\PushChannelController@edit', 'GET|POST');
Route::post('push/channel/delete/:id$', '\app\admin\controller\PushChannelController@delete');
Route::post('push/channel/test/:id$', '\app\admin\controller\PushChannelController@test');
Route::get('push/log$', '\app\admin\controller\PushLogController@index');
Route::get('push/log/list$', '\app\admin\controller\PushLogController@list');
Route::post('push/log/retry/:id$', '\app\admin\controller\PushLogController@retry');
Route::post('push/dispatch/:contentId$', '\app\admin\controller\PushChannelController@dispatch');
Route::post('push/dispatch/channel$', '\app\admin\controller\PushChannelController@dispatchChannel');
// V2.9.19 D-1d: 推送健康检查 + 重试队列
Route::get('push/health$', '\app\admin\controller\PushChannelController@health');
Route::get('push/retryStats$', '\app\admin\controller\PushChannelController@retryStats');
Route::get('push/retry$', '\app\admin\controller\PushRetryController@index');
Route::get('push/retry/list$', '\app\admin\controller\PushRetryController@list');
Route::post('push/retry/process$', '\app\admin\controller\PushRetryController@process');
Route::post('push/retry/cleanup$', '\app\admin\controller\PushRetryController@cleanup');

// ========== V2.9.18: 邮件订阅管理 ==========
Route::get('subscriber/index$', '\app\admin\controller\SubscriberController@index');
Route::get('subscriber/list$', '\app\admin\controller\SubscriberController@list');
Route::post('subscriber/add$', '\app\admin\controller\SubscriberController@add');
Route::post('subscriber/delete$', '\app\admin\controller\SubscriberController@delete');
Route::get('subscriber/export$', '\app\admin\controller\SubscriberController@export');
// V2.9.19 S-1c: CSV导入 + 标记无效/恢复有效 + 标签列表
Route::post('subscriber/import$', '\app\admin\controller\SubscriberController@import');
Route::post('subscriber/markInvalid$', '\app\admin\controller\SubscriberController@markInvalid');
Route::post('subscriber/restoreValid$', '\app\admin\controller\SubscriberController@restoreValid');
Route::get('subscriber/tagOptions$', '\app\admin\controller\SubscriberController@tagOptions');
// V2.9.19 S-1b: 退订分析面板
Route::get('subscriber/analysis$', '\app\admin\controller\UnsubscribeAnalysisController@index');
Route::get('subscriber/analysis/overview$', '\app\admin\controller\UnsubscribeAnalysisController@overview');
Route::get('subscriber/analysis/trend$', '\app\admin\controller\UnsubscribeAnalysisController@trend');
// V2.9.19 S-1d: 邮件日志统计增强
Route::get('mail_log/index$', '\app\admin\controller\MailLogController@index');
Route::get('mail_log/list$', '\app\admin\controller\MailLogController@list');
Route::get('mail_log/overview$', '\app\admin\controller\MailLogController@overview');
Route::get('mail_log/stats$', '\app\admin\controller\MailLogController@stats');

// V2.9.21 D-2: 邮件统计看板
Route::get('mail_log/statistics$', '\app\admin\controller\MailLogController@statistics');
Route::get('mail_log/statistics_data$', '\app\admin\controller\MailLogController@statisticsData');

// ========== V2.9.18: 通知管理 ==========
Route::get('notify/send$', '\app\admin\controller\NotifyController@sendPage');
Route::post('notify/send$', '\app\admin\controller\NotifyController@doSend');
Route::get('notify/history$', '\app\admin\controller\NotifyController@history');
// V2.9.20 C-4: 通知默认设置
Route::get('notification/setting$', '\app\admin\controller\NotificationSettingController@index');
Route::post('notification/setting/save$', '\app\admin\controller\NotificationSettingController@save');

// V2.9.24 G-1~G-5: 模板商店运营（Banner/推荐位/统计/评论批量管理）
Route::rule('template_store_ops/bannerIndex$', '\app\admin\controller\TemplateStoreOpsController@bannerIndex', 'GET');
Route::rule('template_store_ops/bannerEdit/:id$', '\app\admin\controller\TemplateStoreOpsController@bannerEdit', 'GET|POST');
Route::post('template_store_ops/bannerDelete/:id$', '\app\admin\controller\TemplateStoreOpsController@bannerDelete');
Route::post('template_store_ops/bannerSort$', '\app\admin\controller\TemplateStoreOpsController@bannerSort');
Route::rule('template_store_ops/recommendIndex$', '\app\admin\controller\TemplateStoreOpsController@recommendIndex', 'GET');
Route::rule('template_store_ops/recommendEdit/:id$', '\app\admin\controller\TemplateStoreOpsController@recommendEdit', 'GET|POST');
Route::post('template_store_ops/recommendDelete/:id$', '\app\admin\controller\TemplateStoreOpsController@recommendDelete');
Route::rule('template_store_ops/statsDashboard$', '\app\admin\controller\TemplateStoreOpsController@statsDashboard', 'GET');
Route::get('template_store_ops/statsExport$', '\app\admin\controller\TemplateStoreOpsController@statsExport');
Route::post('template_store_ops/migrateBaseline$', '\app\admin\controller\TemplateStoreOpsController@migrateBaseline');
Route::rule('template_store_ops/reviewBatch$', '\app\admin\controller\TemplateStoreOpsController@reviewBatch', 'GET');
Route::post('template_store_ops/reviewBatchAudit$', '\app\admin\controller\TemplateStoreOpsController@reviewBatchAudit');
Route::post('template_store_ops/reviewBatchDelete$', '\app\admin\controller\TemplateStoreOpsController@reviewBatchDelete');

// V2.9.26 P-1: AI模板智能推荐系统路由
Route::get('template_store_ops/recommendRuleIndex$', '\app\admin\controller\TemplateStoreOpsController@recommendRuleIndex');
Route::rule('template_store_ops/recommendRuleEdit/:id$', '\app\admin\controller\TemplateStoreOpsController@recommendRuleEdit', 'GET|POST');
Route::post('template_store_ops/recommendRuleDelete/:id$', '\app\admin\controller\TemplateStoreOpsController@recommendRuleDelete');
Route::post('template_store_ops/recommendRuleToggle/:id$', '\app\admin\controller\TemplateStoreOpsController@recommendRuleToggle');
Route::get('template_store_ops/recommendStats$', '\app\admin\controller\TemplateStoreOpsController@recommendStats');
Route::get('template_store_ops/recommendPreview$', '\app\admin\controller\TemplateStoreOpsController@recommendPreview');

// V2.9.26 P-3: 审核流程路由
Route::get('template_store_ops/auditPending$', '\app\admin\controller\TemplateStoreOpsController@auditPendingList');
Route::post('template_store_ops/auditApprove/:id$', '\app\admin\controller\TemplateStoreOpsController@auditApprove');
Route::post('template_store_ops/auditReject/:id$', '\app\admin\controller\TemplateStoreOpsController@auditReject');
Route::get('template_store_ops/auditHistory/:id$', '\app\admin\controller\TemplateStoreOpsController@auditHistory');
Route::get('template_store_ops/rejectReasons$', '\app\admin\controller\TemplateStoreOpsController@rejectReasons');

// V2.9.26 P-4: 定价促销路由
Route::get('template_store_ops/promotionIndex$', '\app\admin\controller\TemplateStoreOpsController@promotionIndex');
Route::rule('template_store_ops/promotionEdit/:id$', '\app\admin\controller\TemplateStoreOpsController@promotionEdit', 'GET|POST');
Route::post('template_store_ops/promotionDelete/:id$', '\app\admin\controller\TemplateStoreOpsController@promotionDelete');
Route::get('template_store_ops/couponIndex$', '\app\admin\controller\TemplateStoreOpsController@couponIndex');
Route::rule('template_store_ops/couponEdit/:id$', '\app\admin\controller\TemplateStoreOpsController@couponEdit', 'GET|POST');
Route::post('template_store_ops/couponDelete/:id$', '\app\admin\controller\TemplateStoreOpsController@couponDelete');
Route::get('template_store_ops/priceHistory/:id$', '\app\admin\controller\TemplateStoreOpsController@priceHistory');

// V2.9.26 P-5/P-6/P-7: 质量+版本+报表路由
Route::get('template_store_ops/qualityIndex$', '\app\admin\controller\TemplateStoreOpsController@qualityIndex');
Route::post('template_store_ops/qualityAutoScore/:id$', '\app\admin\controller\TemplateStoreOpsController@qualityAutoScore');
Route::post('template_store_ops/qualityAddTag/:id$', '\app\admin\controller\TemplateStoreOpsController@qualityAddTag');
Route::get('template_store_ops/versionHistory/:id$', '\app\admin\controller\TemplateStoreOpsController@versionHistoryPage');
Route::post('template_store_ops/versionCreate/:id$', '\app\admin\controller\TemplateStoreOpsController@versionCreate');
Route::post('template_store_ops/versionPublish/:id$', '\app\admin\controller\TemplateStoreOpsController@versionPublish');
Route::post('template_store_ops/versionRollback/:id$', '\app\admin\controller\TemplateStoreOpsController@versionRollback');
Route::get('template_store_ops/analyticsDashboard$', '\app\admin\controller\TemplateStoreOpsController@analyticsDashboard');
Route::get('template_store_ops/analyticsCategory$', '\app\admin\controller\TemplateStoreOpsController@analyticsCategory');
Route::get('template_store_ops/analyticsExport$', '\app\admin\controller\TemplateStoreOpsController@analyticsExport');

// V2.9.28 Sprint M: 模板商店后台管理完善路由
// M-1: 订单管理增强（退款/发票）
Route::get('template_order_admin/index$', '\app\admin\controller\TemplateOrderAdminController@index');
Route::get('template_order_admin/detail/:id$', '\app\admin\controller\TemplateOrderAdminController@detail');
Route::get('template_order_admin/refundHandle/:id$', '\app\admin\controller\TemplateOrderAdminController@refundHandle');
Route::post('template_order_admin/approveRefund/:id$', '\app\admin\controller\TemplateOrderAdminController@approveRefund');
Route::post('template_order_admin/rejectRefund/:id$', '\app\admin\controller\TemplateOrderAdminController@rejectRefund');
Route::post('template_order_admin/refund/:id$', '\app\admin\controller\TemplateOrderAdminController@refund');
Route::get('template_order_admin/invoiceList$', '\app\admin\controller\TemplateOrderAdminController@invoiceList');
Route::post('template_order_admin/issueInvoice/:id$', '\app\admin\controller\TemplateOrderAdminController@issueInvoice');
Route::post('template_order_admin/rejectInvoice/:id$', '\app\admin\controller\TemplateOrderAdminController@rejectInvoice');
// M-2: 评价管理
Route::get('template_review_admin/index$', '\app\admin\controller\TemplateReviewAdminController@index');
Route::get('template_review_admin/reply/:id$', '\app\admin\controller\TemplateReviewAdminController@reply');
Route::post('template_review_admin/saveReply/:id$', '\app\admin\controller\TemplateReviewAdminController@saveReply');
Route::post('template_review_admin/audit/:id$', '\app\admin\controller\TemplateReviewAdminController@audit');
Route::get('template_review_admin/reports$', '\app\admin\controller\TemplateReviewAdminController@reports');
Route::post('template_review_admin/handleReport/:id$', '\app\admin\controller\TemplateReviewAdminController@handleReport');
Route::get('template_review_admin/stats$', '\app\admin\controller\TemplateReviewAdminController@stats');
// M-3: 统计看板
Route::get('template_store_stats/index$', '\app\admin\controller\TemplateStoreStatsController@index');
Route::get('template_store_stats/ranking$', '\app\admin\controller\TemplateStoreStatsController@ranking');
Route::get('template_store_stats/revenueTrend$', '\app\admin\controller\TemplateStoreStatsController@revenueTrend');
Route::post('template_store_stats/aggregate$', '\app\admin\controller\TemplateStoreStatsController@aggregate');
// M-4: 模板包管理
Route::get('template_pack/index$', '\app\admin\controller\TemplatePackController@index');
Route::rule('template_pack/edit/:id$', '\app\admin\controller\TemplatePackController@edit', 'GET|POST');
Route::get('template_pack/add$', '\app\admin\controller\TemplatePackController@edit');
Route::post('template_pack/delete/:id$', '\app\admin\controller\TemplatePackController@delete');
// M-5: 审核工作流
Route::get('template_audit_workflow/index$', '\app\admin\controller\TemplateAuditWorkflowController@index');
Route::get('template_audit_workflow/detail/:id$', '\app\admin\controller\TemplateAuditWorkflowController@detail');
Route::post('template_audit_workflow/firstPass/:id$', '\app\admin\controller\TemplateAuditWorkflowController@firstPass');
Route::post('template_audit_workflow/finalPass/:id$', '\app\admin\controller\TemplateAuditWorkflowController@finalPass');
Route::post('template_audit_workflow/reject/:id$', '\app\admin\controller\TemplateAuditWorkflowController@reject');
Route::get('template_audit_workflow/diff/:id$', '\app\admin\controller\TemplateAuditWorkflowController@diff');
Route::post('template_audit_workflow/saveConfig/:id$', '\app\admin\controller\TemplateAuditWorkflowController@saveConfig');
// M-6: 推荐位管理
Route::get('template_recommend_position/index$', '\app\admin\controller\TemplateRecommendPositionController@index');
Route::rule('template_recommend_position/edit/:id$', '\app\admin\controller\TemplateRecommendPositionController@edit', 'GET|POST');
Route::get('template_recommend_position/add$', '\app\admin\controller\TemplateRecommendPositionController@edit');
Route::post('template_recommend_position/delete/:id$', '\app\admin\controller\TemplateRecommendPositionController@delete');
// M-7: 结算管理
Route::get('template_settlement_admin/index$', '\app\admin\controller\TemplateSettlementAdminController@index');
Route::rule('template_settlement_admin/rule$', '\app\admin\controller\TemplateSettlementAdminController@rule', 'GET|POST');
Route::get('template_settlement_admin/withdrawList$', '\app\admin\controller\TemplateSettlementAdminController@withdrawList');
Route::post('template_settlement_admin/approveWithdraw/:id$', '\app\admin\controller\TemplateSettlementAdminController@approveWithdraw');
Route::post('template_settlement_admin/confirmWithdraw/:id$', '\app\admin\controller\TemplateSettlementAdminController@confirmWithdraw');
Route::post('template_settlement_admin/rejectWithdraw/:id$', '\app\admin\controller\TemplateSettlementAdminController@rejectWithdraw');
Route::get('template_settlement_admin/monthlyReport$', '\app\admin\controller\TemplateSettlementAdminController@monthlyReport');
// M-8: 商店SEO
Route::get('template_store_seo/index$', '\app\admin\controller\TemplateStoreSeoController@index');
Route::post('template_store_seo/save$', '\app\admin\controller\TemplateStoreSeoController@save');
Route::get('template_store_seo/templateList$', '\app\admin\controller\TemplateStoreSeoController@templateList');
Route::rule('template_store_seo/editTemplateSeo/:id$', '\app\admin\controller\TemplateStoreSeoController@editTemplateSeo', 'GET|POST');

// V2.9.26 R-1~R-5: AI编辑器增强路由
Route::post('ai_content/continue$', '\app\admin\controller\AiContentController@continueWriting');
Route::post('ai_content/rewrite$', '\app\admin\controller\AiContentController@rewrite');
Route::post('ai_content/expand$', '\app\admin\controller\AiContentController@expand');
Route::post('ai_content/summarize$', '\app\admin\controller\AiContentController@summarize');
// V2.9.28 Sprint A: AI编辑器增强路由
Route::post('ai_content/optimizeParagraph$', '\app\admin\controller\AiContentController@optimizeParagraph');
Route::post('ai_content/chat$', '\app\admin\controller\AiContentController@chat');
Route::get('ai_content/chatHistory$', '\app\admin\controller\AiContentController@chatHistory');
Route::get('ai_content/exportChat$', '\app\admin\controller\AiContentController@exportChat');
Route::post('ai_content/formatPreserveProcess$', '\app\admin\controller\AiContentController@formatPreserveProcess');
Route::post('ai_content/translate$', '\app\admin\controller\AiContentController@translate');
Route::get('ai_content/translateLanguages$', '\app\admin\controller\AiContentController@translateLanguages');
Route::get('ai_content/snapshotList$', '\app\admin\controller\AiContentController@snapshotList');
Route::get('ai_content/snapshotDiff$', '\app\admin\controller\AiContentController@snapshotDiff');
Route::post('ai_content/snapshotRollback$', '\app\admin\controller\AiContentController@snapshotRollback');
// V2.9.28 A-5: AI模板库
Route::get('ai_editor_template/index$', '\app\admin\controller\AiEditorTemplateController@index');
Route::rule('ai_editor_template/edit/:id$', '\app\admin\controller\AiEditorTemplateController@edit', 'GET|POST');
Route::get('ai_editor_template/add$', '\app\admin\controller\AiEditorTemplateController@edit');
Route::post('ai_editor_template/delete/:id$', '\app\admin\controller\AiEditorTemplateController@delete');
Route::post('ai_editor_template/useTemplate/:id$', '\app\admin\controller\AiEditorTemplateController@useTemplate');
// V2.9.28 A-8: AI配置管理
Route::get('ai_config/index$', '\app\admin\controller\AiConfigController@index');
Route::post('ai_config/save$', '\app\admin\controller\AiConfigController@save');
Route::get('ai_config/apiStats$', '\app\admin\controller\AiConfigController@apiStats');
Route::post('ai_translate/batch$', '\app\admin\controller\AiTranslateController@batchTranslateEnhanced');
Route::get('ai_translate/memoryStats$', '\app\admin\controller\AiTranslateController@memoryStats');
Route::get('ai_translate/glossaryStats$', '\app\admin\controller\AiTranslateController@glossaryStats');
Route::get('ai_theme/cssPresets$', '\app\admin\controller\AiThemeController@cssPresets');
Route::post('ai_theme/generatePreset$', '\app\admin\controller\AiThemeController@generatePreset');
Route::post('ai_theme/applyCss/:id$', '\app\admin\controller\AiThemeController@applyCss');
Route::post('ai_seo/analyze$', '\app\admin\controller\AiSeoController@analyze');
Route::post('ai_seo/keywords$', '\app\admin\controller\AiSeoController@suggestKeywords');
Route::post('ai_seo/meta$', '\app\admin\controller\AiSeoController@optimizeMeta');
Route::post('ai_seo/readability$', '\app\admin\controller\AiSeoController@readabilityScore');

// V2.9.28 Sprint P: 插件市场在线安装路由
Route::get('plugin_store/index$', '\app\admin\controller\PluginStoreController@index');
Route::post('plugin_store/install$', '\app\admin\controller\PluginStoreController@install');
Route::post('plugin_store/update$', '\app\admin\controller\PluginStoreController@update');
Route::get('plugin_store/logs$', '\app\admin\controller\PluginStoreController@logs');
Route::post('plugin_store/securityScan$', '\app\admin\controller\PluginStoreController@securityScan');

// V2.9.28 Sprint H: Hook事件扩展路由
Route::get('hook_debug/performance$', '\app\admin\controller\HookDebugController@performance');
Route::get('hook_debug/listeners$', '\app\admin\controller\HookDebugController@listeners');
Route::get('hook_debug/executionChain$', '\app\admin\controller\HookDebugController@executionChain');
Route::get('hook_doc/index$', '\app\admin\controller\HookDocController@index');
Route::get('hook_doc/exportMarkdown$', '\app\admin\controller\HookDocController@exportMarkdown');
// V2.9.28 Sprint MO: PWA配置路由
Route::get('pwa_config/index$', '\app\admin\controller\PwaConfigController@index');
Route::post('pwa_config/save$', '\app\admin\controller\PwaConfigController@save');

// V2.9.25 L-2: 插件包管理后台路由
Route::get('plugin_store/index$', '\app\admin\controller\PluginStoreController@index');
Route::rule('plugin_store/add$', '\app\admin\controller\PluginStoreController@add', 'GET|POST');
Route::rule('plugin_store/edit/:id$', '\app\admin\controller\PluginStoreController@edit', 'GET|POST');
Route::post('plugin_store/delete$', '\app\admin\controller\PluginStoreController@delete');
Route::post('plugin_store/toggleStatus$', '\app\admin\controller\PluginStoreController@toggleStatus');
Route::post('plugin_store/setFeatured$', '\app\admin\controller\PluginStoreController@setFeatured');
Route::post('plugin_store/uploadPackage$', '\app\admin\controller\PluginStoreController@uploadPackage');
// 分类管理
Route::get('plugin_store/categories$', '\app\admin\controller\PluginStoreController@categories');
Route::rule('plugin_store/categoryEdit/:id$', '\app\admin\controller\PluginStoreController@categoryEdit', 'GET|POST');
Route::rule('plugin_store/categoryEdit$', '\app\admin\controller\PluginStoreController@categoryEdit', 'GET|POST');
Route::post('plugin_store/categoryDelete$', '\app\admin\controller\PluginStoreController@categoryDelete');
// 版本管理
Route::get('plugin_store/versions/:plugin_id$', '\app\admin\controller\PluginStoreController@versions');
Route::rule('plugin_store/versionAdd/:plugin_id$', '\app\admin\controller\PluginStoreController@versionAdd', 'GET|POST');
Route::post('plugin_store/versionDelete$', '\app\admin\controller\PluginStoreController@versionDelete');
// 依赖管理
Route::get('plugin_store/dependencies/:plugin_id$', '\app\admin\controller\PluginStoreController@dependencies');
Route::post('plugin_store/dependencyAdd$', '\app\admin\controller\PluginStoreController@dependencyAdd');
Route::post('plugin_store/dependencyDelete$', '\app\admin\controller\PluginStoreController@dependencyDelete');
// 安装日志
Route::get('plugin_store/logs$', '\app\admin\controller\PluginStoreController@logs');
// V2.9.30: 批量管理插件
Route::rule('plugin/batchIndex$', '\app\admin\controller\PluginController@batchIndex', 'GET|POST');

// ===== V2.9.30 Sprint Q: 功能看板 =====
Route::get('feature_registry/index$', '\app\admin\controller\FeatureRegistryController@index');
Route::get('feature_registry/health_check/:id$', '\app\admin\controller\FeatureRegistryController@healthCheck');
Route::post('feature_registry/full_check$', '\app\admin\controller\FeatureRegistryController@fullCheck');

// ===== V2.9.30 Sprint T2: 批量管理 =====
Route::get('template_batch/index$', '\app\admin\controller\TemplateBatchController@index');
Route::post('template_batch/preview$', '\app\admin\controller\TemplateBatchController@preview');
Route::post('template_batch/execute$', '\app\admin\controller\TemplateBatchController@execute');
Route::get('template_batch/log$', '\app\admin\controller\TemplateBatchController@log');

// ===== V2.9.30 Sprint T2: 标签管理 =====
Route::get('template_tag/index$', '\app\admin\controller\TemplateTagController@index');
Route::post('template_tag/create$', '\app\admin\controller\TemplateTagController@create');
Route::post('template_tag/edit/:id$', '\app\admin\controller\TemplateTagController@edit');
Route::post('template_tag/delete/:id$', '\app\admin\controller\TemplateTagController@delete');
Route::post('template_tag/attach$', '\app\admin\controller\TemplateTagController@attach');
Route::post('template_tag/batch_assign$', '\app\admin\controller\TemplateTagController@batchAssign');

// ===== V2.9.30 Sprint AI2: AI改写 =====
Route::post('content/ai_batch_rewrite$', '\app\admin\controller\AiRewriteController@batchRewrite');
Route::post('content/ai_rewrite/confirm/:id$', '\app\admin\controller\AiRewriteController@confirm');
Route::post('content/ai_rewrite/discard/:id$', '\app\admin\controller\AiRewriteController@discard');
Route::post('content/ai_rewrite/rollback/:contentId$', '\app\admin\controller\AiRewriteController@rollback');
Route::get('content/ai_rewrite/history/:contentId$', '\app\admin\controller\AiRewriteController@history');

// ===== V2.9.30 Sprint AI2: AI SEO =====
Route::post('content/ai_seo_optimize/:id$', '\app\admin\controller\ContentController@aiSeoOptimize');
Route::post('content/batch_ai_seo$', '\app\admin\controller\ContentController@batchAiSeo');

// ===== V2.9.30 Sprint AI2: AI配图 =====
Route::post('content/ai_generate_image/:id$', '\app\admin\controller\ContentController@aiGenerateImage');

// ===== V2.9.30 Sprint AI2: AI写作风格 =====
Route::get('content/ai_writing_styles$', '\app\admin\controller\ContentController@aiWritingStyles');

// ===== V2.9.31 Sprint AI3: AI SEO诊断 =====
Route::get('content/seo_diagnose/:id$', '\app\admin\controller\ContentController@seoDiagnose');
Route::post('content/batch_seo_diagnose$', '\app\admin\controller\ContentController@batchSeoDiagnose');
Route::get('content/seo_overview$', '\app\admin\controller\ContentController@seoOverview');

// ===== V2.9.31 Sprint AI3: AI Prompt模板 =====
Route::get('content/ai_prompt_templates$', '\app\admin\controller\ContentController@aiPromptTemplates');
Route::post('content/ai_prompt_template_save$', '\app\admin\controller\ContentController@aiPromptTemplateSave');

// ===== V2.9.30 Sprint UX: 全局搜索 =====
Route::post('search/global$', '\app\admin\controller\SearchController@global');

// V2.9.25 M-5: Hook 调试面板
Route::get('hook_debug/index$', '\app\admin\controller\HookDebugController@index');
Route::post('hook_debug/toggleDebug$', '\app\admin\controller\HookDebugController@toggleDebug');
Route::get('hook_debug/logs$', '\app\admin\controller\HookDebugController@logs');
Route::post('hook_debug/clearLogs$', '\app\admin\controller\HookDebugController@clearLogs');
Route::get('hook_debug/meta$', '\app\admin\controller\HookDebugController@meta');
Route::post('hook_debug/testFire$', '\app\admin\controller\HookDebugController@testFire');

// V2.9.25 N-1/N-2: 使用统计 + 安装趋势
Route::get('usage_stats/index$', '\app\admin\controller\UsageStatsController@index');
Route::get('usage_stats/installTrend$', '\app\admin\controller\UsageStatsController@installTrend');

// V2.9.25 N-3/N-4: 营收统计 + 结算 + 导出
Route::get('revenue/index$', '\app\admin\controller\RevenueController@index');
Route::get('revenue/settlements$', '\app\admin\controller\RevenueController@settlements');
Route::post('revenue/createSettlement$', '\app\admin\controller\RevenueController@createSettlement');
Route::post('revenue/auditSettlement$', '\app\admin\controller\RevenueController@auditSettlement');
Route::get('revenue/export$', '\app\admin\controller\RevenueController@export');
Route::get('revenue/doExport$', '\app\admin\controller\RevenueController@doExport');

// ========== V2.9.29 Sprint D: 开发者生态启动 ==========
// D-1: 开发者管理
Route::get('developer/index$', '\app\admin\controller\DeveloperController@index');
Route::get('developer/detail/:id$', '\app\admin\controller\DeveloperController@detail');
Route::post('developer/audit$', '\app\admin\controller\DeveloperController@audit');
Route::post('developer/disable$', '\app\admin\controller\DeveloperController@disable');
Route::post('developer/enable$', '\app\admin\controller\DeveloperController@enable');
// D-2: 模板打包导出
Route::rule('template_pack_export/export/:id$', '\app\admin\controller\TemplatePackExportController@export', 'GET|POST');
Route::rule('template_pack_export/import$', '\app\admin\controller\TemplatePackExportController@import', 'GET|POST');
// D-3: 插件开发工具
Route::get('plugin_dev/docs$', '\app\admin\controller\PluginDevController@docs');
Route::get('plugin_dev/examples$', '\app\admin\controller\PluginDevController@examples');
Route::get('plugin_dev/debug$', '\app\admin\controller\PluginDevController@debug');
Route::post('plugin_dev/scaffold$', '\app\admin\controller\PluginDevController@scaffold');
// D-4: Webhook管理
Route::get('webhook/index$', '\app\admin\controller\WebhookController@index');
Route::rule('webhook/edit/:id$', '\app\admin\controller\WebhookController@edit', 'GET|POST');
Route::post('webhook/delete/:id$', '\app\admin\controller\WebhookController@delete');
Route::post('webhook/toggle/:id$', '\app\admin\controller\WebhookController@toggle');
Route::get('webhook/logs/:id$', '\app\admin\controller\WebhookController@logs');
Route::get('webhook/logs$', '\app\admin\controller\WebhookController@logs');
// D-5: API密钥管理
Route::get('api_key/index$', '\app\admin\controller\ApiKeyController@index');
Route::get('api_key/add$', '\app\admin\controller\ApiKeyController@add');
Route::post('api_key/create$', '\app\admin\controller\ApiKeyController@create');
Route::post('api_key/revoke/:id$', '\app\admin\controller\ApiKeyController@revoke');
Route::get('api_key/logs$', '\app\admin\controller\ApiKeyController@logs');
Route::get('api_key/doc$', '\app\admin\controller\ApiKeyController@doc');

// ========== V2.9.29 Sprint T: 模板生态进阶 ==========
// T-4: 分类V2
Route::get('template_category_v2/index$', '\app\admin\controller\TemplateCategoryV2Controller@index');
Route::rule('template_category_v2/edit/:id$', '\app\admin\controller\TemplateCategoryV2Controller@edit', 'GET|POST');
Route::post('template_category_v2/delete/:id$', '\app\admin\controller\TemplateCategoryV2Controller@delete');
// T-5/T-6: 审核报告+统计详情
Route::get('template_stats_detail/detail/:id$', '\app\admin\controller\TemplateStatsDetailController@detail');
Route::get('template_stats_detail/compare$', '\app\admin\controller\TemplateStatsDetailController@compare');
Route::get('template_stats_detail/auditReport/:id$', '\app\admin\controller\TemplateStatsDetailController@auditReport');

// ========== V2.9.29 Sprint I: 内容智能增强 ==========
// I-1: 内容关联管理
Route::get('content_relation/index$', '\app\admin\controller\ContentRelationController@index');
Route::post('content_relation/add$', '\app\admin\controller\ContentRelationController@add');
Route::post('content_relation/delete/:id$', '\app\admin\controller\ContentRelationController@delete');
Route::get('content_relation/network/:id$', '\app\admin\controller\ContentRelationController@network');
// I-3: 行动计划
Route::get('content_action_plan/index$', '\app\admin\controller\ContentActionPlanController@index');
Route::post('content_action_plan/cancel/:id$', '\app\admin\controller\ContentActionPlanController@cancel');
// I-4: 质量诊断
Route::get('content_diagnosis/diagnose/:id$', '\app\admin\controller\ContentDiagnosisController@diagnose');
// I-5: 评论管理
Route::get('comment_admin/index$', '\app\admin\controller\CommentAdminController@index');
Route::post('comment_admin/audit/:id/:status$', '\app\admin\controller\CommentAdminController@audit');
Route::post('comment_admin/delete/:id$', '\app\admin\controller\CommentAdminController@delete');
Route::post('comment_admin/batchDelete$', '\app\admin\controller\CommentAdminController@batchDelete');
// I-6: 审计日志
Route::get('content_audit_log/index$', '\app\admin\controller\ContentAuditLogController@index');
Route::post('content_audit_log/rollback/:id$', '\app\admin\controller\ContentAuditLogController@rollback');

// ===== V2.9.35 Sprint SEC: 安全增强 =====
Route::get('security/index$', '\app\admin\controller\SecurityController@index');
Route::post('security/save$', '\app\admin\controller\SecurityController@save');
Route::get('security/log$', '\app\admin\controller\SecurityController@log');
Route::get('security/log/export$', '\app\admin\controller\SecurityController@exportLog');
Route::get('security/audit/report$', '\app\admin\controller\SecurityAuditController@report');
Route::get('data_security/index$', '\app\admin\controller\DataSecurityController@index');
Route::post('data_security/encrypt$', '\app\admin\controller\DataSecurityController@encrypt');
Route::post('data_security/rotate_key$', '\app\admin\controller\DataSecurityController@rotateKey');
Route::get('permission/index$', '\app\admin\controller\PermissionController@index');
Route::post('permission/save$', '\app\admin\controller\PermissionController@save');
Route::get('permission/audit$', '\app\admin\controller\PermissionController@audit');
Route::get('upload_security/index$', '\app\admin\controller\UploadSecurityController@index');
Route::get('security_log/index$', '\app\admin\controller\SecurityLogController@index');
Route::get('security_log/detail/:id$', '\app\admin\controller\SecurityLogController@detail');
Route::get('security_log/export$', '\app\admin\controller\SecurityLogController@export');
Route::get('security_log/stats$', '\app\admin\controller\SecurityLogController@stats');

// ===== V2.9.35 Sprint PERF: 性能优化 =====
Route::get('cache_manage/index$', '\app\admin\controller\CacheManageController@index');
Route::post('cache_manage/clear$', '\app\admin\controller\CacheManageController@clear');
Route::post('cache_manage/prewarm$', '\app\admin\controller\CacheManageController@prewarm');
Route::get('cache_manage/stats$', '\app\admin\controller\CacheManageController@stats');
Route::get('db_optimize/index$', '\app\admin\controller\DbOptimizeController@index');
Route::get('db_optimize/slow_queries$', '\app\admin\controller\DbOptimizeController@slowQueries');
Route::post('db_optimize/add_index$', '\app\admin\controller\DbOptimizeController@addIndex');
Route::get('cdn/index$', '\app\admin\controller\CdnController@index');
Route::post('cdn/save$', '\app\admin\controller\CdnController@save');
Route::post('cdn/purge$', '\app\admin\controller\CdnController@purge');
Route::get('render_optimize/index$', '\app\admin\controller\RenderOptimizeController@index');
Route::post('render_optimize/save$', '\app\admin\controller\RenderOptimizeController@save');
Route::post('render_optimize/generate_static$', '\app\admin\controller\RenderOptimizeController@generateStatic');
Route::get('performance_dashboard/index$', '\app\admin\controller\PerformanceDashboardController@index');
Route::get('performance_dashboard/slow_queries$', '\app\admin\controller\PerformanceDashboardController@slowQueries');
Route::get('performance_dashboard/report$', '\app\admin\controller\PerformanceDashboardController@report');

// ===== V2.9.35 Sprint PLUG: 插件市场框架 =====
Route::get('plugin_v2/index$', '\app\admin\controller\PluginManagerController@index');
Route::get('plugin_v2/detail$', '\app\admin\controller\PluginManagerController@detail');
Route::post('plugin_v2/install$', '\app\admin\controller\PluginManagerController@install');
Route::post('plugin_v2/uninstall$', '\app\admin\controller\PluginManagerController@uninstall');
Route::post('plugin_v2/enable$', '\app\admin\controller\PluginManagerController@enable');
Route::post('plugin_v2/disable$', '\app\admin\controller\PluginManagerController@disable');
Route::get('plugin_hook/index$', '\app\admin\controller\PluginHookManageController@index');
Route::get('plugin_hook/detail/:id$', '\app\admin\controller\PluginHookController@detail');
Route::post('plugin_hook/register$', '\app\admin\controller\PluginHookController@register');
Route::post('plugin_hook/unregister/:id$', '\app\admin\controller\PluginHookController@unregister');
Route::post('plugin_hook/priority$', '\app\admin\controller\PluginHookController@updatePriority');
Route::get('plugin_hook/performance$', '\app\admin\controller\PluginHookController@performance');
Route::get('plugin_hook/system$', '\app\admin\controller\PluginHookController@systemHooks');
Route::get('plugin_store_v2/index$', '\app\admin\controller\PluginStoreV2Controller@index');
Route::get('plugin_store_v2/detail$', '\app\admin\controller\PluginStoreV2Controller@detail');
Route::post('plugin_store_v2/install$', '\app\admin\controller\PluginStoreV2Controller@install');
Route::get('plugin_sandbox/index$', '\app\admin\controller\PluginSandboxController@status');
Route::post('plugin_sandbox/scan$', '\app\admin\controller\PluginSandboxController@scan');

// ===== V2.9.36 Sprint CM: 内容模型增强 =====
Route::get('content_model/index$', '\app\admin\controller\ContentModelController@index');
Route::get('content_model/create$', '\app\admin\controller\ContentModelController@create');
Route::post('content_model/save$', '\app\admin\controller\ContentModelController@save');
Route::get('content_model/edit/:id$', '\app\admin\controller\ContentModelController@edit');
Route::post('content_model/update/:id$', '\app\admin\controller\ContentModelController@update');
Route::post('content_model/delete/:id$', '\app\admin\controller\ContentModelController@delete');
Route::post('content_model/toggle/:id$', '\app\admin\controller\ContentModelController@toggle');
Route::get('content_model/fields/:id$', '\app\admin\controller\ContentFieldController@index');
Route::post('content_model/field/save$', '\app\admin\controller\ContentFieldController@save');
Route::post('content_model/field/update/:id$', '\app\admin\controller\ContentFieldController@update');
Route::post('content_model/field/delete/:id$', '\app\admin\controller\ContentFieldController@delete');
Route::post('content_model/field/sort$', '\app\admin\controller\ContentFieldController@sort');
Route::post('content_model/field/copy/:id$', '\app\admin\controller\ContentFieldController@copy');
Route::get('content_model/field/types$', '\app\admin\controller\ContentFieldController@types');
Route::get('content_model/field/detail/:id$', '\app\admin\controller\ContentFieldController@detail');
Route::get('content_model/relations/:id$', '\app\admin\controller\ContentRelationController@index');
Route::post('content_model/relation/save$', '\app\admin\controller\ContentRelationController@save');
Route::post('content_model/relation/delete/:id$', '\app\admin\controller\ContentRelationController@delete');
Route::post('content_model/relation/add$', '\app\admin\controller\ContentRelationController@addRelation');
Route::post('content_model/relation/remove$', '\app\admin\controller\ContentRelationController@removeRelation');
Route::post('content_model/relation/batch_add$', '\app\admin\controller\ContentRelationController@batchAdd');
Route::get('content_model/relation/search$', '\app\admin\controller\ContentRelationController@search');
Route::get('content_model/relation/related/:id$', '\app\admin\controller\ContentRelationController@related');
Route::get('content_model/designer/:id$', '\app\admin\controller\ContentModelController@designer');
Route::post('content_model/designer/save$', '\app\admin\controller\ContentModelController@saveDesign');
Route::get('content_model/import_export$', '\app\admin\controller\ContentModelController@importExport');
Route::post('content_model/import$', '\app\admin\controller\ContentModelController@import');
Route::post('content_model/export$', '\app\admin\controller\ContentModelController@export');
Route::get('content_model/download_template$', '\app\admin\controller\ContentModelController@downloadTemplate');

// ===== V2.9.36 Sprint TASK: 任务系统增强 =====
Route::get('task_enhance/assign$', '\app\admin\controller\TaskEnhanceController@assign');
Route::post('task_enhance/assign$', '\app\admin\controller\TaskEnhanceController@doAssign');
Route::post('task_enhance/reassign$', '\app\admin\controller\TaskEnhanceController@reassign');
Route::post('task_enhance/batch_assign$', '\app\admin\controller\TaskEnhanceController@batchAssign');
Route::post('task_enhance/auto_assign$', '\app\admin\controller\TaskEnhanceController@autoAssign');
Route::get('task_enhance/progress/:id$', '\app\admin\controller\TaskEnhanceController@progress');
Route::get('task_enhance/progress$', '\app\admin\controller\TaskEnhanceController@progress');
Route::post('task_enhance/update_progress/:id$', '\app\admin\controller\TaskEnhanceController@updateProgress');
Route::get('task_enhance/milestones/:id$', '\app\admin\controller\TaskEnhanceController@milestones');
Route::get('task_enhance/gantt$', '\app\admin\controller\TaskEnhanceController@gantt');
Route::get('task_enhance/notify$', '\app\admin\controller\TaskEnhanceController@notify');
Route::post('task_enhance/notify/save_template$', '\app\admin\controller\TaskEnhanceController@notify');
Route::post('task_enhance/check_notify$', '\app\admin\controller\TaskEnhanceController@checkNotify');
Route::get('task_enhance/stats$', '\app\admin\controller\TaskEnhanceController@stats');
Route::get('task_enhance/overview$', '\app\admin\controller\TaskEnhanceController@overview');
Route::get('task_enhance/efficiency$', '\app\admin\controller\TaskEnhanceController@efficiency');
Route::get('task_enhance/bottleneck$', '\app\admin\controller\TaskEnhanceController@bottleneck');
Route::get('task_enhance/trend$', '\app\admin\controller\TaskEnhanceController@trend');
Route::get('task_enhance/report$', '\app\admin\controller\TaskEnhanceController@report');
Route::get('task_enhance/templates$', '\app\admin\controller\TaskEnhanceController@templates');
Route::post('task_enhance/template/create$', '\app\admin\controller\TaskEnhanceController@templateCreate');
Route::post('task_enhance/template/update/:id$', '\app\admin\controller\TaskEnhanceController@templateUpdate');
Route::post('task_enhance/template/delete/:id$', '\app\admin\controller\TaskEnhanceController@templateDelete');
Route::post('task_enhance/create_from_template$', '\app\admin\controller\TaskEnhanceController@createFromTemplate');

// ===== V2.9.36 Sprint MINI: 小程序管理后台路由 =====
Route::get('mini_app/config$', '\app\admin\controller\MiniAppController@config');
Route::post('mini_app/save_config$', '\app\admin\controller\MiniAppController@saveConfig');
Route::get('mini_app/pages$', '\app\admin\controller\MiniAppController@pages');
Route::post('mini_app/page/save$', '\app\admin\controller\MiniAppController@savePage');
Route::get('mini_app/publish$', '\app\admin\controller\MiniAppController@publish');
Route::post('mini_app/upload_code$', '\app\admin\controller\MiniAppController@uploadCode');
Route::get('mini_app/stats$', '\app\admin\controller\MiniAppController@stats');

// ===== V2.9.36 Sprint TASK: 任务系统增强 =====
Route::get('task_enhance/index$', '\app\admin\controller\TaskEnhanceController@index');
Route::post('task_enhance/assign$', '\app\admin\controller\TaskEnhanceController@assign');
Route::post('task_enhance/reassign$', '\app\admin\controller\TaskEnhanceController@reassign');
Route::post('task_enhance/batch_assign$', '\app\admin\controller\TaskEnhanceController@batchAssign');
Route::post('task_enhance/auto_assign$', '\app\admin\controller\TaskEnhanceController@autoAssign');
Route::get('task_enhance/progress/:id$', '\app\admin\controller\TaskEnhanceController@progress');
Route::post('task_enhance/update_progress/:id$', '\app\admin\controller\TaskEnhanceController@updateProgress');
Route::get('task_enhance/milestones/:id$', '\app\admin\controller\TaskEnhanceController@milestones');
Route::get('task_enhance/gantt$', '\app\admin\controller\TaskEnhanceController@gantt');
Route::get('task_enhance/notify$', '\app\admin\controller\TaskEnhanceController@notify');
Route::post('task_enhance/check_notify$', '\app\admin\controller\TaskEnhanceController@checkNotify');
Route::get('task_enhance/stats$', '\app\admin\controller\TaskEnhanceController@stats');
Route::get('task_enhance/overview$', '\app\admin\controller\TaskEnhanceController@overview');
Route::get('task_enhance/efficiency$', '\app\admin\controller\TaskEnhanceController@efficiency');
Route::get('task_enhance/bottleneck$', '\app\admin\controller\TaskEnhanceController@bottleneck');
Route::get('task_enhance/trend$', '\app\admin\controller\TaskEnhanceController@trend');
Route::get('task_enhance/report$', '\app\admin\controller\TaskEnhanceController@report');
Route::get('task_enhance/templates$', '\app\admin\controller\TaskEnhanceController@templates');
Route::post('task_enhance/template/create$', '\app\admin\controller\TaskEnhanceController@templateCreate');
Route::post('task_enhance/template/update/:id$', '\app\admin\controller\TaskEnhanceController@templateUpdate');
Route::post('task_enhance/template/delete/:id$', '\app\admin\controller\TaskEnhanceController@templateDelete');
Route::post('task_enhance/create_from_template$', '\app\admin\controller\TaskEnhanceController@createFromTemplate');
Route::get('task_enhance/assign_page$', '\app\admin\controller\TaskEnhanceController@assignPage');
Route::get('task_enhance/notify_page$', '\app\admin\controller\TaskEnhanceController@notifyPage');
Route::get('task_enhance/template_page$', '\app\admin\controller\TaskEnhanceController@templatePage');

// ===== V2.9.36 Sprint PLUG-SHOP: 插件商店完善 =====
// PLUG-SHOP-1: 商店前端
Route::get('plugin_store_front/index$', '\app\admin\controller\PluginStoreFrontController@index');
Route::get('plugin_store_front/list$', '\app\admin\controller\PluginStoreFrontController@list');
Route::get('plugin_store_front/detail/:id$', '\app\admin\controller\PluginStoreFrontController@detail');
Route::get('plugin_store_front/search$', '\app\admin\controller\PluginStoreFrontController@search');
Route::get('plugin_store_front/category$', '\app\admin\controller\PluginStoreFrontController@category');
// PLUG-SHOP-2: 订单管理
Route::post('plugin_order/create$', '\app\admin\controller\PluginOrderController@create');
Route::get('plugin_order/list$', '\app\admin\controller\PluginOrderController@list');
Route::get('plugin_order/detail/:id$', '\app\admin\controller\PluginOrderController@detail');
Route::post('plugin_order/cancel/:id$', '\app\admin\controller\PluginOrderController@cancel');
Route::post('plugin_order/refund/:id$', '\app\admin\controller\PluginOrderController@refund');
// PLUG-SHOP-3: 支付
Route::post('plugin_payment/pay/:id$', '\app\admin\controller\PluginPaymentController@pay');
Route::post('plugin_payment/callback/alipay$', '\app\admin\controller\PluginPaymentController@alipayCallback');
Route::post('plugin_payment/callback/wechat$', '\app\admin\controller\PluginPaymentController@wechatCallback');
Route::get('plugin_payment/status/:id$', '\app\admin\controller\PluginPaymentController@status');
// PLUG-SHOP-4: 评价评分
Route::post('plugin_rating/submit$', '\app\admin\controller\PluginRatingController@submit');
Route::get('plugin_rating/list/:pluginId$', '\app\admin\controller\PluginRatingController@list');
Route::post('plugin_rating/reply/:id$', '\app\admin\controller\PluginRatingController@reply');
Route::post('plugin_rating/like/:id$', '\app\admin\controller\PluginRatingController@like');
// PLUG-SHOP-5: 商店后台
Route::get('plugin_store_admin/index$', '\app\admin\controller\PluginStoreAdminController@index');
Route::get('plugin_store_admin/stats$', '\app\admin\controller\PluginStoreAdminController@stats');
Route::post('plugin_store_admin/auditPlugin$', '\app\admin\controller\PluginStoreAdminController@auditPlugin');
Route::post('plugin_store_admin/setFeatured$', '\app\admin\controller\PluginStoreAdminController@setFeatured');

// ===== V2.9.37 Sprint MINI-FULL: 小程序/移动端管理 =====
Route::get('mini_manage/sdk$', '\app\admin\controller\MiniManageController@sdk');
Route::get('mini_manage/h5$', '\app\admin\controller\MiniManageController@h5');
Route::post('mini_manage/saveH5Config$', '\app\admin\controller\MiniManageController@saveH5Config');
Route::post('mini_manage/buildH5$', '\app\admin\controller\MiniManageController@buildH5');
Route::get('mini_manage/pageConfig$', '\app\admin\controller\MiniManageController@pageConfig');
Route::post('mini_manage/savePageConfig$', '\app\admin\controller\MiniManageController@savePageConfig');
Route::post('mini_manage/publishPageConfig$', '\app\admin\controller\MiniManageController@publishPageConfig');
Route::post('mini_manage/rollbackPageConfig$', '\app\admin\controller\MiniManageController@rollbackPageConfig');
Route::get('mini_manage/exportPageConfig$', '\app\admin\controller\MiniManageController@exportPageConfig');
Route::get('mini_manage/components$', '\app\admin\controller\MiniManageController@components');
Route::get('mini_manage/stats$', '\app\admin\controller\MiniManageController@stats');
Route::get('mini_manage/message$', '\app\admin\controller\MiniManageController@message');
Route::post('mini_manage/sendMessage$', '\app\admin\controller\MiniManageController@sendMessage');

// ===== V2.9.37 Sprint I18N: 多语言管理 =====
Route::get('lang_pack_manage/index$', '\app\admin\controller\LangPackManageController@index');
Route::get('lang_pack_manage/entries$', '\app\admin\controller\LangPackManageController@entries');
Route::post('lang_pack_manage/save$', '\app\admin\controller\LangPackManageController@save');
Route::post('lang_pack_manage/batchTranslate$', '\app\admin\controller\LangPackManageController@batchTranslate');
Route::get('lang_pack_manage/export$', '\app\admin\controller\LangPackManageController@export');
Route::get('lang_pack_manage/versions$', '\app\admin\controller\LangPackManageController@versions');
Route::get('lang_pack_manage/compareVersions$', '\app\admin\controller\LangPackManageController@compareVersions');
Route::post('lang_pack_manage/rollbackVersion$', '\app\admin\controller\LangPackManageController@rollbackVersion');
Route::post('lang_pack_manage/createSnapshot$', '\app\admin\controller\LangPackManageController@createSnapshot');
Route::get('lang_pack_manage/memory$', '\app\admin\controller\LangPackManageController@memory');
Route::post('lang_pack_manage/cleanupMemory$', '\app\admin\controller\LangPackManageController@cleanupMemory');

// ===== V2.9.37 Sprint AI-HELPER: AI助手管理 =====
Route::get('ai_helper_manage/recommend$', '\app\admin\controller\AiHelperManageController@recommend');
Route::get('ai_helper_manage/qa$', '\app\admin\controller\AiHelperManageController@qa');
Route::get('ai_helper_manage/report$', '\app\admin\controller\AiHelperManageController@report');
Route::post('ai_helper_manage/naturalQuery$', '\app\admin\controller\AiHelperManageController@naturalQuery');

// ===== V2.9.37 Sprint PLUG-ECO: 插件生态管理 =====
Route::get('plugin_eco/developer$', '\app\admin\controller\PluginEcoController@developer');
Route::get('plugin_eco/audit$', '\app\admin\controller\PluginEcoController@audit');
Route::post('plugin_eco/doAudit$', '\app\admin\controller\PluginEcoController@doAudit');
Route::post('plugin_eco/autoAudit$', '\app\admin\controller\PluginEcoController@autoAudit');
Route::post('plugin_eco/publishPlugin$', '\app\admin\controller\PluginEcoController@publishPlugin');
Route::post('plugin_eco/offlinePlugin$', '\app\admin\controller\PluginEcoController@offlinePlugin');
Route::get('plugin_eco/versions$', '\app\admin\controller\PluginEcoController@versions');
Route::post('plugin_eco/createVersion$', '\app\admin\controller\PluginEcoController@createVersion');
Route::post('plugin_eco/grayscalePublish$', '\app\admin\controller\PluginEcoController@grayscalePublish');
Route::post('plugin_eco/fullPublish$', '\app\admin\controller\PluginEcoController@fullPublish');
Route::post('plugin_eco/rollbackVersion$', '\app\admin\controller\PluginEcoController@rollbackVersion');
Route::get('plugin_eco/stats$', '\app\admin\controller\PluginEcoController@stats');
Route::get('plugin_eco/apiOpen$', '\app\admin\controller\PluginEcoController@apiOpen');
Route::post('plugin_eco/createApiKey$', '\app\admin\controller\PluginEcoController@createApiKey');

// ===== V2.9.37 Sprint SEO: SEO优化管理 =====
Route::get('seo_manage/schema$', '\app\admin\controller\SeoManageController@schema');
Route::get('seo_manage/schemaTest$', '\app\admin\controller\SeoManageController@schemaTest');
Route::get('seo_manage/sitemap$', '\app\admin\controller\SeoManageController@sitemap');
Route::post('seo_manage/generateSitemap$', '\app\admin\controller\SeoManageController@generateSitemap');
Route::post('seo_manage/submitSitemap$', '\app\admin\controller\SeoManageController@submitSitemap');
Route::get('seo_manage/performance$', '\app\admin\controller\SeoManageController@performance');
Route::post('seo_manage/autoOptimize$', '\app\admin\controller\SeoManageController@autoOptimize');
Route::post('seo_manage/restoreOptimize$', '\app\admin\controller\SeoManageController@restoreOptimize');
Route::get('seo_manage/geo$', '\app\admin\controller\SeoManageController@geo');
Route::get('seo_manage/report$', '\app\admin\controller\SeoManageController@report');
Route::get('seo_manage/exportReport$', '\app\admin\controller\SeoManageController@exportReport');

// ===== V2.9.38 Sprint AI-PLUS: AI能力深化 =====
Route::get('ai_workflow/index$', '\app\admin\controller\AiWorkflowController@index');
Route::rule('ai_workflow/create$', '\app\admin\controller\AiWorkflowController@create', 'GET|POST');
Route::rule('ai_workflow/edit/:id$', '\app\admin\controller\AiWorkflowController@edit', 'GET|POST');
Route::post('ai_workflow/delete$', '\app\admin\controller\AiWorkflowController@delete');
Route::get('ai_workflow/templates$', '\app\admin\controller\AiWorkflowController@templates');
Route::post('ai_workflow/useTemplate$', '\app\admin\controller\AiWorkflowController@useTemplate');
Route::post('ai_workflow/execute$', '\app\admin\controller\AiWorkflowController@execute');
Route::post('ai_workflow/cancelExec$', '\app\admin\controller\AiWorkflowController@cancelExec');
Route::post('ai_workflow/retryNode$', '\app\admin\controller\AiWorkflowController@retryNode');
Route::get('ai_workflow/logs$', '\app\admin\controller\AiWorkflowController@logs');
Route::get('ai_workflow/stats$', '\app\admin\controller\AiWorkflowController@stats');
Route::get('ai_workflow/export$', '\app\admin\controller\AiWorkflowController@export');
Route::post('ai_workflow/import$', '\app\admin\controller\AiWorkflowController@import');
// AI-PLUS-2: 批量生产
Route::get('ai_batch/index$', '\app\admin\controller\AiBatchController@index');
Route::get('ai_batch/create$', '\app\admin\controller\AiBatchController@create');
Route::post('ai_batch/create$', '\app\admin\controller\AiBatchController@create');
Route::get('ai_batch/detail/:id$', '\app\admin\controller\AiBatchController@detail');
Route::post('ai_batch/cancel$', '\app\admin\controller\AiBatchController@cancel');
Route::get('ai_batch/pipeline$', '\app\admin\controller\AiBatchPipelineController@index');
Route::post('ai_batch/start$', '\app\admin\controller\AiBatchPipelineController@start');
Route::post('ai_batch/pause$', '\app\admin\controller\AiBatchPipelineController@pause');
Route::post('ai_batch/resume$', '\app\admin\controller\AiBatchPipelineController@resume');
Route::get('ai_batch/progress$', '\app\admin\controller\AiBatchPipelineController@progress');
Route::get('ai_batch/results$', '\app\admin\controller\AiBatchPipelineController@results');
Route::post('ai_batch/importCsv$', '\app\admin\controller\AiBatchPipelineController@importCsv');
// AI-PLUS-3: 智能体
Route::get('ai_agent/index$', '\app\admin\controller\AiAgentController@index');
Route::post('ai_agent/create$', '\app\admin\controller\AiAgentController@create');
Route::rule('ai_agent/edit$', '\app\admin\controller\AiAgentController@edit', 'GET|POST');
Route::post('ai_agent/delete$', '\app\admin\controller\AiAgentController@delete');
Route::post('ai_agent/run$', '\app\admin\controller\AiAgentController@run');
Route::get('ai_agent/monitor$', '\app\admin\controller\AiAgentController@monitor');
// AI-PLUS-5: 智能体市场
Route::get('ai_agent/market$', '\app\admin\controller\AiAgentMarketController@market');
Route::get('ai_agent/market_detail/:id$', '\app\admin\controller\AiAgentMarketController@detail');
Route::post('ai_agent/install$', '\app\admin\controller\AiAgentMarketController@install');
Route::post('ai_agent/uninstall$', '\app\admin\controller\AiAgentMarketController@uninstall');
Route::post('ai_agent/submit$', '\app\admin\controller\AiAgentMarketController@submit');
Route::post('ai_agent/audit$', '\app\admin\controller\AiAgentMarketController@audit');
Route::post('ai_agent/rate$', '\app\admin\controller\AiAgentMarketController@rate');

// ===== V2.9.38 Sprint OPEN-PLAT: 开放平台增强 =====
Route::get('platform_developer/index$', '\app\admin\controller\PlatformDeveloperController@index');
Route::rule('platform_developer/audit$', '\app\admin\controller\PlatformDeveloperController@audit', 'GET|POST');
Route::get('platform_developer/sandbox$', '\app\admin\controller\PlatformDeveloperController@sandbox');
Route::get('platform_app/index$', '\app\admin\controller\PlatformAppController@index');
Route::get('platform_app/pending$', '\app\admin\controller\PlatformAppController@pending');
Route::post('platform_app/audit$', '\app\admin\controller\PlatformAppController@audit');
Route::post('platform_app/publish$', '\app\admin\controller\PlatformAppController@publish');
Route::post('platform_app/offline$', '\app\admin\controller\PlatformAppController@offline');
Route::get('api_doc/index$', '\app\admin\controller\ApiDocController@index');
Route::get('api_doc/swagger$', '\app\admin\controller\ApiDocController@swagger');
Route::rule('api_doc/test$', '\app\admin\controller\ApiDocController@test', 'GET|POST');
Route::get('api_doc/search$', '\app\admin\controller\ApiDocController@search');
Route::post('api_doc/addChangelog$', '\app\admin\controller\ApiDocController@addChangelog');

// ===== V2.9.38 Sprint SYS-INTEG: 系统集成 =====
Route::get('oauth_user/index$', '\app\admin\controller\OauthUserController@index');
Route::get('oauth_user/stats$', '\app\admin\controller\OauthUserController@stats');
Route::get('sms/index$', '\app\admin\controller\SmsController@index');
Route::get('sms/templates$', '\app\admin\controller\SmsController@templates');
Route::get('sms/logs$', '\app\admin\controller\SmsController@logs');
Route::post('sms/send$', '\app\admin\controller\SmsController@send');
Route::get('notify_center/index$', '\app\admin\controller\NotifyCenterController@index');
Route::get('notify_center/templates$', '\app\admin\controller\NotifyCenterController@templates');
Route::get('notify_center/channels$', '\app\admin\controller\NotifyCenterController@channels');
Route::post('notify_center/enableChannel$', '\app\admin\controller\NotifyCenterController@enableChannel');
Route::post('notify_center/disableChannel$', '\app\admin\controller\NotifyCenterController@disableChannel');
Route::post('notify_center/testChannel$', '\app\admin\controller\NotifyCenterController@testChannel');
Route::get('notify_center/subscriptions$', '\app\admin\controller\NotifyCenterController@subscriptions');
Route::post('notify_center/send$', '\app\admin\controller\NotifyCenterController@send');

// ===== V2.9.38 Sprint OPS-DEEP: 运营工具深化 =====
Route::get('ab_test/index$', '\app\admin\controller\AbTestController@index');
Route::post('ab_test/create$', '\app\admin\controller\AbTestController@create');
Route::post('ab_test/start$', '\app\admin\controller\AbTestController@start');
Route::post('ab_test/pause$', '\app\admin\controller\AbTestController@pause');
Route::post('ab_test/stop$', '\app\admin\controller\AbTestController@stop');
Route::get('ab_test/results$', '\app\admin\controller\AbTestController@results');
Route::post('ab_test/applyWinner$', '\app\admin\controller\AbTestController@applyWinner');
Route::get('user_segment/index$', '\app\admin\controller\UserSegmentController@index');
Route::post('user_segment/create$', '\app\admin\controller\UserSegmentController@create');
Route::post('user_segment/compute$', '\app\admin\controller\UserSegmentController@compute');
Route::get('user_segment/members$', '\app\admin\controller\UserSegmentController@members');
Route::get('user_segment/profile$', '\app\admin\controller\UserSegmentController@profile');
Route::get('ops_automation/index$', '\app\admin\controller\OpsAutomationController@index');
Route::post('ops_automation/create$', '\app\admin\controller\OpsAutomationController@create');
Route::post('ops_automation/enable$', '\app\admin\controller\OpsAutomationController@enable');
Route::post('ops_automation/disable$', '\app\admin\controller\OpsAutomationController@disable');
Route::post('ops_automation/test$', '\app\admin\controller\OpsAutomationController@test');
Route::get('quality_monitor/index$', '\app\admin\controller\QualityMonitorController@index');
Route::get('quality_monitor/trend$', '\app\admin\controller\QualityMonitorController@trend');
Route::get('quality_monitor/lowQuality$', '\app\admin\controller\QualityMonitorController@lowQuality');
Route::get('quality_monitor/report$', '\app\admin\controller\QualityMonitorController@report');
Route::rule('quality_monitor/alertConfig$', '\app\admin\controller\QualityMonitorController@alertConfig', 'GET|POST');

// ===== V2.9.38 Sprint PERF-II: 性能优化二期 =====
Route::get('db_rw/index$', '\app\admin\controller\DbRwController@index');
Route::post('db_rw/forceMaster$', '\app\admin\controller\DbRwController@forceMaster');
Route::post('db_rw/failover$', '\app\admin\controller\DbRwController@failover');
Route::get('queue/index$', '\app\admin\controller\QueueController@index');
Route::get('queue/failed$', '\app\admin\controller\QueueController@failed');
Route::post('queue/retry$', '\app\admin\controller\QueueController@retry');
Route::post('queue/cancel$', '\app\admin\controller\QueueController@cancel');
Route::post('queue/clear$', '\app\admin\controller\QueueController@clear');
Route::post('queue/startWorker$', '\app\admin\controller\QueueController@startWorker');
Route::post('queue/stopWorker$', '\app\admin\controller\QueueController@stopWorker');
Route::get('redis/index$', '\app\admin\controller\RedisController@index');
Route::post('redis/testLock$', '\app\admin\controller\RedisController@testLock');
Route::post('redis/testCounter$', '\app\admin\controller\RedisController@testCounter');
Route::post('redis/testRateLimit$', '\app\admin\controller\RedisController@testRateLimit');
Route::get('asset_optimize/index$', '\app\admin\controller\AssetOptimizeController@index');
Route::post('asset_optimize/compress$', '\app\admin\controller\AssetOptimizeController@compress');
Route::post('asset_optimize/merge$', '\app\admin\controller\AssetOptimizeController@merge');
Route::post('asset_optimize/cdnUpload$', '\app\admin\controller\AssetOptimizeController@cdnUpload');

// ===== V2.9.39 Sprint H5-FRONT: H5移动端前端构建 =====
Route::get('h5_config/index$', '\app\admin\controller\H5ConfigController@index');
Route::get('h5_config/edit$', '\app\admin\controller\H5ConfigController@edit');
Route::post('h5_config/save$', '\app\admin\controller\H5ConfigController@save');
Route::get('h5_config/theme$', '\app\admin\controller\H5ConfigController@theme');
Route::post('h5_config/theme$', '\app\admin\controller\H5ConfigController@theme');
Route::get('h5_config/pwa$', '\app\admin\controller\H5ConfigController@pwa');
Route::post('h5_config/pwa$', '\app\admin\controller\H5ConfigController@pwa');

// ===== V2.9.39 Sprint AI-DEEP: AI能力深化 =====
Route::get('ai_dialog/index$', '\app\admin\controller\AiDialogController@index');
Route::get('ai_dialog/history$', '\app\admin\controller\AiDialogController@history');
Route::get('ai_dialog/detail$', '\app\admin\controller\AiDialogController@detail');
Route::delete('ai_dialog/delete$', '\app\admin\controller\AiDialogController@delete');
Route::get('ai_model/index$', '\app\admin\controller\AiModelController@index');
Route::get('ai_model/add$', '\app\admin\controller\AiModelController@add');
Route::get('ai_model/edit$', '\app\admin\controller\AiModelController@edit');
Route::post('ai_model/save$', '\app\admin\controller\AiModelController@save');
Route::post('ai_model/toggleEnabled$', '\app\admin\controller\AiModelController@toggleEnabled');
Route::post('ai_model/setDefault$', '\app\admin\controller\AiModelController@setDefault');
Route::post('ai_model/testConnection$', '\app\admin\controller\AiModelController@testConnection');
Route::get('ai_model/test$', '\app\admin\controller\AiModelController@testConnection');
Route::post('ai_model/delete$', '\app\admin\controller\AiModelController@delete');
Route::get('ai_model/quota$', '\app\admin\controller\AiModelController@quota');

// ===== V2.9.39 Sprint DATA-DEEP: 数据分析深化 =====
Route::get('data_dashboard/index$', '\app\admin\controller\DataDashboardController@index');
Route::get('data_dashboard/edit$', '\app\admin\controller\DataDashboardController@edit');
Route::post('data_dashboard/save$', '\app\admin\controller\DataDashboardController@save');
Route::get('data_dashboard/share$', '\app\admin\controller\DataDashboardController@share');
Route::get('smart_report/index$', '\app\admin\controller\SmartReportController@index');
Route::get('smart_report/create$', '\app\admin\controller\SmartReportController@create');
Route::post('smart_report/save$', '\app\admin\controller\SmartReportController@save');
Route::get('smart_report/view$', '\app\admin\controller\SmartReportController@view');
Route::get('smart_report/templates$', '\app\admin\controller\SmartReportController@templates');
Route::get('data_drill/drill$', '\app\admin\controller\DataDrillController@drill');

// ===== V2.9.39 Sprint I18N-V2: 国际化增强 =====
Route::get('translation_task/index$', '\app\admin\controller\TranslationTaskController@index');
Route::get('translation_task/create$', '\app\admin\controller\TranslationTaskController@create');
Route::post('translation_task/save$', '\app\admin\controller\TranslationTaskController@save');
Route::get('translation_task/review$', '\app\admin\controller\TranslationTaskController@review');
Route::post('translation_task/approve$', '\app\admin\controller\TranslationTaskController@approve');
Route::get('translation_task/progress$', '\app\admin\controller\TranslationTaskController@progress');

// ===== V2.9.39 Sprint COMPLIANCE: 合规与安全 =====
Route::get('privacy/consent$', '\app\admin\controller\PrivacyController@consent');
Route::get('privacy/policy$', '\app\admin\controller\PrivacyController@policy');
Route::post('privacy/savePolicy$', '\app\admin\controller\PrivacyController@savePolicy');
Route::get('privacy/requests$', '\app\admin\controller\PrivacyController@requests');
Route::post('privacy/processRequest$', '\app\admin\controller\PrivacyController@processRequest');
Route::get('privacy/dpia$', '\app\admin\controller\PrivacyController@dpia');
Route::get('security_center/index$', '\app\admin\controller\SecurityCenterController@index');
Route::get('security_center/config$', '\app\admin\controller\SecurityCenterController@config');
Route::post('security_center/saveConfig$', '\app\admin\controller\SecurityCenterController@saveConfig');
Route::get('security_center/check$', '\app\admin\controller\SecurityCenterController@check');
Route::get('security_center/reports$', '\app\admin\controller\SecurityCenterController@reports');
Route::get('security_center/alerts$', '\app\admin\controller\SecurityCenterController@alerts');

// ===== V2.9.39 Sprint SYS-ROBUST: 系统健壮性 =====
Route::get('backup_restore/index$', '\app\admin\controller\BackupController@index');
Route::post('backup_restore/create$', '\app\admin\controller\BackupController@create');
Route::post('backup_restore/delete$', '\app\admin\controller\BackupController@delete');
Route::get('backup_restore/download$', '\app\admin\controller\BackupController@download');
Route::post('backup_restore/restore$', '\app\admin\controller\BackupController@restore');
Route::post('backup_restore/verify$', '\app\admin\controller\BackupController@verify');
Route::get('grayscale/index$', '\app\admin\controller\GrayscaleController@index');
Route::get('grayscale/create$', '\app\admin\controller\GrayscaleController@create');
Route::post('grayscale/save$', '\app\admin\controller\GrayscaleController@save');
Route::get('grayscale/detail$', '\app\admin\controller\GrayscaleController@detail');
Route::get('grayscale/history$', '\app\admin\controller\GrayscaleController@history');
Route::get('config_center/index$', '\app\admin\controller\ConfigCenterController@index');
Route::get('config_center/edit$', '\app\admin\controller\ConfigCenterController@edit');
Route::post('config_center/save$', '\app\admin\controller\ConfigCenterController@save');
Route::get('config_center/version$', '\app\admin\controller\ConfigCenterController@version');
Route::post('config_center/rollback$', '\app\admin\controller\ConfigCenterController@rollback');

// ===== V2.9.39 Sprint DEV-ECO: 开发者生态 =====
Route::get('developer_community/index$', '\app\admin\controller\DeveloperCommunityController@index');
Route::get('developer_community/qa$', '\app\admin\controller\DeveloperCommunityController@qa');
Route::get('developer_community/docs$', '\app\admin\controller\DeveloperCommunityController@docs');
Route::get('developer_community/examples$', '\app\admin\controller\DeveloperCommunityController@examples');
Route::get('cicd/index$', '\app\admin\controller\CicdController@index');
Route::post('cicd/save$', '\app\admin\controller\CicdController@save');
Route::get('cicd/webhooks$', '\app\admin\controller\CicdController@webhooks');

// ===== V2.9.40 Sprint AI-DEEP2: AI能力深化 =====
Route::get('ai_quality/index$', '\app\admin\controller\AiQualityController@index');
Route::post('ai_quality/check$', '\app\admin\controller\AiQualityController@check');
Route::post('ai_quality/batchCheck$', '\app\admin\controller\AiQualityController@batchCheck');
Route::get('ai_quality/detail/:id$', '\app\admin\controller\AiQualityController@detail');
Route::get('ai_quality/stats$', '\app\admin\controller\AiQualityController@stats');
Route::get('ai_recommend/index$', '\app\admin\controller\AiRecommendController@index');
Route::post('ai_recommend/updateConfig$', '\app\admin\controller\AiRecommendController@updateConfig');
Route::get('ai_recommend/preview$', '\app\admin\controller\AiRecommendController@preview');
Route::get('ai_recommend/stats$', '\app\admin\controller\AiRecommendController@stats');
Route::post('ai_recommend/clearCache$', '\app\admin\controller\AiRecommendController@clearCache');
Route::get('ai_knowledge/index$', '\app\admin\controller\AiKnowledgeController@index');
Route::get('ai_knowledge/create$', '\app\admin\controller\AiKnowledgeController@create');
Route::post('ai_knowledge/create$', '\app\admin\controller\AiKnowledgeController@create');
Route::get('ai_knowledge/detail/:id$', '\app\admin\controller\AiKnowledgeController@detail');
Route::post('ai_knowledge/importDoc$', '\app\admin\controller\AiKnowledgeController@importDoc');
Route::get('ai_knowledge/search$', '\app\admin\controller\AiKnowledgeController@search');
Route::post('ai_knowledge/delete$', '\app\admin\controller\AiKnowledgeController@delete');
Route::get('ai_knowledge/config$', '\app\admin\controller\AiKnowledgeController@config');
Route::post('ai_knowledge/config$', '\app\admin\controller\AiKnowledgeController@config');

// ===== V2.9.40 Sprint DATA-DEEP2: 数据服务深化 =====
Route::get('dashboard_interaction/layout/:id$', '\app\admin\controller\DataDashboardController@getLayout');
Route::post('dashboard_interaction/saveLayout$', '\app\admin\controller\DataDashboardController@saveLayout');
Route::post('dashboard_interaction/createShare$', '\app\admin\controller\DataDashboardController@createShare');
Route::get('dashboard_interaction/shareLinks/:id$', '\app\admin\controller\DataDashboardController@getShareLinks');
Route::post('dashboard_interaction/deleteShare$', '\app\admin\controller\DataDashboardController@deleteShare');
Route::get('dashboard_template/list$', '\app\admin\controller\DataDashboardController@templateList');
Route::post('dashboard_template/save$', '\app\admin\controller\DataDashboardController@templateSave');
Route::post('dashboard_template/createFrom$', '\app\admin\controller\DataDashboardController@createFromTemplate');
Route::post('report_subscription/create$', '\app\admin\controller\DataDashboardController@subscriptionCreate');
Route::get('report_subscription/list$', '\app\admin\controller\DataDashboardController@subscriptionList');
Route::post('report_subscription/delete$', '\app\admin\controller\DataDashboardController@subscriptionDelete');
Route::get('data_alert/index$', '\app\admin\controller\DataDashboardController@alertIndex');
Route::post('data_alert/createRule$', '\app\admin\controller\DataDashboardController@alertCreateRule');
Route::post('data_alert/updateRule$', '\app\admin\controller\DataDashboardController@alertUpdateRule');
Route::post('data_alert/deleteRule$', '\app\admin\controller\DataDashboardController@alertDeleteRule');
Route::get('data_alert/logs$', '\app\admin\controller\DataDashboardController@alertLogs');
Route::get('data_alert/stats$', '\app\admin\controller\DataDashboardController@alertStats');
Route::post('data_alert/toggle$', '\app\admin\controller\DataDashboardController@alertToggle');

// ===== V2.9.40 Sprint I18N-V3: 国际化深化 =====
Route::get('i18n_content/index$', '\app\admin\controller\I18nContentController@index');
Route::get('i18n_content/create$', '\app\admin\controller\I18nContentController@create');
Route::post('i18n_content/create$', '\app\admin\controller\I18nContentController@create');
Route::get('i18n_content/detail/:id$', '\app\admin\controller\I18nContentController@detail');
Route::post('i18n_content/linkContent$', '\app\admin\controller\I18nContentController@linkContent');
Route::post('i18n_content/sync$', '\app\admin\controller\I18nContentController@sync');
Route::get('i18n_content/routes$', '\app\admin\controller\I18nContentController@routes');
Route::post('i18n_content/updateRouteStrategy$', '\app\admin\controller\I18nContentController@updateRouteStrategy');

// ===== V2.9.40 Sprint COMPLIANCE2: 合规管理深化 =====
Route::get('compliance/index$', '\app\admin\controller\ComplianceController@index');
Route::get('compliance/auditLog$', '\app\admin\controller\ComplianceController@auditLog');
Route::post('compliance/auditLog$', '\app\admin\controller\ComplianceController@auditLog');
Route::get('compliance/auditDetail/:id$', '\app\admin\controller\ComplianceController@auditDetail');
Route::get('compliance/gdprReport$', '\app\admin\controller\ComplianceController@gdprReport');
Route::get('compliance/securityReport$', '\app\admin\controller\ComplianceController@securityReport');
Route::get('compliance/auditReport$', '\app\admin\controller\ComplianceController@auditReport');
Route::get('compliance/classification$', '\app\admin\controller\ComplianceController@classification');
Route::post('compliance/classification$', '\app\admin\controller\ComplianceController@classification');
Route::get('compliance/export$', '\app\admin\controller\ComplianceController@export');

// ===== V2.9.40 Sprint DEV-ECO2: 开发者生态深化 =====
Route::get('dev_doc/index$', '\app\admin\controller\DevDocController@index');
Route::post('dev_doc/generateTemplate$', '\app\admin\controller\DevDocController@generateTemplate');
Route::get('dev_doc/checklist$', '\app\admin\controller\DevDocController@checklist');
Route::post('dev_doc/createSandbox$', '\app\admin\controller\DevDocController@createSandbox');
Route::post('dev_doc/runTests$', '\app\admin\controller\DevDocController@runTests');
