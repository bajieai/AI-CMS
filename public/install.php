<?php
// [ACT] AI-CMS V2.0 安装向导入口（install应用，独立于认证体系）
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// ===== 编码根治：全局强制UTF-8 =====
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
mb_regex_encoding('UTF-8');

// 如果已安装，直接跳转到后台
if (file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /admin.php');
    exit;
}

$http = (new App())->http;

// PHP 8.4 兼容：抑制 ThinkPHP 8.1 的隐式可空类型弃用警告
error_reporting(E_ALL & ~E_DEPRECATED);

$response = $http->name('install')->run();

$response->send();

$http->end($response);
