<?php
// home应用视图配置

return [
    // 模板路径映射到template/pc目录
    // 注意：使用相对路径，避免在配置加载阶段调用 root_path()
    'view_path' => dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR,
];
