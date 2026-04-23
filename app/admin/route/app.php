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

// 系统设置
Route::rule('system/config$', '\app\admin\controller\SystemController@config', 'GET|POST');
Route::rule('log/index$', '\app\admin\controller\LogController@index', 'GET');
