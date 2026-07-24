<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
// admin应用视图配置

return [
    // 模板路径映射到template/admin目录
    // 注意：使用相对路径，避免在配置加载阶段调用 root_path()
    'view_path' => dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR,
];
