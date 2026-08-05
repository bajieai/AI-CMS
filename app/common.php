<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 全局辅助函数
// +----------------------------------------------------------------------

declare(strict_types=1);

// V2.9.42: 全局错误捕获（写入日志，便于排查500错误）
if (!defined('AI_CMS_ERROR_LOGGED')) {
    define('AI_CMS_ERROR_LOGGED', true);
    $aiCmsErrorLogFile = __DIR__ . '/../runtime/ai_cms_error.log';
    set_error_handler(function ($severity, $message, $file, $line) use ($aiCmsErrorLogFile) {
        $entry = date('Y-m-d H:i:s') . " [$severity] $message in $file:$line\n";
        @file_put_contents($aiCmsErrorLogFile, $entry, FILE_APPEND | LOCK_EX);
    });
    set_exception_handler(function ($e) use ($aiCmsErrorLogFile) {
        $entry = date('Y-m-d H:i:s') . ' [Exception] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
        @file_put_contents($aiCmsErrorLogFile, $entry, FILE_APPEND | LOCK_EX);
    });
    register_shutdown_function(function () use ($aiCmsErrorLogFile) {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            $entry = date('Y-m-d H:i:s') . ' [Fatal] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . "\n";
            @file_put_contents($aiCmsErrorLogFile, $entry, FILE_APPEND | LOCK_EX);
        }
    });
}

/**
 * msubstr 字符串截取函数（ThinkPHP 5.x/6.x 兼容函数）
 * ThinkPHP 8.1 不再内置此函数，模板中大量使用了 {$var|msubstr=0,100} 语法
 *
 * @param string $str 要截取的字符串
 * @param int $start 开始位置
 * @param int $length 截取长度
 * @param string $charset 字符编码
 * @param bool $suffix 是否加省略号
 * @return string
 */
if (!function_exists('msubstr')) {
    function msubstr(string $str, int $start = 0, int $length = 100, string $charset = 'utf-8', bool $suffix = true): string
    {
        $str = (string) $str;
        if (function_exists('mb_substr')) {
            $slice = mb_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset] ?? $re['utf-8'], $str, $match);
            $slice = implode('', array_slice($match[0], $start, $length));
        }

        $strLen = function_exists('mb_strlen') ? mb_strlen($str, $charset) : strlen($str);
        if ($suffix && $strLen > $length) {
            return $slice . '...';
        }
        return $slice;
    }
}
