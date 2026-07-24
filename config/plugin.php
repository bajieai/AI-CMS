<?php
// +----------------------------------------------------------------------
// | 八界AI-CMS 插件配置
// +----------------------------------------------------------------------

return [
    // 插件市场API地址
    'market_url' => env('PLUGIN_MARKET_URL', 'https://market.aicms.io/api'),

    // 自动更新检查
    'auto_update_check' => env('PLUGIN_AUTO_UPDATE_CHECK', true),

    // 安全扫描
    'security_scan' => env('PLUGIN_SECURITY_SCAN', true),

    // 最大文件大小(字节，默认50MB)
    'max_filesize' => env('PLUGIN_MAX_FILESIZE', 52428800),

    // 依赖检查
    'dependency_check' => true,

    // 安装后默认状态(0=禁用需手动启用, 1=自动启用)
    'auto_enable' => false,
];
