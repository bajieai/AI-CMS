<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 在线升级配置 - V2.9.44
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

return [
    // Gitee 仓库信息
    'gitee_owner' => 'bajieai',
    'gitee_repo'  => 'ai-cms',
    // Gitee 私人令牌（可选，用于提高 API 频率限制）
    'gitee_token' => env('GITEE_TOKEN', ''),

    // HTTP 请求超时（秒）
    'timeout' => 120,
    // 是否验证 SSL 证书
    'verify_ssl' => true,

    // 升级包文件名匹配前缀（用于在 Release assets 中定位升级包）
    'upgrade_package_prefix' => 'ai-cms-upgrade-',

    // 文件保护清单：以下文件/目录永远不会被升级包覆盖或删除
    // 支持精确路径、目录前缀（以 / 结尾）、fnmatch 通配符
    'protected_paths' => [
        '.env',
        'config/database.php',
        'config/oauth.php',
        'config/mail.php',
        'config/payment.php',
        'config/sms.php',
        'config/upgrade.php',
        'uploads/',
        'public/uploads/',
        'public/storage/',
        'runtime/',
        'plugin/',
        '.gitignore',
        '.git/',
        '.user.ini',
    ],
];
