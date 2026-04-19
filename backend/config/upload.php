<?php

return [
    // 上传根目录
    'root' => env('UPLOAD_PATH', './uploads'),
    
    // 最大上传大小(字节)
    'max_size' => env('UPLOAD_MAX_SIZE', 10485760),  // 10MB
    
    // 允许的文件类型
    'allowed_types' => explode(',', env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png,gif,webp,mp4,mp3,zip,pdf,doc,docx,xls,xlsx')),
    
    // 图片配置
    'image' => [
        // 缩略图配置
        'thumb' => [
            'width' => env('IMAGE_THUMB_WIDTH', 300),
            'height' => env('IMAGE_THUMB_HEIGHT', 300),
            'quality' => env('IMAGE_QUALITY', 85),
        ],
        
        // 水印配置
        'water' => [
            'enabled' => false,
            'type' => 1,  // 1: 文字水印 2: 图片水印
            'image' => '',
            'text' => '',
            'font_size' => 14,
            'color' => '#ffffff',
            'position' => 9,  // 9右下角
            'opacity' => 50,
        ],
    ],
    
    // 媒体配置
    'media' => [
        // 视频缩略图
        'video_thumb' => true,
        
        // 允许的视频格式
        'video_types' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        
        // 允许的音频格式
        'audio_types' => ['mp3', 'wav', 'ogg', 'aac', 'm4a'],
    ],
    
    // 文件保存策略
    'save_rule' => 'date',  // date: 按日期目录 md5: MD5命名 unique: 唯一命名
    
    // 是否替换同名文件
    'replace_same' => false,
    
    // 路径规则
    'path_format' => [
        'image' => 'images/{year}/{month}/{day}',
        'video' => 'videos/{year}/{month}/{day}',
        'audio' => 'audios/{year}/{month}/{day}',
        'file' => 'files/{year}/{month}/{day}',
    ],
];
