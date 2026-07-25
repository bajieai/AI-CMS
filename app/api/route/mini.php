<?php
// AI-CMS V2.9.36 Sprint MINI — 小程序API路由
use think\facade\Route;

Route::group('api/mini/v1/content', function () {
    Route::get('list', '\app\api\controller\mini\ContentController@list');
    Route::get('detail', '\app\api\controller\mini\ContentController@detail');
    Route::get('search', '\app\api\controller\mini\ContentController@search');
    Route::get('category', '\app\api\controller\mini\ContentController@category');
    Route::get('tag', '\app\api\controller\mini\ContentController@tag');
    Route::get('recommend', '\app\api\controller\mini\ContentController@recommend');
    Route::get('hot', '\app\api\controller\mini\ContentController@hot');
    Route::get('related', '\app\api\controller\mini\ContentController@related');
    Route::get('fields', '\app\api\controller\mini\ContentController@fields');
    Route::get('relation', '\app\api\controller\mini\ContentController@relation');
})->middleware(\app\api\middleware\MiniAuthMiddleware::class);

Route::group('api/mini/v1/user', function () {
    Route::post('login', '\app\api\controller\mini\UserController@login');
    Route::get('info', '\app\api\controller\mini\UserController@info');
    Route::post('update', '\app\api\controller\mini\UserController@update');
    Route::get('favorite', '\app\api\controller\mini\UserController@favoriteList');
    Route::post('favorite/add', '\app\api\controller\mini\UserController@favoriteAdd');
    Route::post('favorite/remove', '\app\api\controller\mini\UserController@favoriteRemove');
    Route::post('like', '\app\api\controller\mini\UserController@like');
    Route::post('comment', '\app\api\controller\mini\UserController@comment');
    Route::get('comment/list', '\app\api\controller\mini\UserController@commentList');
    Route::post('message', '\app\api\controller\mini\UserController@message');
})->middleware(\app\api\middleware\MiniAuthMiddleware::class);

Route::group('api/mini/v1/system', function () {
    Route::get('config', '\app\api\controller\mini\SystemController@config');
    Route::get('menu', '\app\api\controller\mini\SystemController@menu');
    Route::get('ad', '\app\api\controller\mini\SystemController@ad');
    Route::get('site', '\app\api\controller\mini\SystemController@site');
    Route::get('update_time', '\app\api\controller\mini\SystemController@updateTime');
    Route::get('version', '\app\api\controller\mini\SystemController@version');
});

Route::get('api/mini/v1/index', '\app\api\controller\mini\MiniController@index');
Route::get('api/mini/v1/page/:name', '\app\api\controller\mini\MiniController@page');

// V2.9.37 MINI-FULL-5: 统计
Route::post('api/mini/v1/stats/track', '\app\api\controller\mini\StatsController@track');
Route::get('api/mini/v1/stats/realtime', '\app\api\controller\mini\StatsController@realtime');

// V2.9.37 MINI-FULL-6: 消息
Route::get('api/mini/v1/message/list', '\app\api\controller\mini\MessageController@list');
Route::post('api/mini/v1/message/read', '\app\api\controller\mini\MessageController@read');
Route::post('api/mini/v1/message/readAll', '\app\api\controller\mini\MessageController@readAll');
Route::get('api/mini/v1/message/unread', '\app\api\controller\mini\MessageController@unread');
Route::post('api/mini/v1/message/delete', '\app\api\controller\mini\MessageController@delete');
