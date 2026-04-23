<?php
// AI-CMS V2.0 API路由
use think\facade\Route;

// AI生成
Route::post('ai/generate', '\app\api\controller\AiController@generate');

// 图片上传
Route::post('upload/image', '\app\api\controller\UploadController@image');

// 缓存清理（限管理员）
Route::post('cache/clear', '\app\api\controller\CacheController@clear');
