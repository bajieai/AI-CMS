<?php
// AI-CMS V2.0 安装向导路由
// 完整命名空间类名需包含 Controller 后缀

use think\facade\Route;

Route::get('$', '\app\install\controller\IndexController@index');
Route::rule('index/step2$', '\app\install\controller\IndexController@step2', 'GET|POST');
Route::rule('index/step3$', '\app\install\controller\IndexController@step3', 'GET|POST');
Route::rule('index/step4$', '\app\install\controller\IndexController@step4', 'POST');
Route::rule('index/step5$', '\app\install\controller\IndexController@step5', 'GET');
