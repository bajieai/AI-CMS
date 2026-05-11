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
Route::post('content/autoSave/:id$', '\app\admin\controller\ContentController@autoSave');
Route::get('content/versions/:id$', '\app\admin\controller\ContentController@versions');
Route::post('content/rollback/:versionId$', '\app\admin\controller\ContentController@rollback');

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
