<?php
// 应用助手函数

/**
 * 获取语言
 */
function lang(string $name = '', array $vars = [], string $lang = ''): string
{
    return \think\facade\Lang::get($name, $vars, $lang);
}

/**
 * 获取客户端IP地址
 */
function get_client_ip(int $type = 0, bool $adv = true): string
{
    return \think\facade\Request::ip($type, $adv);
}

/**
 * 生成完整URL
 */
function full_url(string $path = '', array $params = []): string
{
    $url = \think\facade\Request::domain() . '/' . ltrim($path, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * 生成带域名的资源URL
 */
function asset_url(string $path): string
{
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    return \think\facade\Request::domain() . '/' . ltrim($path, '/');
}

/**
 * 加密ID
 * 使用 AES-256-CBC 加密，兼容 URL 安全传输
 */
function encode_id(int $id): string
{
    $key = hash('sha256', env('JWT_SECRET', 'aicms-default-secret'), true);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt((string) $id, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_url_encode($iv . $encrypted);
}

/**
 * 解密ID
 */
function decode_id(string $hash): ?int
{
    try {
        $key = hash('sha256', env('JWT_SECRET', 'aicms-default-secret'), true);
        $data = base64_url_decode($hash);
        if ($data === false || strlen($data) < 16) {
            return null;
        }
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $result = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($result === false) {
            return null;
        }
        return is_numeric($result) ? (int) $result : null;
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * URL安全的 Base64 编码
 */
function base64_url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * URL安全的 Base64 解码
 */
function base64_url_decode(string $data): string|false
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * 获取当前登录用户
 */
function current_user(): ?\app\model\User
{
    static $user = null;
    if ($user === null) {
        $userId = \think\facade\Request::instance()->user_id ?? null;
        if ($userId) {
            $user = \app\model\User::find($userId);
        }
    }
    return $user;
}

/**
 * 验证权限
 */
function has_permission(string $permission): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $rbac = new \app\service\RbacService();
    return $rbac->hasPermission($user->id, $permission);
}

/**
 * 是否超级管理员
 */
function is_super_admin(): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $rbac = new \app\service\RbacService();
    return $rbac->isSuperAdmin($user->id);
}

/**
 * 记录操作日志
 */
function log_operation(string $action, string $content = '', array $data = []): void
{
    if (env('LOG_OPERATION_ENABLED', true)) {
        $excludePaths = explode(',', env('LOG_OPERATION_EXCLUDE', '/api/auth/login'));
        $currentPath = \think\facade\Request::path();
        foreach ($excludePaths as $path) {
            if (stripos($currentPath, trim($path)) !== false) {
                return;
            }
        }
        
        \app\model\OperationLog::create([
            'user_id' => \think\facade\Request::instance()->user_id ?? 0,
            'username' => current_user()->username ?? 'system',
            'action' => $action,
            'content' => $content,
            'ip' => get_client_ip(),
            'user_agent' => \think\facade\Request::header('user-agent', ''),
            'params' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time' => date('Y-m-d H:i:s'),
        ]);
    }
}

/**
 * 雪花ID生成
 */
function snowflake_id(): string
{
    $workerId = env('SNOWFLAKE_WORKER_ID', 1);
    $sequence = 0;
    $lastTime = 0;
    $time = (int) (microtime(true) * 1000);
    
    if ($time === $lastTime) {
        $sequence = ($sequence + 1) & 4095;
        if ($sequence === 0) {
            while ($time <= $lastTime) {
                $time = (int) (microtime(true) * 1000);
            }
        }
    } else {
        $sequence = 0;
    }
    
    $lastTime = $time;
    
    return sprintf(
        '%04x%010x%04x',
        ($workerId & 0x3FF),
        $time,
        ($sequence & 0xFFF)
    );
}

/**
 * 验证手机号格式
 */
function is_valid_phone(string $phone): bool
{
    return preg_match('/^1[3-9]\d{9}$/', $phone) === 1;
}

/**
 * 验证邮箱格式
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证URL格式
 */
function is_valid_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * 清理HTML标签
 */
function strip_html_tags(string $html, array $allowable_tags = []): string
{
    $html = strip_tags($html, '<' . implode('><', $allowable_tags) . '>');
    return trim($html);
}

/**
 * 截取UTF8字符串 (使用PHP内置mb_substr，已原生支持UTF-8)
 */

/**
 * 生成文章摘要
 */
function make_summary(string $content, int $length = 200): string
{
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (mb_strlen($content) <= $length) {
        return $content;
    }
    
    return mb_substr($content, 0, $length) . '...';
}

/**
 * 过滤XSS
 * 使用内置HTML Purifier实现，无需外部依赖
 */
function filter_xss(string $string): string
{
    // 移除脚本标签及其内容
    $string = preg_replace('#<script[^>]*>.*?</script>#is', '', $string);
    
    // 移除危险事件属性
    $string = preg_replace('/\s*on(?:load|click|mouse|error|abort|keyup|keydown|keypress|focus|blur|change|submit|reset|select|unload)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $string);
    
    // 移除 javascript: 协议
    $string = preg_replace('/(javascript\s*:)/i', '', $string);
    
    // 移除 vbscript: 协议
    $string = preg_replace('/(vbscript\s*:)/i', '', $string);
    
    // 移除 data: 协议（可能包含脚本）
    $string = preg_replace('/(data\s*:\s*text\/html)/i', '', $string);
    
    // 保留安全的HTML标签
    $allowedTags = '<p><br><a><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td><div><span><img><hr><sub><sup>';
    $string = strip_tags($string, $allowedTags);
    
    return trim($string);
}

/**
 * 判断是否SSL
 */
function is_ssl(): bool
{
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return true;
    }
    return false;
}
