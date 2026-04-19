<?php

return [
    // 允许的来源
    'allow_origin' => explode(',', env('CORS_ALLOW_ORIGIN', '*')),
    
    // 允许的请求方法
    'allow_methods' => explode(',', env('CORS_ALLOW_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
    
    // 允许的请求头
    'allow_headers' => explode(',', env('CORS_ALLOW_HEADERS', 'Content-Type,Authorization,X-Requested-With,Accept,Origin')),
    
    // 暴露的头部
    'expose_headers' => explode(',', env('CORS_EXPOSE_HEADERS', 'Content-Length,Content-Type,Access-Control-Allow-Origin')),
    
    // 预检请求缓存时间
    'max_age' => env('CORS_MAX_AGE', 86400),
    
    // 是否允许携带凭证
    'allow_credentials' => true,
];
