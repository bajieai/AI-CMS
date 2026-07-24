<?php
// [ACT] AI-CMS V2.9.41 后台入口（admin应用）
namespace think;

// ===== 编码根治：全局强制UTF-8 =====
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
mb_regex_encoding('UTF-8');

// 检查是否已安装
if (!file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /install.php');
    exit;
}

// 检查vendor目录是否存在
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Location: /install.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

$app = new App();
$http = $app->http;

// PHP 8.4 兼容：抑制 ThinkPHP 8.1 的隐式可空类型弃用警告
error_reporting(E_ALL & ~E_DEPRECATED);

$response = $http->name('admin')->run();

$response->send();

$http->end($response);
