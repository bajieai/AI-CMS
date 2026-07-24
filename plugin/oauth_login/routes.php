<?php

/**
 * 第三方登录插件路由
 * 插件路由文件由PluginManagerService在安装时注册
 */

use think\facade\Route;

// GitHub OAuth回调
Route::get('oauth/github/callback', function() {
    $plugin = new OauthLoginPlugin();
    return $plugin->handleGithubCallback();
});

// 微信OAuth回调
Route::get('oauth/wechat/callback', function() {
    $plugin = new OauthLoginPlugin();
    return $plugin->handleWechatCallback();
});
