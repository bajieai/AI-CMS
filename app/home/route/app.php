<?php
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
