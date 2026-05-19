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
// admin应用中间件配置（app级别，由 MultiApp::loadApp 加载）
// 在 MultiApp 解析应用后执行，此时 $request->controller() 等方法可用
return [
    \app\common\middleware\AdminAuth::class,
    \app\common\middleware\AdminPermission::class,
    \app\common\middleware\AdminCsrf::class,
    \app\admin\middleware\PjaxMiddleware::class,
];
