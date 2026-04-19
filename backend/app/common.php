<?php
// 应用公共函数文件

use app\exception\BusinessException;

/**
 * 打印调试信息（使用 ThinkPHP/Symfony 内置 dd）
 */
if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        exit(1);
    }
}

/**
 * 简化Json响应
 */
function json_result($data = null, $message = 'success', $code = 200): \think\response\Json
{
    return json([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ]);
}

/**
 * 获取当前用户ID
 */
function get_current_user_id(): ?int
{
    return request()->user_id ?? null;
}

/**
 * 简化业务异常抛出
 */
function throw_business_exception(string $message = '操作失败', int $code = 400, array $errors = [], int $httpCode = 400): void
{
    throw new BusinessException($message, $code, $errors, $httpCode);
}

/**
 * 获取配置值
 */
function config_get(string $key, $default = null)
{
    return config($key, $default);
}

/**
 * 获取环境变量
 */
function env_get(string $key, $default = null)
{
    return env($key, $default);
}

/**
 * 安全过滤HTML
 */
function escape_html(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * 生成随机字符串
 */
function generate_random_string(int $length = 16): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 格式化文件大小
 */
function format_file_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 格式化时间差
 */
function format_time_diff(int $timestamp): string
{
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return $diff . '秒前';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . '个月前';
    } else {
        return floor($diff / 31536000) . '年前';
    }
}
