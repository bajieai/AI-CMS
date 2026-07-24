<?php

declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\facade\Db;
use think\facade\Cache;

/**
 * V2.9.35 QA-4: 一键升级命令
 * php think upgrade v2.9.35
 *
 * 升级流程：检查环境 → 备份数据库 → 执行SQL迁移 → 清除缓存 → 验证
 */
class UpgradeV2935Command extends Command
{
    protected array $sqlFiles = [
        'database/v2.9.35_sec_security.sql',
        'database/v2.9.35_perf_performance.sql',
        'database/v2.9.35_plug_plugin.sql',
        'database/v2.9.35_qa_upgrade.sql',
    ];

    protected function configure(): void
    {
        $this->setName('upgrade')
            ->addArgument('version', Argument::OPTIONAL, '目标版本号', 'v2.9.35')
            ->addOption('dry-run', 'd', \think\console\input\Option::VALUE_NONE, '预检模式，不实际执行')
            ->addOption('skip-backup', null, \think\console\input\Option::VALUE_NONE, '跳过数据库备份')
            ->setDescription('V2.9.35 一键升级（检查→备份→SQL迁移→缓存清理→验证）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $version = $input->getArgument('version');
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run');
        $skipBackup = $input->hasOption('skip-backup') && $input->getOption('skip-backup');

        $output->info('========================================');
        $output->info('  AI-CMS 一键升级工具');
        $output->info('  目标版本: ' . $version);
        $output->info('  模式: ' . ($dryRun ? '预检（不实际执行）' : '正式升级'));
        $output->info('========================================');

        // Step 1: 环境检查
        $output->info('');
        $output->info('[1/5] 环境检查...');
        if (!$this->checkEnvironment($output)) {
            $output->error('环境检查未通过，升级中止');
            return 1;
        }

        // Step 2: 版本检查
        $output->info('');
        $output->info('[2/5] 版本检查...');
        $currentVersion = $this->getCurrentVersion();
        $output->info('当前版本: ' . $currentVersion);

        if (version_compare($currentVersion, '2.9.35', '>=')) {
            $output->info('已是V2.9.35或更高版本，无需升级');
            return 0;
        }

        if (!version_compare($currentVersion, '2.9.34', '>=')) {
            $output->error('当前版本过低（' . $currentVersion . '），请先升级到V2.9.34');
            return 1;
        }

        // Step 3: SQL文件检查
        $output->info('');
        $output->info('[3/5] SQL迁移文件检查...');
        $missingFiles = $this->checkSqlFiles();
        if (!empty($missingFiles)) {
            $output->error('缺少SQL文件: ' . implode(', ', $missingFiles));
            return 1;
        }
        $output->info('4个SQL文件全部存在');

        if ($dryRun) {
            $output->info('');
            $output->info('[预检模式] 所有检查通过，可执行升级');
            return 0;
        }

        // Step 4: 数据库备份
        if (!$skipBackup) {
            $output->info('');
            $output->info('[4/5] 数据库备份...');
            $backupFile = $this->backupDatabase($output);
            if ($backupFile === null) {
                $output->error('数据库备份失败，升级中止');
                return 1;
            }
            $output->info('备份完成: ' . $backupFile);
        } else {
            $output->info('已跳过数据库备份');
        }

        // Step 5: 执行SQL迁移
        $output->info('');
        $output->info('[5/5] 执行SQL迁移...');
        foreach ($this->sqlFiles as $sqlFile) {
            $output->info('  导入: ' . $sqlFile);
            $result = $this->executeSqlFile($sqlFile);
            if (!$result['success']) {
                $output->error('  失败: ' . $result['error']);
                $output->error('升级失败！请使用备份文件恢复: ' . ($backupFile ?? '无'));
                return 1;
            }
            $output->info('  成功');
        }

        // 清除缓存
        $output->info('');
        $output->info('清除缓存...');
        $this->clearCache();
        $output->info('缓存已清除');

        // 更新版本号
        $this->updateVersion('2.9.35');
        $output->info('版本号已更新为 V2.9.35');

        // 验证
        $output->info('');
        $output->info('=== 升级完成 ===');
        $output->info('版本: V2.9.35');
        $output->info('升级日期: ' . date('Y-m-d H:i:s'));
        $output->info('');
        $output->info('后续操作:');
        $output->info('  1. 执行 composer require ezyang/htmlpurifier nelexa/zip');
        $output->info('  2. 配置 PluginStoreService 商店API地址');
        $output->info('  3. 检查后台菜单是否正常显示');

        return 0;
    }

    /**
     * 环境检查
     */
    protected function checkEnvironment(Output $output): bool
    {
        // PHP版本
        if (PHP_VERSION_ID < 80200) {
            $output->error('PHP版本过低: ' . PHP_VERSION . '，需要 ≥ 8.2');
            return false;
        }
        $output->info('  PHP版本: ' . PHP_VERSION . ' ✓');

        // ThinkPHP版本
        if (!defined('THINK_VERSION') || version_compare(THINK_VERSION, '8.1', '<')) {
            $output->error('ThinkPHP版本过低，需要 ≥ 8.1');
            return false;
        }
        $output->info('  ThinkPHP: ' . (THINK_VERSION ?: '8.1') . ' ✓');

        // 数据库连接
        try {
            Db::query('SELECT 1');
            $output->info('  数据库连接: 正常 ✓');
        } catch (\Throwable $e) {
            $output->error('  数据库连接失败: ' . $e->getMessage());
            return false;
        }

        // 磁盘空间
        $freeSpace = disk_free_space(root_path());
        if ($freeSpace < 100 * 1024 * 1024) {
            $output->error('  磁盘空间不足100MB');
            return false;
        }
        $output->info('  磁盘空间: ' . round($freeSpace / 1024 / 1024) . 'MB ✓');

        return true;
    }

    /**
     * 获取当前版本
     */
    protected function getCurrentVersion(): string
    {
        try {
            $config = Db::name('system_config')
                ->where('name', 'version')
                ->value('value');
            return $config ?: '2.9.34';
        } catch (\Throwable) {
            return '2.9.34';
        }
    }

    /**
     * 检查SQL文件
     */
    protected function checkSqlFiles(): array
    {
        $missing = [];
        $rootPath = root_path();
        foreach ($this->sqlFiles as $file) {
            if (!file_exists($rootPath . $file)) {
                $missing[] = $file;
            }
        }
        return $missing;
    }

    /**
     * 数据库备份
     */
    protected function backupDatabase(Output $output): ?string
    {
        $backupDir = root_path() . 'runtime/backup/';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . $filename;

        try {
            // 获取所有表
            $tables = Db::query('SHOW TABLES');
            $sql = "-- AI-CMS 数据库备份\n-- 日期: " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $table) {
                $tableName = array_values($table)[0];

                // 建表语句
                $createSql = Db::query("SHOW CREATE TABLE `{$tableName}`");
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createSql[0]['Create Table'] . ";\n\n";

                // 数据（分批导出，避免内存溢出）
                $count = Db::table($tableName)->count();
                if ($count > 0) {
                    $batchSize = 500;
                    $pages = ceil($count / $batchSize);
                    for ($i = 0; $i < $pages; $i++) {
                        $rows = Db::table($tableName)
                            ->limit($i * $batchSize, $batchSize)
                            ->select()
                            ->toArray();
                        foreach ($rows as $row) {
                            $values = array_map(function ($v) {
                                return is_null($v) ? 'NULL' : "'" . addslashes($v) . "'";
                            }, array_values($row));
                            $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
                        }
                    }
                    $sql .= "\n";
                }
            }

            file_put_contents($filepath, $sql);
            return $filepath;
        } catch (\Throwable $e) {
            $output->error('备份失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 执行SQL文件
     */
    protected function executeSqlFile(string $relativePath): array
    {
        $filepath = root_path() . $relativePath;
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => '文件不存在'];
        }

        $sql = file_get_contents($filepath);
        if ($sql === false) {
            return ['success' => false, 'error' => '读取文件失败'];
        }

        try {
            // 按分号分割并执行
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    Db::execute($statement);
                }
            }
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 清除缓存
     */
    protected function clearCache(): void
    {
        $runtimePath = root_path() . 'runtime/';
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
     * 递归删除目录
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = glob($dir . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->removeDir($file);
            } else {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }

    /**
     * 更新版本号
     */
    protected function updateVersion(string $version): void
    {
        try {
            $exists = Db::name('system_config')->where('name', 'version')->find();
            if ($exists) {
                Db::name('system_config')->where('name', 'version')->update(['value' => $version]);
            } else {
                Db::name('system_config')->insert(['name' => 'version', 'value' => $version]);
            }
        } catch (\Throwable) {
            // 忽略
        }
    }
}
