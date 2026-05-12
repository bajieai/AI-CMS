<?php
// V2.9.5 文件上传安全配置

return [
    // 扩展名黑名单（全局生效，优先级最高）
    'blacklist_exts' => [
        'php', 'php3', 'php4', 'php5', 'phtml',
        'jsp', 'jspx',
        'asp', 'aspx', 'asa', 'cdx', 'cer',
        'sh', 'bash', 'zsh',
        'bat', 'cmd', 'com',
        'exe', 'dll', 'scr', 'msi',
        'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh',
        'htaccess', 'htpasswd',
    ],

    // 自定义上传类型（会合并到UploadSecurityService默认配置）
    'types' => [
        // 示例：添加音频类型
        // 'audio' => [
        //     'maxSize'   => 10 * 1024 * 1024,
        //     'mimes'     => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
        //     'exts'      => ['mp3', 'wav', 'ogg'],
        //     'mimeToExt' => [
        //         'audio/mpeg' => ['mp3'],
        //         'audio/wav'  => ['wav'],
        //         'audio/ogg'  => ['ogg'],
        //     ],
        //     'contentVerify' => false,
        // ],
    ],

    // 是否在上传失败时记录安全日志
    'log_failures' => true,
];
