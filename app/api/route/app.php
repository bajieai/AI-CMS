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
// AI-CMS V2.7 API路由
use think\facade\Route;

// AI生成
Route::post('ai/generate', '\app\api\controller\AiController@generate');

// V3.1: AI批量配图+SEO评分+写作风格+社交分享
Route::post('ai/batch_image', '\app\api\controller\AiController@batchImage');
Route::post('ai/seo_score', '\app\api\controller\AiController@seoScore');
Route::get('ai/styles', '\app\api\controller\AiController@styles');
Route::post('ai/share', '\app\api\controller\AiController@share');

// 图片上传
Route::post('upload/image', '\app\api\controller\UploadController@image');

// V2.9.1 M14a: 配图任务状态查询（公开API，无需认证）
Route::get('image/status', '\app\api\controller\ImageController@status');
Route::post('image/batch_status', '\app\api\controller\ImageController@batchStatus');

// CSRF Token刷新（GET请求无需CSRF验证，用于AJAX自动恢复）
Route::get('csrf/token', '\app\api\controller\CsrfController@token');

// 缓存清理（限管理员）
Route::post('cache/clear', '\app\api\controller\CacheController@clear');
Route::post('cache/clearByType', '\app\api\controller\CacheController@clearByType');

// V2.7 API v1 路由组（Token认证 + 会员身份解析 + 付费内容防护）
Route::group('v1', function () {
    // 内容API
    Route::get('content$', '\app\api\controller\v1\Content@index');
    Route::get('content/:id$', '\app\api\controller\v1\Content@read');

    // 分类API
    Route::get('cate$', '\app\api\controller\v1\Cate@index');
    Route::get('cate/tree$', '\app\api\controller\v1\Cate@tree');

    // 评论API
    Route::get('comment$', '\app\api\controller\v1\Comment@index');
    Route::post('comment$', '\app\api\controller\v1\Comment@save');

    // 媒体API
    Route::get('media$', '\app\api\controller\v1\Media@index');

    // API文档
    Route::get('doc$', '\app\api\controller\v1\Doc@index');

    // V2.7 搜索增强
    Route::get('search$', '\app\api\controller\v1\Search@index');
    Route::get('search/suggest$', '\app\api\controller\v1\Search@suggest');
    Route::get('search/hot$', '\app\api\controller\v1\Search@hot');
})->middleware([
    \app\api\middleware\ApiAuth::class,
    \app\api\middleware\ApiMemberAuth::class,
    \app\common\middleware\PaidContentGuard::class,
]);

// V2.7 公开API（无需认证）
Route::group('v1', function () {
    // PV统计（游客也可上报）
    Route::post('visit/pv$', '\app\api\controller\v1\Visit@pv');
    Route::get('visit/hot$', '\app\api\controller\v1\Visit@hot');
});

// V2.9.9: 分享追踪（公开API，无需认证）
Route::post('share/track$', '\app\api\controller\ShareController@track');

// ========== V2.9.18: 邮件订阅 + 通知 API ==========
// D-3: 邮件订阅（公开）
Route::post('subscribe/submit$', '\app\api\controller\v1\SubscribeController@submit');
Route::get('subscribe/confirm$', '\app\api\controller\v1\SubscribeController@confirm');
Route::get('subscribe/unsubscribe$', '\app\api\controller\v1\SubscribeController@unsubscribe');

// U-3: 通知 API（需要登录）
Route::get('v1/notify/list$', '\app\api\controller\v1\NotifyController@list');
Route::get('v1/notify/unread_count$', '\app\api\controller\v1\NotifyController@unreadCount');
Route::post('v1/notify/read$', '\app\api\controller\v1\NotifyController@read');
Route::post('v1/notify/read_all$', '\app\api\controller\v1\NotifyController@readAll');

// ========== V2.9.18 U-2: 注册登录 API ==========
Route::post('auth/register$', '\app\api\controller\v1\AuthController@register');
Route::get('auth/register/captcha$', '\app\api\controller\v1\AuthController@getCaptcha');
Route::post('auth/register/send_code$', '\app\api\controller\v1\AuthController@sendVerifyCode');
Route::post('auth/login$', '\app\api\controller\v1\AuthController@login');
Route::post('auth/password/forgot$', '\app\api\controller\v1\AuthController@forgotPassword');
Route::post('auth/password/reset$', '\app\api\controller\v1\AuthController@resetPassword');
