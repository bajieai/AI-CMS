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

use app\common\model\UpgradeLog;
use app\common\model\UpgradePatch;
use GuzzleHttp\Client;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

/**
 * 在线升级服务 - V2.9.44
 *
 * 核心流程：版本检查 → 环境检查 → 下载升级包 → 校验 → 备份 → SQL迁移 → 文件更新 → 清缓存 → 更新版本号
 * 安全机制：文件保护清单 + SQL补丁幂等执行 + 自动备份 + 失败回滚
 */
class UpgradeService
{
    /**
     * 升级工作目录
     */
    protected string $workPath;

    /**
     * 备份目录
     */
    protected string $backupPath;

    /**
     * HTTP客户端
     */
    protected Client $httpClient;

    /**
     * 当前升级日志ID
     */
    protected int $logId = 0;

    /**
     * 升级步骤记录
     */
    protected array $steps = [];

    public function __construct()
    {
        $this->workPath = runtime_path() . 'upgrade' . DIRECTORY_SEPARATOR;
        $this->backupPath = $this->workPath . 'backups' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->workPath)) {
            mkdir($this->workPath, 0755, true);
        }
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $this->httpClient = new Client([
            'timeout' => Config::get('upgrade.timeout', 120),
            'verify' => Config::get('upgrade.verify_ssl', true),
            'headers' => [
                'User-Agent' => 'AI-CMS-UpgradeBot/2.9.44',
                'Accept' => 'application/vnd.github+json,application/json',
            ],
        ]);
    }

    /**
     * 获取当前版本号（优先 app_version，回退 version）
     */
    public function getCurrentVersion(): string
    {
        $appVersion = ConfigService::get('system.app_version', '');
        if (!empty($appVersion)) {
            return $this->normalizeVersion($appVersion);
        }
        $version = ConfigService::get('system.version', '');
        if (!empty($version)) {
            return $this->normalizeVersion($version);
        }
        return '2.9.43';
    }

    /**
     * 规范化版本号：去掉前缀V/v，保留 x.y.z
     */
    public function normalizeVersion(string $version): string
    {
        $version = trim($version);
        $version = ltrim($version, 'vV');
        return preg_replace('/[^0-9.]/', '', $version) ?: '0.0.0';
    }

    /**
     * 检查最新版本（带10分钟缓存）
     */
    public function checkLatest(): array
    {
        return Cache::remember('upgrade_latest_check', function () {
            $config = Config::get('upgrade');
            $owner = $config['gitee_owner'] ?? 'bajieai';
            $repo = $config['gitee_repo'] ?? 'ai-cms';
            $token = $config['gitee_token'] ?? '';

            $url = "https://gitee.com/api/v5/repos/{$owner}/{$repo}/releases/latest";
            $query = [];
            if (!empty($token)) {
                $query['access_token'] = $token;
            }

            try {
                $response = $this->httpClient->get($url, ['query' => $query]);
                $body = json_decode((string) $response->getBody(), true);

                if (empty($body) || !isset($body['tag_name'])) {
                    return [
                        'success' => false,
                        'msg' => '未获取到最新版本信息',
                        'data' => null,
                    ];
                }

                $latestVersion = $this->normalizeVersion($body['tag_name']);
                $currentVersion = $this->getCurrentVersion();
                $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

                // 查找升级包资源（优先匹配 ai-cms-upgrade-*.zip）
                $asset = null;
                $assets = $body['assets'] ?? [];
                foreach ($assets as $item) {
                    $name = $item['name'] ?? '';
                    if (str_starts_with($name, 'ai-cms-upgrade-') && str_ends_with($name, '.zip')) {
                        $asset = $item;
                        break;
                    }
                }

                // 回退：任意 zip
                if (!$asset) {
                    foreach ($assets as $item) {
                        if (str_ends_with($item['name'] ?? '', '.zip')) {
                            $asset = $item;
                            break;
                        }
                    }
                }

                return [
                    'success' => true,
                    'msg' => '获取成功',
                    'data' => [
                        'current_version' => $currentVersion,
                        'latest_version' => $latestVersion,
                        'has_update' => $hasUpdate,
                        'tag_name' => $body['tag_name'],
                        'name' => $body['name'] ?? '',
                        'body' => $body['body'] ?? '',
                        'published_at' => $body['published_at'] ?? '',
                        'asset' => $asset,
                        'download_url' => $asset['browser_download_url'] ?? ($asset['url'] ?? ''),
                    ],
                ];
            } catch (\Throwable $e) {
                Log::error('[Upgrade] 检查版本失败: ' . $e->getMessage());
                return [
                    'success' => false,
                    'msg' => '检查版本失败: ' . $e->getMessage(),
                    'data' => null,
                ];
            }
        }, 600);
    }

    /**
     * 清除版本检查缓存
     */
    public function clearLatestCache(): void
    {
        Cache::delete('upgrade_latest_check');
    }

    /**
     * 环境检查
     */
    public function checkEnvironment(): array
    {
        $errors = [];
        $warnings = [];

        // PHP版本
        if (PHP_VERSION_ID < 80200) {
            $errors[] = 'PHP版本过低，需要 >= 8.2';
        }

        // 扩展
        if (!extension_loaded('zip')) {
            $errors[] = '缺少 zip 扩展';
        }
        if (!extension_loaded('json')) {
            $errors[] = '缺少 json 扩展';
        }

        // 数据库连接
        try {
            Db::query('SELECT 1');
        } catch (\Throwable $e) {
            $errors[] = '数据库连接失败: ' . $e->getMessage();
        }

        // 目录写权限
        $paths = [$this->workPath, $this->backupPath, runtime_path(), root_path()];
        foreach ($paths as $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $errors[] = '目录不可写: ' . $path;
            }
        }

        // 磁盘空间
        $freeSpace = disk_free_space(root_path());
        if ($freeSpace < 200 * 1024 * 1024) {
            $errors[] = '磁盘空间不足200MB';
        } elseif ($freeSpace < 500 * 1024 * 1024) {
            $warnings[] = '磁盘空间小于500MB，建议清理后再升级';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * 执行完整升级流程
     */
    public function upgrade(string $downloadUrl, string $expectedVersion): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        // 自举：首次使用时确保升级系统表存在
        $this->ensureTablesExist();

        $currentVersion = $this->getCurrentVersion();
        $this->logId = $this->createUpgradeLog($currentVersion, $expectedVersion);

        $this->addStep('init', '开始升级', 'pending');

        try {
            // Step 1: 环境检查
            $this->addStep('env_check', '环境检查', 'running');
            $env = $this->checkEnvironment();
            if (!$env['success']) {
                throw new \RuntimeException('环境检查未通过: ' . implode('; ', $env['errors']));
            }
            $this->addStep('env_check', '环境检查通过', 'done');

            // Step 2: 下载升级包
            $this->addStep('download', '下载升级包', 'running');
            $zipFile = $this->downloadPackage($downloadUrl);
            $this->addStep('download', '下载完成: ' . basename($zipFile), 'done');

            // Step 3: 校验并解压
            $this->addStep('verify', '校验升级包', 'running');
            $manifest = $this->verifyAndExtract($zipFile);
            $this->addStep('verify', '校验通过', 'done');

            // 校验目标版本
            $manifestToVersion = $this->normalizeVersion($manifest['to_version'] ?? '');
            if ($manifestToVersion && $manifestToVersion !== $this->normalizeVersion($expectedVersion)) {
                throw new \RuntimeException('升级包目标版本与期望版本不一致: ' . ($manifest['to_version'] ?? 'unknown'));
            }
            $toVersion = $manifestToVersion ?: $expectedVersion;

            // 校验 from_version
            $manifestFromVersion = $this->normalizeVersion($manifest['from_version'] ?? '');
            if ($manifestFromVersion && $manifestFromVersion !== $currentVersion) {
                $warnings = '升级包来源版本(' . $manifest['from_version'] . ')与当前版本(' . $currentVersion . ')不一致，继续升级可能存在风险';
                $this->addStep('version_match', $warnings, 'warning');
            }

            // Step 4: 备份
            $this->addStep('backup', '创建自动备份', 'running');
            $backupResult = $this->createBackups();
            $this->updateLogBackup($backupResult);
            $this->addStep('backup', '备份完成', 'done');

            // Step 5: 执行SQL迁移
            $this->addStep('sql', '执行数据库迁移', 'running');
            $this->executeSqlPatches($manifest['sql_patches'] ?? [], $toVersion);
            $this->addStep('sql', '数据库迁移完成', 'done');

            // Step 6: 更新文件
            $this->addStep('files', '更新系统文件', 'running');
            $fileResult = $this->updateFiles($manifest);
            $this->addStep('files', '文件更新完成: 新增' . $fileResult['added'] . ', 修改' . $fileResult['modified'] . ', 跳过' . $fileResult['skipped'] . ', 删除' . $fileResult['deleted'], 'done');

            // Step 7: 清理缓存
            $this->addStep('cache', '清理缓存', 'running');
            $this->clearCache();
            $this->addStep('cache', '缓存清理完成', 'done');

            // Step 8: 更新版本号
            $this->addStep('version', '更新版本号', 'running');
            $this->updateVersion($toVersion);
            $this->addStep('version', '版本号已更新为 V' . $toVersion, 'done');

            // 完成
            $this->finishLog(UpgradeLog::STATUS_SUCCESS, '升级成功');
            $this->clearLatestCache();

            return [
                'success' => true,
                'msg' => '升级成功',
                'data' => [
                    'from_version' => $currentVersion,
                    'to_version' => $toVersion,
                    'log_id' => $this->logId,
                    'file_result' => $fileResult,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('[Upgrade] 升级失败: ' . $e->getMessage());

            // 自动回滚
            $this->addStep('rollback', '升级失败，正在自动回滚: ' . $e->getMessage(), 'running');
            try {
                $this->rollback();
                $this->addStep('rollback', '已自动回滚到升级前状态', 'done');
                $this->finishLog(UpgradeLog::STATUS_FAILED, '升级失败已回滚: ' . $e->getMessage());
                return [
                    'success' => false,
                    'msg' => '升级失败，已自动回滚: ' . $e->getMessage(),
                    'data' => ['log_id' => $this->logId],
                ];
            } catch (\Throwable $rollbackEx) {
                $this->addStep('rollback', '回滚失败: ' . $rollbackEx->getMessage(), 'failed');
                $this->finishLog(UpgradeLog::STATUS_FAILED, '升级失败且回滚失败: ' . $e->getMessage() . ' | 回滚错误: ' . $rollbackEx->getMessage());
                return [
                    'success' => false,
                    'msg' => '升级失败，回滚也失败: ' . $e->getMessage() . '（回滚错误: ' . $rollbackEx->getMessage() . '）',
                    'data' => ['log_id' => $this->logId],
                ];
            }
        }
    }

    /**
     * 下载升级包
     */
    protected function downloadPackage(string $url): string
    {
        if (empty($url)) {
            throw new \RuntimeException('下载地址为空');
        }

        $filename = 'upgrade_' . date('Ymd_His') . '_' . md5($url . time()) . '.zip';
        $filepath = $this->workPath . $filename;

        // Gitee assets 可能需要 token；若配置中提供，自动附加
        $config = Config::get('upgrade');
        $token = $config['gitee_token'] ?? '';
        $options = ['sink' => $filepath];
        if (!empty($token) && str_contains($url, 'gitee.com')) {
            $options['query'] = ['access_token' => $token];
        }

        try {
            $this->httpClient->get($url, $options);
        } catch (\Throwable $e) {
            throw new \RuntimeException('下载升级包失败: ' . $e->getMessage());
        }

        if (!file_exists($filepath) || filesize($filepath) < 100) {
            throw new \RuntimeException('下载文件无效或为空');
        }

        return $filepath;
    }

    /**
     * 校验ZIP并解压，读取manifest.json
     */
    protected function verifyAndExtract(string $zipFile): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('无法打开升级包ZIP文件');
        }

        // 安全扫描：检查是否有跳出根目录的文件
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/')) {
                $zip->close();
                throw new \RuntimeException('升级包包含非法路径: ' . $entryName);
            }
        }

        // 解压到临时目录
        $extractDir = $this->workPath . 'extract_' . md5($zipFile . time()) . DIRECTORY_SEPARATOR;
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        $zip->extractTo($extractDir);
        $zip->close();

        // 如果ZIP根目录只有一个文件夹，进入该文件夹
        $entries = array_diff(scandir($extractDir), ['.', '..']);
        if (count($entries) === 1) {
            $first = reset($entries);
            $firstPath = $extractDir . $first;
            if (is_dir($firstPath)) {
                $extractDir = $firstPath . DIRECTORY_SEPARATOR;
            }
        }

        $manifestFile = $extractDir . 'manifest.json';
        if (!file_exists($manifestFile)) {
            throw new \RuntimeException('升级包缺少 manifest.json');
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (empty($manifest) || !isset($manifest['to_version'])) {
            throw new \RuntimeException('manifest.json 格式错误');
        }

        // 校验文件清单中的哈希
        $files = $manifest['files'] ?? [];
        foreach ($files as $relativePath => $expectedHash) {
            $absolutePath = $extractDir . ltrim($relativePath, '/');
            if (!file_exists($absolutePath)) {
                throw new \RuntimeException('升级包缺少文件: ' . $relativePath);
            }
            if (!empty($expectedHash)) {
                $actualHash = hash_file('sha256', $absolutePath);
                if ($actualHash !== $expectedHash) {
                    throw new \RuntimeException('文件校验失败: ' . $relativePath);
                }
            }
        }

        // 将解压目录存入 manifest，供后续使用
        $manifest['_extract_dir'] = rtrim($extractDir, DIRECTORY_SEPARATOR);

        return $manifest;
    }

    /**
     * 创建备份：数据库 + 将被覆盖的文件
     */
    protected function createBackups(): array
    {
        $result = ['db' => '', 'files' => ''];

        // 数据库备份（复用 BackupService）
        $backupService = new BackupService();
        $dbResult = $backupService->create('all', true);
        $result['db'] = $dbResult['path'] ?? '';

        return $result;
    }

    /**
     * 执行SQL补丁（幂等）
     */
    protected function executeSqlPatches(array $patches, string $version): void
    {
        $prefix = Db::getConfig('prefix');
        $extractDir = $this->getCurrentManifestDir();

        foreach ($patches as $patchFile) {
            $patchFile = ltrim($patchFile, '/');
            $checksum = md5($patchFile);

            // 检查是否已执行过（首次升级时 upgrade_patch 表可能尚未创建，需容错）
            try {
                $executed = UpgradePatch::where('version', $version)
                    ->where('patch_file', $patchFile)
                    ->where('status', UpgradePatch::STATUS_SUCCESS)
                    ->find();
                if ($executed) {
                    $this->addStep('sql_' . $patchFile, '已执行过，跳过: ' . $patchFile, 'skipped');
                    continue;
                }
            } catch (\Throwable) {
                // 表不存在，继续执行
            }

            $filePath = $extractDir . DIRECTORY_SEPARATOR . $patchFile;
            if (!file_exists($filePath)) {
                throw new \RuntimeException('SQL补丁文件不存在: ' . $patchFile);
            }

            $sql = file_get_contents($filePath);
            if ($sql === false) {
                throw new \RuntimeException('读取SQL补丁失败: ' . $patchFile);
            }

            // 替换表前缀占位符
            $sql = str_replace('{prefix}', $prefix, $sql);

            // 执行SQL（逐条，忽略已存在等错误）
            $this->executeSqlContent($sql, $patchFile);

            // 记录已执行
            UpgradePatch::create([
                'version' => $version,
                'patch_file' => $patchFile,
                'checksum' => $checksum,
                'status' => UpgradePatch::STATUS_SUCCESS,
            ]);
        }
    }

    /**
     * 安全执行SQL内容
     */
    protected function executeSqlContent(string $sql, string $patchFile): void
    {
        // 去掉BOM
        $sql = str_replace("\xEF\xBB\xBF", '', $sql);

        // 按语句分割（简单实现，支持多行）
        $lines = explode("\n", $sql);
        $current = '';
        $success = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*') || str_starts_with($line, '#')) {
                continue;
            }
            $current .= $line . "\n";
            if (str_ends_with($line, ';')) {
                try {
                    Db::execute(trim($current));
                    $success++;
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'already exists') !== false
                        || stripos($msg, '1050') !== false
                        || stripos($msg, 'duplicate') !== false
                        || stripos($msg, '1060') !== false
                        || stripos($msg, '1061') !== false
                        || stripos($msg, '1062') !== false
                        || stripos($msg, '1091') !== false
                    ) {
                        $skipped++;
                    } else {
                        $failed++;
                        Log::error('[Upgrade] SQL执行失败: ' . $patchFile . ' | ' . $msg . ' | SQL: ' . substr($current, 0, 200));
                        throw new \RuntimeException('SQL执行失败 [' . $patchFile . ']: ' . $msg);
                    }
                }
                $current = '';
            }
        }

        $this->addStep('sql_' . $patchFile, 'SQL执行: 成功' . $success . ', 跳过' . $skipped . ', 失败' . $failed, $failed > 0 ? 'failed' : 'done');
    }

    /**
     * 更新系统文件
     */
    protected function updateFiles(array $manifest): array
    {
        $extractDir = $this->getCurrentManifestDir();
        $files = $manifest['files'] ?? [];
        $deletedFiles = $manifest['deleted_files'] ?? [];
        $protectedPaths = Config::get('upgrade.protected_paths', []);

        $result = [
            'added' => 0,
            'modified' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'protected' => [],
        ];

        // 创建文件备份ZIP（仅备份被修改/删除的文件）
        $fileBackupZip = $this->backupPath . 'files_' . date('Ymd_His') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($fileBackupZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $relativePath => $_hash) {
            $relativePath = ltrim($relativePath, '/');
            $sourcePath = $extractDir . DIRECTORY_SEPARATOR . $relativePath;
            $targetPath = root_path() . $relativePath;

            // 检查是否在保护清单中
            if ($this->isProtected($relativePath, $protectedPaths)) {
                $result['skipped']++;
                $result['protected'][] = $relativePath;
                continue;
            }

            // 备份旧文件
            if (file_exists($targetPath)) {
                $zip->addFile($targetPath, $relativePath);
            }

            // 确保目标目录存在
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // 复制文件
            if (copy($sourcePath, $targetPath)) {
                if (file_exists($targetPath)) {
                    $result['modified']++;
                } else {
                    $result['added']++;
                }
            } else {
                throw new \RuntimeException('文件更新失败: ' . $relativePath);
            }
        }

        // 处理删除清单
        foreach ($deletedFiles as $relativePath) {
            $relativePath = ltrim($relativePath, '/');
            $targetPath = root_path() . $relativePath;

            if ($this->isProtected($relativePath, $protectedPaths)) {
                $result['skipped']++;
                continue;
            }

            if (file_exists($targetPath)) {
                $zip->addFile($targetPath, $relativePath);
                if (is_dir($targetPath)) {
                    $this->removeDir($targetPath);
                } else {
                    unlink($targetPath);
                }
                $result['deleted']++;
            }
        }

        $zip->close();

        // 如果ZIP为空则删除
        if (filesize($fileBackupZip) < 22) {
            unlink($fileBackupZip);
            $fileBackupZip = '';
        }

        // 记录文件备份路径
        if ($fileBackupZip) {
            $this->updateLogFileBackup($fileBackupZip);
        }

        return $result;
    }

    /**
     * 判断路径是否在保护清单中
     */
    protected function isProtected(string $relativePath, array $protectedPaths): bool
    {
        foreach ($protectedPaths as $pattern) {
            $pattern = trim($pattern, '/');
            if (empty($pattern)) {
                continue;
            }
            // 精确匹配
            if ($relativePath === $pattern) {
                return true;
            }
            // 目录前缀匹配（如 uploads/）
            if (str_ends_with($pattern, '/')) {
                if (str_starts_with($relativePath, $pattern)) {
                    return true;
                }
            }
            // 通配符匹配
            if (fnmatch($pattern, $relativePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 清理缓存
     */
    protected function clearCache(): void
    {
        $runtimePath = runtime_path();
        $dirs = ['cache/', 'temp/', 'admin/temp/', 'home/temp/', 'api/temp/'];

        foreach ($dirs as $dir) {
            $path = $runtimePath . $dir;
            if (is_dir($path)) {
                $this->removeDir($path);
                @mkdir($path, 0755, true);
            }
        }

        try {
            Cache::clear();
        } catch (\Throwable) {
            // 忽略
        }
    }

    /**
     * 更新版本号
     */
    protected function updateVersion(string $version): void
    {
        ConfigService::set('app_version', $version, 'system', '当前系统版本号');
        ConfigService::set('version', 'V' . $version, 'system', 'AI-CMS版本号');
    }

    /**
     * 回滚到升级前状态
     */
    public function rollback(): array
    {
        if ($this->logId <= 0) {
            return ['success' => false, 'msg' => '没有可回滚的升级记录'];
        }

        $log = UpgradeLog::find($this->logId);
        if (!$log) {
            return ['success' => false, 'msg' => '升级日志不存在'];
        }

        // 1. 恢复数据库
        $dbBackup = $log->backup_db_path;
        if (!empty($dbBackup) && file_exists($dbBackup)) {
            $backupService = new BackupService();
            // 复用 BackupService 的 restore 方法
            $backupService->restore($dbBackup, false);
        }

        // 2. 恢复文件
        $filesBackup = $log->backup_files_path;
        if (!empty($filesBackup) && file_exists($filesBackup)) {
            $zip = new \ZipArchive();
            if ($zip->open($filesBackup) === true) {
                $zip->extractTo(root_path());
                $zip->close();
            }
        }

        // 3. 清理缓存
        $this->clearCache();

        // 4. 更新日志状态
        $log->status = UpgradeLog::STATUS_ROLLED_BACK;
        $log->save();

        $this->clearLatestCache();

        return ['success' => true, 'msg' => '回滚完成'];
    }

    /**
     * 按日志ID回滚（供控制器调用）
     */
    public function rollbackByLogId(int $logId): array
    {
        $this->logId = $logId;
        return $this->rollback();
    }

    /**
     * 获取升级历史
     */
    public function getHistory(int $page = 1, int $limit = 20): array
    {
        $query = UpgradeLog::order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 获取当前步骤进度（供SSE使用）
     */
    public function getProgress(int $logId): array
    {
        $log = UpgradeLog::find($logId);
        if (!$log) {
            return ['success' => false, 'msg' => '日志不存在'];
        }
        return [
            'success' => true,
            'data' => [
                'log_id' => $logId,
                'status' => $log->status,
                'steps' => $log->upgrade_steps ?? [],
                'error_message' => $log->error_message ?? '',
            ],
        ];
    }

    /**
     * 获取当前manifest解压目录
     */
    protected function getCurrentManifestDir(): string
    {
        $steps = UpgradeLog::where('id', $this->logId)->value('upgrade_steps');
        if (!empty($steps)) {
            foreach ($steps as $step) {
                if (!empty($step['extract_dir'])) {
                    return $step['extract_dir'];
                }
            }
        }

        // 查找最新的 extract_ 目录
        $entries = glob($this->workPath . 'extract_*', GLOB_ONLYDIR);
        if (!empty($entries)) {
            usort($entries, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });
            return $entries[0];
        }

        throw new \RuntimeException('找不到升级包解压目录');
    }

    /**
     * 确保升级系统表存在（自举）
     */
    protected function ensureTablesExist(): void
    {
        $prefix = Db::getConfig('prefix');

        // 升级日志表
        Db::execute("CREATE TABLE IF NOT EXISTS `{$prefix}upgrade_log` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
            `from_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '升级前版本',
            `to_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '目标版本',
            `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:0待执行/1成功/2失败/3已回滚',
            `backup_db_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '数据库备份路径',
            `backup_files_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件备份路径',
            `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
            `upgrade_steps` json DEFAULT NULL COMMENT '升级步骤JSON',
            `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`),
            KEY `idx_create_time` (`create_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统升级日志表'");

        // SQL补丁执行记录表
        Db::execute("CREATE TABLE IF NOT EXISTS `{$prefix}upgrade_patch` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
            `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '版本号',
            `patch_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '补丁文件名',
            `checksum` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '校验值',
            `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态:1成功/2失败',
            `executed_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '执行时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_version_patch` (`version`,`patch_file`),
            KEY `idx_version` (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SQL升级补丁执行记录表'");
    }

    /**
     * 创建升级日志
     */
    protected function createUpgradeLog(string $fromVersion, string $toVersion): int
    {
        $log = UpgradeLog::create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => UpgradeLog::STATUS_PENDING,
            'backup_db_path' => '',
            'backup_files_path' => '',
            'error_message' => '',
            'upgrade_steps' => [],
        ]);
        return (int) $log->id;
    }

    /**
     * 添加升级步骤
     */
    protected function addStep(string $step, string $message, string $status = 'pending', array $extra = []): void
    {
        $this->steps[] = array_merge([
            'step' => $step,
            'message' => $message,
            'status' => $status,
            'time' => date('Y-m-d H:i:s'),
        ], $extra);

        if ($this->logId > 0) {
            UpgradeLog::where('id', $this->logId)->update([
                'upgrade_steps' => json_encode($this->steps, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * 更新日志备份路径
     */
    protected function updateLogBackup(array $backupResult): void
    {
        if ($this->logId <= 0) {
            return;
        }
        UpgradeLog::where('id', $this->logId)->update([
            'backup_db_path' => $backupResult['db'] ?? '',
            'backup_files_path' => $backupResult['files'] ?? '',
        ]);
    }

    /**
     * 更新日志文件备份路径
     */
    protected function updateLogFileBackup(string $fileBackupPath): void
    {
        if ($this->logId <= 0) {
            return;
        }
        UpgradeLog::where('id', $this->logId)->update([
            'backup_files_path' => $fileBackupPath,
        ]);
    }

    /**
     * 完成日志
     */
    protected function finishLog(int $status, string $message): void
    {
        if ($this->logId <= 0) {
            return;
        }
        UpgradeLog::where('id', $this->logId)->update([
            'status' => $status,
            'error_message' => $status === UpgradeLog::STATUS_SUCCESS ? '' : $message,
            'upgrade_steps' => json_encode($this->steps, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * 递归删除目录
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $path = $file->getRealPath();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
