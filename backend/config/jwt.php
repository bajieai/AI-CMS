<?php

return [
    // JWT密钥
    'secret' => env('JWT_SECRET', 'your-jwt-secret-key-change-in-production'),
    
    // Access Token过期时间(秒)
    'access_expire' => env('JWT_EXPIRE', 7200),
    
    // Refresh Token过期时间(秒)
    'refresh_expire' => env('JWT_REFRESH_EXPIRE', 604800),
    
    // JWT算法
    'algorithm' => 'HS256',
    
    // Token前缀
    'token_prefix' => 'Bearer ',
    
    // 黑名单前缀
    'blacklist_prefix' => 'jwt:blacklist:',
    
    // 黑名单缓存时间(秒)
    'blacklist_grace_period' => 0,
];
