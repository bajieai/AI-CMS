<?php
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

// 系统设置
Route::rule('system/config$', '\app\admin\controller\SystemController@config', 'GET|POST');
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
