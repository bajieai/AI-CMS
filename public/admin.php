<?php
namespace think;

if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
if (function_exists('mb_http_output')) mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_regex_encoding')) mb_regex_encoding('UTF-8');

$uploadTmpDir = __DIR__ . '/../runtime/upload_tmp';
if (!is_dir($uploadTmpDir)) @mkdir($uploadTmpDir, 0777, true);
ini_set('upload_tmp_dir', $uploadTmpDir);

if (!file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /install.php');
    exit;
}

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Location: /install.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

$scriptName = $_SERVER['SCRIPT_NAME'];
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

if (strpos($uriPath, $scriptName) === 0 && strlen($uriPath) > strlen($scriptName)) {
    $_SERVER['PATH_INFO'] = substr($uriPath, strlen($scriptName));
} elseif (!isset($_GET['s']) && !isset($_SERVER['PATH_INFO']) && strpos($uriPath, '/admin/') === 0) {
    $pathInfo = substr($uriPath, strlen('/admin'));
    $_SERVER['PATH_INFO'] = $pathInfo;
    $_GET['s'] = $pathInfo;
}

$app = new App();
$http = $app->http;
error_reporting(E_ALL & ~E_DEPRECATED);
$response = $http->name('admin')->run();
$response->send();
$http->end($response);
