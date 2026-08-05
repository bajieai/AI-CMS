<?php
namespace think;

if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
if (function_exists('mb_http_output')) mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_regex_encoding')) mb_regex_encoding('UTF-8');

// V2.9.42: 全局错误日志（排查500错误）
$aiCmsLogFile = __DIR__ . '/../runtime/ai_cms_error.log';
set_exception_handler(function ($e) use ($aiCmsLogFile) {
    $entry = date('Y-m-d H:i:s') . ' [Exception] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n---\n";
    @file_put_contents($aiCmsLogFile, $entry, FILE_APPEND | LOCK_EX);
    throw $e; // 重新抛出，保持 ThinkPHP 默认行为
});

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
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($uri, '/api/') === 0) {
        $response = $http->name('api')->run();
    } else {
        $response = $http->name('home')->run();
    }

    $response->send();
    $http->end($response);
} catch (\Throwable $e) {
    $entry = date('Y-m-d H:i:s') . ' [FATAL] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n---\n";
    @file_put_contents(__DIR__ . '/../runtime/ai_cms_error.log', $entry, FILE_APPEND | LOCK_EX);
    // 直接输出错误
    http_response_code(500);
    echo '<pre style=\"background:#fff;color:red;padding:20px;font-size:14px\">';
    echo '<h2>' . get_class($e) . '</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<p>' . $e->getFile() . ':' . $e->getLine() . '</p>';
    echo '</pre>';
}
