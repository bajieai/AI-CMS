<?php
// AI-CMS V2.3 API路由
use think\facade\Route;

// AI生成
Route::post('ai/generate', '\app\api\controller\AiController@generate');

// 图片上传
Route::post('upload/image', '\app\api\controller\UploadController@image');

// CSRF Token刷新（GET请求无需CSRF验证，用于AJAX自动恢复）
Route::get('csrf/token', '\app\api\controller\CsrfController@token');

// 缓存清理（限管理员）
Route::post('cache/clear', '\app\api\controller\CacheController@clear');

// V2.3 API v1 路由组（Token认证）
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
})->middleware(\app\api\middleware\ApiAuth::class);
