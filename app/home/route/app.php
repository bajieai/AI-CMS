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
// AI-CMS V2.0 前台路由
//
// URL 体系（参考 eyoucms/织梦模式）：
//   列表页：/{seo_url}       如 /news, /products, /about
//   列表页：/{type_slug}     如 /info, /product （无 seo_url 时回退）
//   详情页：/{seo_url}/{id}  如 /news/1, /products/3
//   详情页：/{type_slug}/{id} 如 /info/1 （无 seo_url 时回退）
//   单页面：/{seo_url}       如 /about, /contact
//   单页面：/page/{id}       如 /page/6 （无 seo_url 时回退）
//
// 旧URL通过控制器内301重定向到规范URL
use think\facade\Route;

// 首页
Route::get('/', '\app\home\controller\IndexController@index');

// ========== 固定路由（系统保留路径，必须在通配路由之前注册） ==========

// 分类列表页（无 seo_url 时回退入口）
// completeMatch 防止 /info/1 被匹配到这里
Route::get('product', '\app\home\controller\CateController@listing')->append(['type' => 'product'])->completeMatch(true);
Route::get('case', '\app\home\controller\CateController@listing')->append(['type' => 'case'])->completeMatch(true);
Route::get('info', '\app\home\controller\CateController@listing')->append(['type' => 'info'])->completeMatch(true);
Route::get('download', '\app\home\controller\CateController@listing')->append(['type' => 'download'])->completeMatch(true);
Route::get('job', '\app\home\controller\CateController@listing')->append(['type' => 'job'])->completeMatch(true);

// 内容详情页（无 seo_url 时回退入口）
// /product/123 /info/123 等（纯数字ID，completeMatch 不需要因为 pattern 已限制）
Route::get('product/:id', '\app\home\controller\ContentController@detail')
    ->append(['type' => 'product'])->pattern(['id' => '\d+']);
Route::get('case/:id', '\app\home\controller\ContentController@detail')
    ->append(['type' => 'case'])->pattern(['id' => '\d+']);
Route::get('info/:id', '\app\home\controller\ContentController@detail')
    ->append(['type' => 'info'])->pattern(['id' => '\d+']);
Route::get('download/:id', '\app\home\controller\ContentController@detail')
    ->append(['type' => 'download'])->pattern(['id' => '\d+']);
Route::get('job/:id', '\app\home\controller\ContentController@detail')
    ->append(['type' => 'job'])->pattern(['id' => '\d+']);

// 单页列表页 /page（展示所有单页分类卡片）
Route::get('page', '\app\home\controller\CateController@listing')->append(['type' => 'page'])->completeMatch(true);

// 单页面直达路由 /page/6 /page/7 等（按分类ID）
Route::get('page/:cateId', '\app\home\controller\CateController@singlePage')
    ->pattern(['cateId' => '\d+']);

// 旧版两段URL 301重定向 /product/{seoUrl} → /{seoUrl}
// 保留一段时间用于SEO过渡，之后可移除
Route::get('product/:seoUrl', '\app\home\controller\CateController@listingBySlug')->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);
Route::get('case/:seoUrl', '\app\home\controller\CateController@listingBySlug')->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);
Route::get('info/:seoUrl', '\app\home\controller\CateController@listingBySlug')->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);
Route::get('download/:seoUrl', '\app\home\controller\CateController@listingBySlug')->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);
Route::get('job/:seoUrl', '\app\home\controller\CateController@listingBySlug')->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);
Route::get('page/:seoUrl', '\app\home\controller\CateController@singlePageBySlug')
    ->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*']);

// 搜索页
Route::get('search', '\app\home\controller\SearchController@index');

// 用户中心
Route::get('user', '\app\home\controller\UserController@index');

// V2.3 会员系统
Route::get('member/index$', '\app\home\controller\MemberController@index');
Route::rule('member/register$', '\app\home\controller\MemberController@register', 'GET|POST');
Route::rule('member/login$', '\app\home\controller\MemberController@login', 'GET|POST');
Route::get('member/logout$', '\app\home\controller\MemberController@logout');
Route::rule('member/password/forgot$', '\app\home\controller\MemberController@forgotPassword', 'GET|POST');
Route::rule('member/password/reset$', '\app\home\controller\MemberController@passwordReset', 'GET|POST');
Route::rule('member/profile$', '\app\home\controller\MemberController@profile', 'GET|POST');
Route::post('member/changePassword$', '\app\home\controller\MemberController@changePassword');
Route::get('member/points$', '\app\home\controller\MemberController@points');
Route::get('member/exchange$', '\app\home\controller\MemberController@exchangeLog');

// V2.3 OAuth回调（放在home应用，避开api全局AdminAuth中间件）
Route::get('oauth/gitee_callback$', '\app\home\controller\OauthController@giteeCallback');

// V2.7 头条号OAuth回调
Route::get('oauth/toutiao/callback$', '\app\home\controller\ToutiaoOAuthController@callback');

// V2.9.27 V-3: RSS Feed
Route::get('rss/:type$', '\app\home\controller\RssController@feed')->pattern(['type' => '[a-z]+']);
Route::get('rss$', '\app\home\controller\RssController@feed');

// V2.9.27 U-7: 已购模板
Route::get('my_templates$', '\app\home\controller\MyTemplateController@index');

// V2.9.27 U-4: 模板预览增强
Route::get('template/preview/:id$', '\app\home\controller\TemplatePreviewController@preview')->pattern(['id' => '\d+']);

// V2.3 前台评论AJAX
Route::post('comment/submit$', '\app\home\controller\CommentController@submit');
Route::get('comment/list$', '\app\home\controller\CommentController@list');

// V2.7 表单展示与提交
Route::get('form/:code', '\app\home\controller\FormController@show')
    ->pattern(['code' => '[a-zA-Z0-9_]+']);
Route::post('form/submit', '\app\home\controller\FormController@submit');

// V2.7 章节阅读与购买
Route::get('chapter/read/:parent_id/:chapter_id', '\app\home\controller\ChapterController@read')
    ->pattern(['parent_id' => '\d+', 'chapter_id' => '\d+']);
Route::post('chapter/buy', '\app\home\controller\ChapterController@buyChapter');
Route::post('chapter/buy-book', '\app\home\controller\ChapterController@buyBook');

// V2.7 积分商城
Route::get('points$', '\app\home\controller\PointsProductController@index');
Route::post('points/exchange$', '\app\home\controller\PointsProductController@exchange');

// V2.7 签到
Route::get('signin$', '\app\home\controller\SigninController@index');
Route::post('signin/do$', '\app\home\controller\SigninController@doSignin');
Route::get('signin/log$', '\app\home\controller\SigninController@pointsLog');

// V2.3 会员收藏与通知
Route::get('member/favorite$', '\app\home\controller\MemberController@favorite');
Route::post('member/favoriteRemove$', '\app\home\controller\MemberController@favoriteRemove');
Route::get('member/notification$', '\app\home\controller\MemberController@notification');
Route::post('member/notificationRead$', '\app\home\controller\MemberController@notificationRead');
Route::post('member/notificationReadAll$', '\app\home\controller\MemberController@notificationReadAll');

// V2.9.3 M20: 会员等级进度页
Route::get('member/level$', '\app\home\controller\MemberController@level');

// V2.9.3: 会员头像上传
Route::post('member/uploadAvatar$', '\app\home\controller\MemberController@uploadAvatar');

// V2.9.9: 注册验证码
Route::get('member/captcha$', '\app\home\controller\MemberController@captcha');

// V3.1: 社交分享统计
Route::post('content/share$', '\app\home\controller\ContentController@share');

// V2.9.8 C-1: 自定义404页面
Route::get('404.html$', '\app\home\controller\IndexController@error404');

// V2.9.12: 模板前台预览
Route::get('template/preview/:slug$', '\app\home\controller\TemplatePreviewController@preview');

// ========== V2.9.18 U-1: 个人中心扩展 ==========
Route::get('member/publish$', '\app\home\controller\MemberController@publish');
Route::rule('member/preferences$', '\app\home\controller\MemberController@preferences', 'GET|POST');
// V2.9.19 U-1: 内容统计面板
Route::get('member/stats$', '\app\home\controller\MemberController@stats');

// V2.9.25 L-3: 插件市场前台浏览
Route::get('plugin_store/index$', '\app\home\controller\PluginStoreController@index');
Route::get('plugin_store/detail/:code$', '\app\home\controller\PluginStoreController@detail');

// ===== V2.9.32 Sprint FIX-4: AI配图/摘要/标签独立Service =====
Route::get('ai/image/index$', '\app\home\controller\AiImageController@index');
Route::post('ai/image/generate$', '\app\home\controller\AiImageController@generate');
Route::post('ai/image/batch_generate$', '\app\home\controller\AiImageController@batchGenerate');
Route::post('ai/image/rate$', '\app\home\controller\AiImageController@rateImage');
Route::post('ai/summary/generate$', '\app\home\controller\AiSummaryController@generate');
Route::post('ai/summary/batch_generate$', '\app\home\controller\AiSummaryController@batchGenerate');
Route::post('ai/summary/preview$', '\app\home\controller\AiSummaryController@preview');
Route::post('ai/tag/recommend$', '\app\home\controller\AiTagController@recommend');
Route::post('ai/tag/batch_recommend$', '\app\home\controller\AiTagController@batchRecommend');
Route::get('ai/tag/hotness$', '\app\home\controller\AiTagController@tagHotness');

// ===== V2.9.31 Sprint T3: 前台模板商店 =====
Route::get('template_store$', '\app\home\controller\TemplateStoreController@index');
Route::get('template_store/detail/:slug$', '\app\home\controller\TemplateStoreController@detail');
Route::get('template_store/search$', '\app\home\controller\TemplateStoreController@search');
Route::get('template_store/ajax_list$', '\app\home\controller\TemplateStoreController@ajaxList');

// ========== V2.9.29 Sprint D: 开发者前台 ==========
Route::rule('developer/apply$', '\app\home\controller\DeveloperApplyController@apply', 'GET|POST');
Route::get('developer/panel$', '\app\home\controller\DeveloperApplyController@panel');


// ========== V2.9.29 Sprint T: 模板购买前台 ==========
Route::rule('template_store/buy/:id$', '\app\home\controller\TemplateStoreBuyController@buy', 'GET|POST');
Route::get('template_store/cart$', '\app\home\controller\TemplateStoreBuyController@cart');
Route::post('template_store/addToCart$', '\app\home\controller\TemplateStoreBuyController@addToCart');
Route::post('template_store/checkout$', '\app\home\controller\TemplateStoreBuyController@checkout');

// ========== V2.9.29 Sprint I: 内容互动前台 ==========
// I-5: 收藏
Route::post('favorite/toggle$', '\app\home\controller\FavoriteController@toggle');
Route::get('member/favorites$', '\app\home\controller\FavoriteController@myFavorites');
// I-5: 点赞
Route::post('like/toggle$', '\app\home\controller\LikeController@toggle');
// I-7: 订阅
Route::post('subscribe/toggle$', '\app\home\controller\ContentSubscribeController@toggle');
Route::get('member/subscriptions$', '\app\home\controller\ContentSubscribeController@mySubscriptions');

// ===== V2.9.30 Sprint T2: 我的模板 =====
Route::get('member/my_templates$', '\app\home\controller\MyTemplateController@index');
Route::post('member/template/install/:storeId$', '\app\home\controller\MyTemplateController@install');

// ===== V2.9.30 Sprint T2: 收藏夹管理 =====
Route::get('member/favorite_folders$', '\app\home\controller\FavoriteFolderController@index');
Route::post('member/favorite_folder/create$', '\app\home\controller\FavoriteFolderController@create');
Route::post('member/favorite_folder/edit/:id$', '\app\home\controller\FavoriteFolderController@edit');
Route::post('member/favorite_folder/delete/:id$', '\app\home\controller\FavoriteFolderController@delete');

// ===== V2.9.34 Sprint MEM: 支付回调 =====
Route::post('payment/wechat_notify$', '\app\home\controller\PaymentController@wechatNotify');
Route::post('payment/alipay_notify$', '\app\home\controller\PaymentController@alipayNotify');
Route::get('payment/alipay_return$', '\app\home\controller\PaymentController@alipayReturn');

// ========== 通配路由（必须放在所有固定路由之后） ==========
//
// 系统保留词（被这些词精确匹配的URL不会进入通配路由）：
//   固定路径：product, case, info, download, job, page, search, member, user,
//            rss, form, chapter, points, signin, comment, oauth, ai, template,
//            template_store, plugin_store, developer, payment, my_templates, 404
//
// 通配路由1：/{seoUrl}/{id} → 内容详情页
//   匹配 /news/1, /products/3 等（第二段为纯数字）
//   先查 seo_url 对应的分类，找到则展示详情；找不到则 404
//
// 通配路由2：/{seoUrl} → 分类列表页或单页面
//   匹配 /news, /products, /about 等
//   先查 seo_url 对应的分类，列表类型展示列表，单页类型展示单页

// 通配路由1：内容详情页 /{seoUrl}/{id}
Route::get(':seoUrl/:id', '\app\home\controller\ContentController@detailBySlug')
    ->pattern(['seoUrl' => '[a-zA-Z][a-zA-Z0-9\-]*', 'id' => '\d+']);

// 通配路由2：分类列表页/单页面 /{seoUrl}
Route::get(':seoUrl', '\app\home\controller\CateController@dispatch')
    ->pattern(['seoUrl' => '(?!product$|case$|info$|download$|job$|page$|search$|member$|user$|rss$|form$|chapter$|points$|signin$|comment$|oauth$|ai$|template$|template_store$|plugin_store$|developer$|payment$|my_templates$|404$)[a-zA-Z][a-zA-Z0-9\-]*']);
