<?php
use think\facade\Route;

// API路由组
Route::group('api', function () {

    // ==================== 认证接口（无需登录） ====================
    Route::group('auth', function () {
        Route::post('login', 'api.AuthController/login');          // 登录 - 无需认证
        Route::post('refresh', 'api.AuthController/refresh');      // 刷新Token - 无需认证

        // 以下接口需要登录
        Route::get('me', 'api.AuthController/me')->middleware(\app\middleware\AuthMiddleware::class);
        Route::post('logout', 'api.AuthController/logout')->middleware(\app\middleware\AuthMiddleware::class);
        Route::post('password', 'api.AuthController/changePassword')->middleware(\app\middleware\AuthMiddleware::class);
    });

    // ==================== 文章接口 ====================
    Route::group('articles', function () {
        Route::get('/', 'api.ArticleController/index');
        Route::post('/', 'api.ArticleController/save');
        Route::put('/:id', 'api.ArticleController/update');
        Route::delete('/:id', 'api.ArticleController/delete');
        Route::delete('/', 'api.ArticleController/batchDelete');
        Route::post('batch', 'api.ArticleController/batch');
        Route::post('/:id/publish', 'api.ArticleController/publish');
        Route::post('/:id/archive', 'api.ArticleController/archive');
        Route::post('/:id/submit', 'api.ArticleController/submit');
        Route::post('/:id/reject', 'api.ArticleController/reject');
        Route::get(':id', 'api.ArticleController/read');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 分类接口 ====================
    Route::group('categories', function () {
        Route::get('/', 'api.CategoryController/index');
        Route::get('tree', 'api.CategoryController/tree');
        Route::post('/', 'api.CategoryController/save');
        Route::put('/:id', 'api.CategoryController/update');
        Route::delete('/:id', 'api.CategoryController/delete');
        Route::get(':id', 'api.CategoryController/read');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 标签接口 ====================
    Route::group('tags', function () {
        Route::get('/', 'api.TagController/index');
        Route::get('search', 'api.TagController/search');
        Route::post('/', 'api.TagController/save');
        Route::put('/:id', 'api.TagController/update');
        Route::delete('/:id', 'api.TagController/delete');
        Route::get(':id', 'api.TagController/read');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 媒体接口 ====================
    Route::group('media', function () {
        Route::post('upload', 'api.MediaController/upload');
        Route::get('/', 'api.MediaController/index');
        Route::delete('/:id', 'api.MediaController/delete');
        Route::get(':id', 'api.MediaController/read');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== AI接口 ====================
    Route::group('ai', function () {
        Route::post('generate', 'api.AiController/generate');
        Route::post('generate-stream', 'api.AiController/generateStream');
        Route::post('optimize', 'api.AiController/optimize');
        Route::post('geo-check', 'api.AiController/geoCheck');
        Route::get('tasks', 'api.AiController/tasks');
        Route::get('prompts', 'api.AiController/prompts');
        Route::get('models', 'api.AiController/models');
        Route::get('stats', 'api.AiController/stats');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 设置接口 ====================
    Route::group('settings', function () {
        Route::get('/', 'api.SettingsController/index');
        Route::put('/', 'api.SettingsController/update');
        Route::get(':group', 'api.SettingsController/getByGroup');
        Route::put(':group', 'api.SettingsController/updateByGroup');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 仪表盘接口 ====================
    Route::group('dashboard', function () {
        Route::get('stats', 'api.DashboardController/stats');
        Route::get('recent-activities', 'api.DashboardController/recentActivities');
    })->middleware(\app\middleware\AuthMiddleware::class);

    // ==================== 缓存管理接口 ====================
    Route::group('cache', function () {
        Route::post('clear', 'api.CacheController/clear');
        Route::get('status', 'api.CacheController/status');
    })->middleware(\app\middleware\AuthMiddleware::class);

})->middleware(\app\middleware\CorsMiddleware::class);

// 状态码说明路由
Route::get('status-codes', function () {
    return json([
        'code' => 200,
        'message' => 'success',
        'data' => [
            '200' => '成功',
            '400' => '请求参数错误',
            '401' => '未授权，请登录',
            '403' => '无权限访问',
            '404' => '资源不存在',
            '422' => '数据验证失败',
            '429' => '请求过于频繁',
            '500' => '服务器内部错误',
        ]
    ]);
});
