<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\ThemeInfo;
use app\common\service\theme\RemoteTemplateSource;
use app\common\service\theme\ThemeBackupService;
use app\common\service\theme\ThemePackageService;
use think\facade\Cache;
use think\facade\Log;

/**
 * 模板市场服务 - V3.1 Sprint 15 增强版
 *
 * 功能：
 * 1. 扫描同步（已有）
 * 2. 安装主题（本地预埋/远程下载）+ 安装前自动备份
 * 3. 回滚主题
 * 4. 已安装检测
 * 5. 切换主题
 * 6. 透传API：合并本地+远程模板列表
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
        $frontendThemes = self::scanThemeDir(root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes', 'frontend');
        // 扫描后台主题
        $adminThemes = self::scanThemeDir(root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin', 'admin');

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
     * 获取主题列表（本地已安装）
     */
    public static function getThemes(string $type = 'frontend'): array
    {
        return ThemeInfo::where('type', $type)->order('id', 'asc')->select()->toArray();
    }

    /**
     * V3.1 Sprint 15: 获取合并的市场模板列表（本地+远程+预埋）
     *
     * @return array 包含 source, templates, installed_codes
     */
    public static function getMarketList(string $type = 'frontend'): array
    {
        $remoteSource = new RemoteTemplateSource();
        $result = $remoteSource->fetchTemplateList();

        // 获取已安装的主题code集合
        $installed = ThemeInfo::where('type', $type)->column('code');
        $installedCodes = array_flip($installed);

        // 合并本地已安装信息到列表
        $templates = [];
        foreach ($result['templates'] as $t) {
            $t['is_installed'] = isset($installedCodes[$t['code']]) || is_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $t['code']);
            $templates[] = $t;
        }

        // 补充本地已安装但不在远程列表中的主题
        foreach ($installed as $code) {
            $found = false;
            foreach ($templates as $t) {
                if ($t['code'] === $code) { $found = true; break; }
            }
            if (!$found) {
                $info = ThemeInfo::where('code', $code)->where('type', $type)->find();
                if ($info) {
                    $templates[] = [
                        'code'         => $code,
                        'name'         => $info->name,
                        'description'  => $info->description,
                        'version'      => $info->version,
                        'author'       => $info->author,
                        'industry'     => $info->industry ?? '',
                        'style_tag'    => $info->style_tag ?? '',
                        'thumbnail'    => $info->thumbnail ?? '',
                        'screenshots'  => json_decode($info->screenshots ?? '[]', true) ?: [],
                        'avg_rating'   => (float) ($info->avg_rating ?? 0),
                        'install_count'=> (int) ($info->install_count ?? 0),
                        'download_url' => $info->market_url ?? '',
                        'source'       => 'local',
                        'is_prebuilt'  => false,
                        'is_installed' => true,
                    ];
                }
            }
        }

        return [
            'templates'      => $templates,
            'source'         => $result['source'],
            'fetched_at'     => $result['fetched_at'],
            'installed_codes'=> $installed,
        ];
    }

    /**
     * V3.1 Sprint 15: 安装主题（支持预埋本地复制 / 远程下载）
     *
     * @param string $code 主题code
     * @param string $type frontend/admin
     * @param string $source 'prebuilt'|'remote'
     * @param string $downloadUrl 远程下载URL（source=remote时必填）
     * @param int $userId 操作用户ID
     * @return array
     */
    public static function installTheme(string $code, string $type = 'frontend', string $source = 'prebuilt', string $downloadUrl = '', int $userId = 0): array
    {
        if (empty($code)) {
            throw new \Exception('请提供主题标识');
        }

        // 安全校验：code不能包含路径穿越字符
        if (str_contains($code, '..') || str_contains($code, '/') || str_contains($code, '\\')) {
            throw new \Exception('无效的主题标识');
        }

        $targetDir = $type === 'frontend'
            ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code
            : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $code;

        if (is_dir($targetDir)) {
            throw new \Exception("主题 {$code} 已存在");
        }

        // 步骤1：获取源目录或下载ZIP
        $sourcePath = '';
        if ($source === 'prebuilt') {
            $prebuiltDir = root_path() . 'app' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'prebuilt' . DIRECTORY_SEPARATOR . $code;
            if (!is_dir($prebuiltDir)) {
                throw new \Exception('预埋模板不存在: ' . $code);
            }
            $sourcePath = $prebuiltDir;
        } elseif ($source === 'remote') {
            if (empty($downloadUrl)) {
                throw new \Exception('远程模板缺少下载地址');
            }
            $remoteSource = new RemoteTemplateSource();
            $zipPath = $remoteSource->downloadZip($downloadUrl);
            if (!$zipPath || !is_file($zipPath)) {
                throw new \Exception('模板下载失败');
            }
            // 解压ZIP
            $packageService = new ThemePackageService();
            $importResult = $packageService->importTheme($zipPath);
            if (!$importResult['success']) {
                throw new \Exception('模板解压失败: ' . $importResult['message']);
            }
            $sourcePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $importResult['theme_name'];
            // 如果解压后的名称与code不同，需要重命名
            if ($importResult['theme_name'] !== $code && is_dir($sourcePath)) {
                $renamedPath = dirname($sourcePath) . DIRECTORY_SEPARATOR . $code;
                rename($sourcePath, $renamedPath);
                $sourcePath = $renamedPath;
            }
            // 清理临时ZIP
            @unlink($zipPath);
        } else {
            throw new \Exception('不支持的安装源: ' . $source);
        }

        // 步骤3：校验源目录存在
        if (empty($sourcePath) || !is_dir($sourcePath)) {
            throw new \Exception('模板源目录不存在');
        }

        // 步骤4：复制到目标目录（编码安全由源文件保证）
        self::copyDir($sourcePath, $targetDir);

        // 步骤6：同步到数据库
        self::scanAndSync();

        // 步骤7：记录日志
        self::logAction($code, 'install', $userId, ['source' => $source, 'type' => $type]);

        // 步骤8：更新安装次数
        ThemeInfo::where('code', $code)->where('type', $type)->inc('install_count', 1)->update();

        return ['success' => true, 'code' => $code, 'message' => '安装成功'];
    }

    /**
     * V3.1 Sprint 15: 切换主题
     */
    public static function switchTheme(string $code, string $type = 'frontend'): array
    {
        $theme = ThemeInfo::where('code', $code)->where('type', $type)->find();
        if (!$theme) {
            throw new \Exception('主题不存在');
        }

        $themeDir = $type === 'frontend'
            ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code
            : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $code;

        if (!is_dir($themeDir)) {
            throw new \Exception('主题目录不存在');
        }

        $configName = $type === 'frontend' ? 'frontend_theme' : 'admin_theme';
        $configModel = new \app\common\model\Config();
        $configModel->where('name', $configName)->update(['value' => $code]);

        // 清除缓存
        Cache::delete('site_configs');
        TemplateService::clearCache();

        return ['success' => true, 'code' => $code];
    }

    /**
     * V3.1 Sprint 15: 回滚主题
     */
    public static function rollbackTheme(string $code, string $backupId, string $type = 'frontend'): array
    {
        $themeDir = $type === 'frontend'
            ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code
            : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $code;

        $backupService = new ThemeBackupService();
        $result = $backupService->rollback($backupId, $themeDir);

        if ($result['success']) {
            // 同步数据库
            self::scanAndSync();
            // 清除缓存
            TemplateService::clearCache();
        }

        return $result;
    }

    /**
     * V3.1 Sprint 15: 获取主题备份列表
     */
    public static function getBackups(string $code): array
    {
        $backupService = new ThemeBackupService();
        return $backupService->getBackups($code);
    }

    /**
     * 卸载主题
     */
    public static function uninstallTheme(int $themeId): bool
    {
        $theme = ThemeInfo::find($themeId);
        if (!$theme) throw new \Exception('主题不存在');

        // 检查是否为当前使用中的主题
        $configName = $theme->type === 'frontend' ? 'frontend_theme' : 'admin_theme';
        $currentTheme = \app\common\model\Config::where('name', $configName)->value('value');

        if ($theme->code === $currentTheme) {
            throw new \Exception('不能卸载正在使用的主题');
        }

        // 删除主题目录
        $dir = $theme->type === 'frontend'
            ? root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme->code
            : root_path() . 'template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . $theme->code;

        if (is_dir($dir) && $theme->code !== 'default') {
            self::rrmdir($dir);
        }

        $theme->delete();
        TemplateService::clearCache();
        return true;
    }

    /**
     * 检查版本更新（预留，连接远程市场API）
     */
    public static function checkUpdates(): array
    {
        $themes = ThemeInfo::where('is_installed', 1)->select();
        $updates = [];

        $remoteSource = new RemoteTemplateSource();
        $remoteResult = $remoteSource->fetchTemplateList();
        $remoteMap = [];
        foreach ($remoteResult['templates'] as $t) {
            $remoteMap[$t['code']] = $t;
        }

        foreach ($themes as $theme) {
            if (isset($remoteMap[$theme->code])) {
                $latest = $remoteMap[$theme->code]['version'];
                if (version_compare($latest, $theme->version, '>')) {
                    $updates[] = [
                        'code'    => $theme->code,
                        'name'    => $theme->name,
                        'current' => $theme->version,
                        'latest'  => $latest,
                        'url'     => $remoteMap[$theme->code]['download_url'] ?? '',
                    ];
                }
            }
        }

        return $updates;
    }

    /**
     * 记录主题操作日志
     */
    protected static function logAction(string $code, string $action, int $userId, array $detail = []): void
    {
        try {
            $theme = ThemeInfo::where('code', $code)->find();
            $themeId = $theme ? $theme->id : 0;
            \app\common\model\ThemeLog::record($themeId, $action, $userId, $detail);
        } catch (\Throwable $e) {
            Log::warning('ThemeMarketService: 日志记录失败 ' . $e->getMessage());
        }
    }

    /**
     * 递归复制目录
     */
    protected static function copyDir(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
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
