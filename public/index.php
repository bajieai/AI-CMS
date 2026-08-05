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
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($uri, '/api/') === 0) {
        $response = $http->name('api')->run();
    } else {
        $response = $http->name('home')->run();
    }

    // V2.9.42: 检查响应状态码，500时输出响应内容用于调试
    $code = $response->getCode();
    if ($code >= 500) {
        $content = $response->getContent();
        // 写入日志
        $entry = date('Y-m-d H:i:s') . " [500 Response] $code\n" . substr($content, 0, 5000) . "\n---\n";
        @file_put_contents(__DIR__ . '/../runtime/ai_cms_error.log', $entry, FILE_APPEND | LOCK_EX);
        // 输出到页面
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
        exit;
    }

    $response->send();
    $http->end($response);
} catch (\Throwable $e) {
    $entry = date('Y-m-d H:i:s') . ' [FATAL] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n---\n";
    @file_put_contents(__DIR__ . '/../runtime/ai_cms_error.log', $entry, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>500 Error</title></head><body>';
    echo '<div style="background:#fff;color:red;padding:20px;font-size:14px;font-family:monospace">';
    echo '<h2>' . htmlspecialchars(get_class($e)) . '</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
    echo '<pre style="white-space:pre-wrap;word-break:break-all">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div></body></html>';
    exit;
}
