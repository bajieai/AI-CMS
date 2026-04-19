<?php
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 注册自定义Request类，修复 UrlHandler trait 的 $this->config() 缺失问题
$app = new App();
$app->bind('request', \app\Request::class);

$http = $app->http;
$response = $http->run();
$response->send();
$http->end($response);
