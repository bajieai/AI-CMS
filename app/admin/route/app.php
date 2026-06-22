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
Route::get('dashboard/getSourceAnalysis$', '\app\admin\controller\DashboardController@getSourceAnalysis');

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
