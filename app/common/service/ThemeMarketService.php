<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\ThemeInfo;
use think\facade\Filesystem;

/**
 * 模板市场服务 - V2.5新增
 * 主题信息管理 + 在线安装 + 版本检测
 */
class ThemeMarketService
{
    /**
     * 扫描已安装的主题并同步到theme_info表
     */
    public static function scanAndSync(): array
    {
        $result = ['added' => 0, 'updated' => 0];

        // 扫描前台主题
        $frontendThemes = self::scanThemeDir(template_path() . 'themes', 'frontend');
        // 扫描后台主题
        $adminThemes = self::scanThemeDir(template_path() . 'admin', 'admin');

        foreach (array_merge($frontendThemes, $adminThemes) as $theme) {
            $existing = ThemeInfo::where('code', $theme['code'])->where('type', $theme['type'])->find();

            if ($existing) {
                $existing->name = $theme['name'];
                $existing->version = $theme['version'];
                $existing->author = $theme['author'] ?? $existing->author;
                $existing->description = $theme['description'] ?? $existing->description;
                $existing->installed_version = $theme['version'];
                $existing->is_installed = 1;
                $existing->save();
                $result['updated']++;
            } else {
                ThemeInfo::create([
                    'code' => $theme['code'],
                    'type' => $theme['type'],
                    'name' => $theme['name'],
                    'version' => $theme['version'],
                    'author' => $theme['author'] ?? '',
                    'description' => $theme['description'] ?? '',
                    'thumbnail' => $theme['thumbnail'] ?? '',
                    'is_installed' => 1,
                    'installed_version' => $theme['version'],
                ]);
                $result['added']++;
            }
        }

        return $result;
    }

    /**
     * 扫描主题目录
     */
    protected static function scanThemeDir(string $dir, string $type): array
    {
        if (!is_dir($dir)) return [];

        $themes = [];
        $dirs = array_filter(glob($dir . '/*'), 'is_dir');

        foreach ($dirs as $themeDir) {
            $code = basename($themeDir);
            $jsonFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';

            if (file_exists($jsonFile)) {
                $meta = json_decode(file_get_contents($jsonFile), true) ?: [];
            } else {
                $meta = ['name' => ucfirst($code), 'version' => '1.0.0'];
            }

            $themes[] = [
                'code' => $code,
                'type' => $type,
                'name' => $meta['name'] ?? ucfirst($code),
                'version' => $meta['version'] ?? '1.0.0',
                'author' => $meta['author'] ?? '',
                'description' => $meta['description'] ?? '',
                'thumbnail' => $meta['thumbnail'] ?? '',
            ];
        }

        return $themes;
    }

    /**
     * 获取主题列表
     */
    public static function getThemes(string $type = 'frontend'): array
    {
        return ThemeInfo::where('type', $type)->order('id', 'asc')->select()->toArray();
    }

    /**
     * 安装主题（从ZIP或远程URL）
     */
    public static function installTheme(string $sourcePath, string $type = 'frontend'): array
    {
        // 简化实现：假设sourcePath是已解压的主题目录
        $code = basename($sourcePath);
        $targetDir = $type === 'frontend'
            ? template_path() . 'themes' . DIRECTORY_SEPARATOR . $code
            : template_path() . 'admin' . DIRECTORY_SEPARATOR . $code;

        if (is_dir($targetDir)) {
            throw new \Exception("主题 {$code} 已存在");
        }

        // 复制目录
        self::copyDir($sourcePath, $targetDir);

        // 同步到数据库
        self::scanAndSync();

        return ['success' => true, 'code' => $code];
    }

    /**
     * 卸载主题
     */
    public static function uninstallTheme(int $themeId): bool
    {
        $theme = ThemeInfo::find($themeId);
        if (!$theme) throw new \Exception('主题不存在');

        // 检查是否为当前使用中的主题
        $currentTheme = $theme->type === 'frontend'
            ? ConfigService::get('frontend_theme', 'default')
            : ConfigService::get('admin_theme', 'default');

        if ($theme->code === $currentTheme) {
            throw new \Exception('不能卸载正在使用的主题');
        }

        // 删除主题目录
        $dir = $theme->type === 'frontend'
            ? template_path() . 'themes' . DIRECTORY_SEPARATOR . $theme->code
            : template_path() . 'admin' . DIRECTORY_SEPARATOR . $theme->code;

        if (is_dir($dir) && $theme->code !== 'default') {
            self::rrmdir($dir);
        }

        $theme->delete();
        return true;
    }

    /**
     * 检查版本更新（预留，连接远程市场API）
     */
    public static function checkUpdates(): array
    {
        // 预留：连接远程主题市场检查更新
        $themes = ThemeInfo::where('is_installed', 1)->select();
        $updates = [];

        foreach ($themes as $theme) {
            // TODO: 调用远程API检查更新
            // $latestVersion = self::fetchLatestVersion($theme->code, $theme->type);
            // if (version_compare($latestVersion, $theme->version, '>')) {
            //     $updates[] = [...];
            // }
        }

        return $updates;
    }

    /**
     * 递归复制目录
     */
    protected static function copyDir(string $src, string $dst): void
    {
        mkdir($dst, 0755, true);
        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') continue;
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            is_dir($srcPath) ? self::copyDir($srcPath, $dstPath) : copy($srcPath, $dstPath);
        }
    }

    /**
     * 递归删除目录
     */
    protected static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? self::rrmdir($file) : unlink($file);
        }
        rmdir($dir);
    }
}
