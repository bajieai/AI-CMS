<?php
namespace think;

if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
if (function_exists('mb_http_output')) mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_regex_encoding')) mb_regex_encoding('UTF-8');

if (!file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /install.php');
    exit;
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Location: /install.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

if (!isset($_GET['s']) && !isset($_SERVER['PATH_INFO']) && $uriPath !== '/' && $uriPath !== '/index.php') {
    $_SERVER['PATH_INFO'] = $uriPath;
    $_GET['s'] = $uriPath;
}

$http = (new App())->http;
error_reporting(E_ALL & ~E_DEPRECATED);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '/api/') === 0) {
    $response = $http->name('api')->run();
} else {
    $response = $http->name('home')->run();
}

$response->send();
$http->end($response);
