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
Route::get('content/versions/:id$', '\app\admin\controller\ContentController@versions');
Route::post('content/rollback/:versionId$', '\app\admin\controller\ContentController@rollback');

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
Route::rule('log/index$', '\app\admin\controller\LogController@index', 'GET');

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
