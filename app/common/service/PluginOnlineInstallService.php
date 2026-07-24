<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PluginInstallLog;
use think\facade\Db;
use think\facade\Log;

/**
 * 插件在线安装服务 — V2.9.28 P-1
 *
 * SSE集成方案（小扣v2审核问题4）：方案A — 安装步骤拆分(下载→校验→解压→注册)，
 * 每步完成后写入SseNotificationService的DB队列，前端SSE连接读取队列更新进度条。
 * 安装前set_time_limit(0)，需确认nginx proxy_buffering off + php output_buffering off。
 */
class PluginOnlineInstallService
{
    private string $pluginDir;
    private int $stepCount = 4;

    public function __construct()
    {
        $this->pluginDir = root_path() . 'plugin/';
    }

    /**
     * 在线安装插件（步骤拆分）
     */
    public function install(string $pluginName, string $downloadUrl, string $version, int $userId = 0): array
    {
        @set_time_limit(0);

        // 创建安装日志
        $logId = $this->createLog($pluginName, 'install', '', $version, $userId);

        try {
            // Step 1: 下载
            $this->notifyProgress($logId, 1, $this->stepCount, 'downloading', '正在下载插件包...');
            $zipFile = $this->download($downloadUrl, $pluginName);
            $this->notifyProgress($logId, 1, $this->stepCount, 'downloaded', '下载完成');

            // Step 2: 校验
            $this->notifyProgress($logId, 2, $this->stepCount, 'verifying', '正在校验文件...');
            $this->verifyZip($zipFile);
            // 安全扫描
            $securityService = new \app\common\service\PluginSandboxService();
            $scanResult = $securityService->scanZip($zipFile);
            if (!$scanResult['safe']) {
                throw new \RuntimeException('安全扫描未通过: ' . ($scanResult['message'] ?? ''));
            }
            $this->notifyProgress($logId, 2, $this->stepCount, 'verified', '校验通过');

            // Step 3: 解压
            $this->notifyProgress($logId, 3, $this->stepCount, 'extracting', '正在解压...');
            $extractDir = $this->extract($zipFile, $pluginName);
            $this->notifyProgress($logId, 3, $this->stepCount, 'extracted', '解压完成');

            // Step 4: 注册
            $this->notifyProgress($logId, 4, $this->stepCount, 'registering', '正在注册插件...');
            $this->registerPlugin($pluginName, $version);
            $this->notifyProgress($logId, 4, $this->stepCount, 'done', '安装完成');

            // 清理临时文件
            @unlink($zipFile);

            // 更新日志状态
            $this->updateLog($logId, 1, '安装成功');

            return ['success' => true, 'message' => '插件安装成功', 'log_id' => $logId];
        } catch (\Throwable $e) {
            $this->updateLog($logId, 2, '安装失败: ' . $e->getMessage());
            Log::error('[PluginInstall] 安装失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'log_id' => $logId];
        }
    }

    /**
     * 更新插件（带回滚）
     */
    public function update(string $pluginName, string $downloadUrl, string $newVersion, int $userId = 0): array
    {
        @set_time_limit(0);

        $currentVersion = $this->getPluginVersion($pluginName);
        $logId = $this->createLog($pluginName, 'update', $currentVersion, $newVersion, $userId);

        // 备份旧版
        $backupPath = $this->backupPlugin($pluginName);

        try {
            // 执行安装（覆盖旧文件）
            $installResult = $this->install($pluginName, $downloadUrl, $newVersion, $userId);
            if (!$installResult['success']) {
                throw new \RuntimeException($installResult['message']);
            }

            // 更新日志
            $this->updateLog($logId, 1, '更新成功');
            @rmdir($backupPath);

            return ['success' => true, 'message' => '插件更新成功', 'log_id' => $logId];
        } catch (\Throwable $e) {
            // 回滚
            $this->rollbackPlugin($pluginName, $backupPath);
            $this->updateLog($logId, 2, '更新失败已回滚: ' . $e->getMessage());
            return ['success' => false, 'message' => '更新失败已回滚: ' . $e->getMessage(), 'log_id' => $logId];
        }
    }

    /**
     * 下载文件
     */
    private function download(string $url, string $pluginName): string
    {
        $tempFile = sys_get_temp_dir() . '/plugin_' . $pluginName . '_' . time() . '.zip';
        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException('下载失败: 无法访问URL');
        }
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * 校验ZIP
     */
    private function verifyZip(string $zipFile): void
    {
        if (!file_exists($zipFile)) {
            throw new \RuntimeException('ZIP文件不存在');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('无效的ZIP文件');
        }
        $zip->close();
    }

    /**
     * 解压
     */
    private function extract(string $zipFile, string $pluginName): string
    {
        $targetDir = $this->pluginDir . $pluginName;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new \ZipArchive();
        $zip->open($zipFile);
        $zip->extractTo($targetDir);
        $zip->close();

        return $targetDir;
    }

    /**
     * 注册插件
     */
    private function registerPlugin(string $pluginName, string $version): void
    {
        // 检查plugin.json是否存在
        $jsonFile = $this->pluginDir . $pluginName . '/plugin.json';
        if (!file_exists($jsonFile)) {
            throw new \RuntimeException('plugin.json不存在');
        }

        $config = json_decode(file_get_contents($jsonFile), true);
        if (!$config) {
            throw new \RuntimeException('plugin.json格式错误');
        }

        // 写入数据库
        Db::name('plugin')->updateOrInsert(
            ['name' => $pluginName],
            [
                'name' => $pluginName,
                'title' => $config['title'] ?? $pluginName,
                'description' => $config['description'] ?? '',
                'version' => $version,
                'status' => 0, // 默认禁用，需手动启用
                'install_time' => time(),
            ]
        );
    }

    /**
     * 备份插件
     */
    private function backupPlugin(string $pluginName): string
    {
        $pluginPath = $this->pluginDir . $pluginName;
        $backupPath = sys_get_temp_dir() . '/plugin_backup_' . $pluginName . '_' . time();

        if (is_dir($pluginPath)) {
            $this->copyDir($pluginPath, $backupPath);
        }

        return $backupPath;
    }

    /**
     * 回滚插件
     */
    private function rollbackPlugin(string $pluginName, string $backupPath): void
    {
        $pluginPath = $this->pluginDir . $pluginName;
        if (is_dir($pluginPath)) {
            $this->removeDir($pluginPath);
        }
        if (is_dir($backupPath)) {
            $this->copyDir($backupPath, $pluginPath);
        }
    }

    /**
     * 递归复制目录
     */
    private function copyDir(string $src, string $dst): void
    {
        if (!is_dir($dst)) mkdir($dst, 0755, true);
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file == '.' || $file == '..') continue;
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    /**
     * 递归删除目录
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * 获取插件当前版本
     */
    private function getPluginVersion(string $pluginName): string
    {
        $plugin = Db::name('plugin')->where('name', $pluginName)->find();
        return $plugin['version'] ?? '';
    }

    /**
     * 创建安装日志
     */
    private function createLog(string $pluginName, string $action, string $versionFrom, string $versionTo, int $userId): int
    {
        $log = PluginInstallLog::create([
            'plugin_name' => $pluginName,
            'action' => $action,
            'version_from' => $versionFrom,
            'version_to' => $versionTo,
            'status' => 0,
            'log' => '',
            'user_id' => $userId,
            'create_time' => time(),
        ]);
        return $log->id;
    }

    /**
     * 更新日志
     */
    private function updateLog(int $logId, int $status, string $message): void
    {
        PluginInstallLog::where('id', $logId)->update([
            'status' => $status,
            'log' => $message,
        ]);
    }

    /**
     * 通知进度（SSE方案A：写入DB队列）
     */
    private function notifyProgress(int $logId, int $step, int $total, string $status, string $message): void
    {
        // 写入SSE通知队列（复用V2.9.27的SseNotificationService）
        try {
            Db::name('sse_notification')->insert([
                'channel' => 'plugin_install_' . $logId,
                'event' => 'progress',
                'data' => json_encode([
                    'step' => $step,
                    'total' => $total,
                    'progress' => round($step / $total * 100),
                    'status' => $status,
                    'message' => $message,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // SSE通知失败不影响主流程
        }
    }

    /**
     * 获取安装日志
     */
    public function getInstallLogs(string $pluginName = '', int $page = 1, int $limit = 20): array
    {
        $query = PluginInstallLog::order('id', 'desc');
        if (!empty($pluginName)) {
            $query->where('plugin_name', $pluginName);
        }
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }
}
