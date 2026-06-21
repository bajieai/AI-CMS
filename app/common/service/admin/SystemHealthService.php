<?php
declare(strict_types=1);
namespace app\common\service\admin;

use think\facade\Db;
use think\facade\Cache;

class SystemHealthService
{
    public static function checkAll(): array
    {
        return [
            'php' => self::checkPhp(),
            'mysql' => self::checkMysql(),
            'disk' => self::checkDisk(),
            'cache' => self::checkCache(),
            'upload' => self::checkUpload(),
            'permission' => self::checkPermission(),
            'extension' => self::checkExtensions(),
        ];
    }

    private static function checkPhp(): array
    { return ['name' => 'PHP版本', 'status' => 'ok', 'value' => PHP_VERSION, 'detail' => 'PHP >= 8.1']; }

    private static function checkMysql(): array
    {
        try {
            $version = Db::query('SELECT VERSION() as v')[0]['v'] ?? 'unknown';
            return ['name' => 'MySQL数据库', 'status' => 'ok', 'value' => $version, 'detail' => '连接正常'];
        } catch (\Throwable $e) { return ['name' => 'MySQL数据库', 'status' => 'error', 'value' => '', 'detail' => $e->getMessage()]; }
    }

    private static function checkDisk(): array
    {
        $free = disk_free_space(root_path());
        $total = disk_total_space(root_path());
        $usedPercent = $total > 0 ? round(($total - $free) / $total * 100, 1) : 0;
        return ['name' => '磁盘空间', 'status' => $usedPercent > 90 ? 'warning' : 'ok', 'value' => $usedPercent . '%', 'detail' => '已用 ' . round(($total - $free) / 1073741824, 1) . 'GB / ' . round($total / 1073741824, 1) . 'GB'];
    }

    private static function checkCache(): array
    {
        try {
            Cache::set('health_check', 'ok', 10);
            $val = Cache::get('health_check');
            return ['name' => '缓存服务', 'status' => $val === 'ok' ? 'ok' : 'error', 'value' => '', 'detail' => '读写正常'];
        } catch (\Throwable $e) { return ['name' => '缓存服务', 'status' => 'error', 'value' => '', 'detail' => $e->getMessage()]; }
    }

    private static function checkUpload(): array
    {
        $maxSize = ini_get('upload_max_filesize');
        return ['name' => '文件上传', 'status' => $maxSize && $maxSize !== '0' ? 'ok' : 'warning', 'value' => $maxSize, 'detail' => '最大上传: ' . $maxSize];
    }

    private static function checkPermission(): array
    {
        $dirs = [runtime_path(), public_path() . 'uploads'];
        $issues = [];
        foreach ($dirs as $dir) { if (!is_writable($dir)) $issues[] = $dir; }
        return ['name' => '目录权限', 'status' => empty($issues) ? 'ok' : 'error', 'value' => '', 'detail' => empty($issues) ? '所有目录可写' : '不可写: ' . implode(', ', $issues)];
    }

    private static function checkExtensions(): array
    {
        $required = ['pdo_mysql', 'mbstring', 'json', 'openssl', 'curl', 'gd', 'zip'];
        $missing = [];
        foreach ($required as $ext) { if (!extension_loaded($ext)) $missing[] = $ext; }
        return ['name' => 'PHP扩展', 'status' => empty($missing) ? 'ok' : 'error', 'value' => '', 'detail' => empty($missing) ? '所有必需扩展已安装' : '缺失: ' . implode(', ', $missing)];
    }
}
