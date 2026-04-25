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
