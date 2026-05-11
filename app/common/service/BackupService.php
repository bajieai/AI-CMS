<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Db;
use app\common\model\BackupLog;

/**
 * 数据库备份服务 - V2.9.3 增强版
 * 修复OOM、支持文件备份、gzip压缩、恢复安全保护
 */
class BackupService
{
    /**
     * 备份目录
     */
    protected string $backupPath;

    /**
     * 每次读取的行数（防止OOM）
     */
    protected int $chunkSize = 1000;

    /**
     * 文件备份包含的目录
     */
    protected array $fileBackupDirs = [];

    public function __construct()
    {
        $this->backupPath = runtime_path() . 'backup' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        // 默认备份上传目录
        $this->fileBackupDirs = [
            public_path() . 'uploads',
            public_path() . 'storage',
        ];
        // V2.9.4: 可配置额外备份目录
        $extraDirs = Config::get('backup_extra_dirs', '');
        if (!empty($extraDirs)) {
            foreach (explode(',', $extraDirs) as $dir) {
                $dir = trim($dir);
                if (is_dir($dir)) {
                    $this->fileBackupDirs[] = $dir;
                }
            }
        }
    }

    /**
     * 获取备份列表
     */
    public function getList(): array
    {
        $files = array_merge(
            glob($this->backupPath . '*.sql') ?: [],
            glob($this->backupPath . '*.sql.gz') ?: [],
            glob($this->backupPath . '*.zip') ?: []
        );
        $list = [];

        foreach ($files as $file) {
            $list[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_text' => $this->formatSize(filesize($file)),
                'create_time' => filemtime($file),
                'create_time_text' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => $this->detectBackupType(basename($file)),
            ];
        }

        usort($list, function ($a, $b) {
            return $b['create_time'] <=> $a['create_time'];
        });

        return $list;
    }

    /**
     * 检测备份类型
     */
    protected function detectBackupType(string $filename): string
    {
        if (str_ends_with($filename, '.zip')) {
            return 'files';
        }
        if (str_contains($filename, '_files_')) {
            return 'db+files';
        }
        return 'database';
    }

    /**
     * 创建数据库备份（流式写入，防OOM）
     * @param string $type all|structure|data
     * @param bool $gzip 是否启用gzip压缩
     */
    public function create(string $type = 'all', bool $gzip = false): array
    {
        $dbConfig = Config::get('database.default');
        $connection = Config::get('database.connections.' . $dbConfig);
        $database = $connection['database'] ?? '';
        $prefix = $connection['prefix'] ?? 'i8j_';

        $ext = $gzip ? '.sql.gz' : '.sql';
        $filename = date('Ymd_His') . '_' . $type . $ext;
        $filepath = $this->backupPath . $filename;

        $handle = $gzip ? gzopen($filepath, 'wb9') : fopen($filepath, 'w');
        if (!$handle) {
            throw new \Exception('无法创建备份文件：' . $filepath);
        }

        // 写入头部
        $header = "-- AI-CMS Database Backup\n";
        $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Type: " . $type . "\n";
        $header .= "-- Version: 2.9.3\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $this->writeStream($handle, $header, $gzip);

        $tables = Db::query('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        foreach ($tables as $table) {
            $tableName = $table[$tableKey];

            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }

            if ($type === 'all' || $type === 'structure') {
                $create = Db::query("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($create) && isset($create[0]['Create Table'])) {
                    $this->writeStream($handle, "-- Table: {$tableName}\n", $gzip);
                    $this->writeStream($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n", $gzip);
                    $this->writeStream($handle, $create[0]['Create Table'] . ";\n\n", $gzip);
                }
            }

            if ($type === 'all' || $type === 'data') {
                $this->exportTableDataChunked($handle, $tableName, $gzip);
            }
        }

        $this->writeStream($handle, "SET FOREIGN_KEY_CHECKS = 1;\n", $gzip);

        $gzip ? gzclose($handle) : fclose($handle);

        // V2.9.4: 记录备份日志
        try {
            BackupLog::create([
                'backup_type' => 'database',
                'file_name' => $filename,
                'file_size' => filesize($filepath),
                'status' => BackupLog::STATUS_SUCCESS,
            ]);
        } catch (\Throwable) {}

        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'size_text' => $this->formatSize(filesize($filepath)),
            'path' => $filepath,
            'type' => 'database',
        ];
    }

    /**
     * 分块导出表数据（防OOM核心）
     */
    protected function exportTableDataChunked($handle, string $tableName, bool $gzip): void
    {
        // 获取总行数
        $countResult = Db::query("SELECT COUNT(*) as cnt FROM `{$tableName}`");
        $total = (int) ($countResult[0]['cnt'] ?? 0);
        if ($total === 0) {
            return;
        }

        // 获取列名
        $firstRow = Db::query("SELECT * FROM `{$tableName}` LIMIT 1");
        if (empty($firstRow)) {
            return;
        }
        $columns = array_keys($firstRow[0]);
        $columnStr = "`" . implode('`, `', $columns) . "`";

        $offset = 0;
        $firstBatch = true;

        while ($offset < $total) {
            $rows = Db::query("SELECT * FROM `{$tableName}` LIMIT {$offset}, {$this->chunkSize}");
            if (empty($rows)) {
                break;
            }

            if ($firstBatch) {
                $this->writeStream($handle, "INSERT INTO `{$tableName}` ({$columnStr}) VALUES \n", $gzip);
                $firstBatch = false;
            } else {
                $this->writeStream($handle, "INSERT INTO `{$tableName}` ({$columnStr}) VALUES \n", $gzip);
            }

            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = "'" . addslashes((string) $val) . "'";
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }

            $this->writeStream($handle, implode(",\n", $values) . ";\n\n", $gzip);
            $offset += $this->chunkSize;
        }
    }

    /**
     * 流式写入
     */
    protected function writeStream($handle, string $data, bool $gzip): void
    {
        if ($gzip) {
            gzwrite($handle, $data);
        } else {
            fwrite($handle, $data);
        }
    }

    /**
     * 创建文件备份（zip压缩上传目录）
     */
    public function createFileBackup(): array
    {
        $filename = date('Ymd_His') . '_files.zip';
        $filepath = $this->backupPath . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('无法创建ZIP文件');
        }

        foreach ($this->fileBackupDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $this->addDirToZip($zip, $dir, basename($dir));
        }

        $zip->close();

        // V2.9.4: 记录备份日志
        try {
            BackupLog::create([
                'backup_type' => 'files',
                'file_name' => $filename,
                'file_size' => filesize($filepath),
                'status' => BackupLog::STATUS_SUCCESS,
            ]);
        } catch (\Throwable) {}

        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'size_text' => $this->formatSize(filesize($filepath)),
            'path' => $filepath,
            'type' => 'files',
        ];
    }

    /**
     * 递归添加目录到ZIP
     */
    protected function addDirToZip(\ZipArchive $zip, string $dir, string $basePath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $basePath . '/' . substr($filePath, strlen($dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    /**
     * 创建完整备份（数据库+文件）
     */
    public function createFullBackup(bool $gzip = true): array
    {
        // 先创建数据库备份
        $dbResult = $this->create('all', $gzip);
        // 再创建文件备份
        $fileResult = $this->createFileBackup();

        return [
            'db' => $dbResult,
            'files' => $fileResult,
            'message' => '完整备份完成：数据库+' . count($this->fileBackupDirs) . '个文件目录',
        ];
    }

    /**
     * 恢复备份（带安全保护：恢复前自动快照）
     */
    public function restore(string $filename, bool $createSnapshot = true): bool
    {
        $filepath = $this->backupPath . basename($filename);
        if (!file_exists($filepath)) {
            throw new \Exception('备份文件不存在');
        }

        $isGzip = str_ends_with($filepath, '.gz');

        // 安全保护：恢复前自动创建快照
        if ($createSnapshot) {
            $snapshot = $this->create('all', true);
            // 记录快照信息到日志
            error_log('[BACKUP_RESTORE] 恢复前自动快照: ' . $snapshot['filename']);
        }

        // 读取SQL内容
        if ($isGzip) {
            $sql = gzfile($filepath);
            $sql = implode("", $sql);
        } else {
            $sql = file_get_contents($filepath);
        }

        if (empty($sql)) {
            throw new \Exception('备份文件为空');
        }

        // 禁用外键检查
        Db::execute('SET FOREIGN_KEY_CHECKS = 0');

        // 按语句分割执行（考虑多行INSERT）
        $statements = $this->splitSqlStatements($sql);
        $success = 0;
        $failed = 0;

        foreach ($statements as $statement) {
            if (empty($statement) || str_starts_with($statement, '--') || str_starts_with($statement, '/*')) {
                continue;
            }
            try {
                Db::execute($statement);
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                error_log('[BACKUP_RESTORE] SQL执行失败: ' . $e->getMessage() . ' | SQL: ' . substr($statement, 0, 200));
            }
        }

        Db::execute('SET FOREIGN_KEY_CHECKS = 1');

        // 清理缓存
        try {
            CacheService::clearAll();
        } catch (\Throwable $e) {
            // 忽略缓存清理错误
        }

        error_log("[BACKUP_RESTORE] 恢复完成: 成功{$success}条, 失败{$failed}条");
        return true;
    }

    /**
     * 安全分割SQL语句（处理多行INSERT）
     */
    protected function splitSqlStatements(string $sql): array
    {
        $lines = explode("\n", $sql);
        $statements = [];
        $current = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }
            $current .= $line . "\n";
            if (str_ends_with($line, ';')) {
                $statements[] = trim($current);
                $current = '';
            }
        }

        if (!empty($current)) {
            $statements[] = trim($current);
        }

        return $statements;
    }

    /**
     * 删除备份
     */
    public function delete(string $filename): bool
    {
        $filepath = $this->backupPath . basename($filename);
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * 下载备份
     */
    public function download(string $filename): string
    {
        $filepath = $this->backupPath . basename($filename);
        if (!file_exists($filepath)) {
            throw new \Exception('备份文件不存在');
        }
        return $filepath;
    }

    /**
     * 清理旧备份（保留最近N个）
     */
    public function cleanup(int $keep = 10): int
    {
        $list = $this->getList();
        $deleted = 0;

        if (count($list) <= $keep) {
            return 0;
        }

        // 按时间倒序，删除旧的
        foreach (array_slice($list, $keep) as $item) {
            if ($this->delete($item['filename'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * 格式化文件大小
     */
    protected function formatSize(int $size): string
    {
        if ($size >= 1073741824) {
            return round($size / 1073741824, 2) . ' GB';
        }
        if ($size >= 1048576) {
            return round($size / 1048576, 2) . ' MB';
        }
        if ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        }
        return $size . ' B';
    }
}
