<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
// AI-CMS V2.0 安装向导路由
// 完整命名空间类名需包含 Controller 后缀

use think\facade\Route;

Route::get('$', '\app\install\controller\IndexController@index');
Route::rule('index/step2$', '\app\install\controller\IndexController@step2', 'GET|POST');
Route::rule('index/step3$', '\app\install\controller\IndexController@step3', 'GET|POST');
Route::rule('index/step4$', '\app\install\controller\IndexController@step4', 'POST');
Route::rule('index/step5$', '\app\install\controller\IndexController@step5', 'GET');
