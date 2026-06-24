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
use think\facade\Route;

// 首页
Route::get('/', '\app\home\controller\IndexController@index');

// 分类列表页 /product /case /news /download /job /page
Route::get(':type', '\app\home\controller\CateController@listing')
    ->pattern(['type' => 'product|case|news|download|job|page']);

// 内容详情页 /product/123 /news/123 等
Route::get(':type/:id', '\app\home\controller\ContentController@detail')
    ->pattern(['type' => 'product|case|news|download|job|page', 'id' => '\d+']);

// 搜索页
Route::get('search', '\app\home\controller\SearchController@index');

// 用户中心
Route::get('user', '\app\home\controller\UserController@index');

// V2.3 会员系统
Route::get('member/index$', '\app\home\controller\MemberController@index');
Route::rule('member/register$', '\app\home\controller\MemberController@register', 'GET|POST');
Route::rule('member/login$', '\app\home\controller\MemberController@login', 'GET|POST');
Route::get('member/logout$', '\app\home\controller\MemberController@logout');
Route::rule('member/profile$', '\app\home\controller\MemberController@profile', 'GET|POST');
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
