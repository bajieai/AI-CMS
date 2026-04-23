<?php
// admin应用中间件配置（app级别，由 MultiApp::loadApp 加载）
// 在 MultiApp 解析应用后执行，此时 $request->controller() 等方法可用
return [
    \app\common\middleware\AdminAuth::class,
    \app\common\middleware\AdminPermission::class,
];
