<?php
// [ACT] AI-CMS V2.0 主入口（前台home应用）
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 检查是否已安装
if (!file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /install.php');
    exit;
}

$http = (new App())->http;

// PHP 8.4 兼容：抑制 ThinkPHP 8.1 的隐式可空类型弃用警告
error_reporting(E_ALL & ~E_DEPRECATED);

// 根据请求前缀自动分发应用：/api/* → api 应用，其余保持 home 应用
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '/api/') === 0) {
    $response = $http->name('api')->run();
} else {
    $response = $http->name('home')->run();
}

$response->send();

$http->end($response);
