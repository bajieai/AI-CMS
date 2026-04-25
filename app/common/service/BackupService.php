<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Db;

/**
 * 数据库备份服务
 */
class BackupService
{
    /**
     * 备份目录
     */
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = runtime_path() . 'backup' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * 获取备份列表
     */
    public function getList(): array
    {
        $files = glob($this->backupPath . '*.sql');
        $list = [];

        foreach ($files as $file) {
            $list[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_text' => $this->formatSize(filesize($file)),
                'create_time' => filemtime($file),
                'create_time_text' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // 按时间倒序
        usort($list, function ($a, $b) {
            return $b['create_time'] <=> $a['create_time'];
        });

        return $list;
    }

    /**
     * 创建备份
     * @param string $type all|structure|data
     */
    public function create(string $type = 'all'): array
    {
        $dbConfig = Config::get('database.default');
        $connection = Config::get('database.connections.' . $dbConfig);
        $database = $connection['database'] ?? '';
        $prefix = $connection['prefix'] ?? 'i8j_';

        $filename = date('Ymd_His') . '_' . $type . '.sql';
        $filepath = $this->backupPath . $filename;

        $tables = Db::query('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        $sql = "-- AI-CMS Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Type: " . $type . "\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table[$tableKey];

            // 只备份带前缀的表
            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }

            if ($type === 'all' || $type === 'structure') {
                $create = Db::query("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($create) && isset($create[0]['Create Table'])) {
                    $sql .= "-- Table: {$tableName}\n";
                    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $sql .= $create[0]['Create Table'] . ";\n\n";
                }
            }

            if ($type === 'all' || $type === 'data') {
                $rows = Db::table($tableName)->select()->toArray();
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES \n";
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
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        file_put_contents($filepath, $sql);

        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'size_text' => $this->formatSize(filesize($filepath)),
            'path' => $filepath,
        ];
    }

    /**
     * 恢复备份
     */
    public function restore(string $filename): bool
    {
        $filepath = $this->backupPath . basename($filename);
        if (!file_exists($filepath)) {
            throw new \Exception('备份文件不存在');
        }

        $sql = file_get_contents($filepath);
        if (empty($sql)) {
            throw new \Exception('备份文件为空');
        }

        // 按分号分割SQL语句并执行
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if (empty($statement) || str_starts_with($statement, '--') || str_starts_with($statement, '/*')) {
                continue;
            }
            try {
                Db::execute($statement);
            } catch (\Throwable $e) {
                error_log('[BACKUP_RESTORE] SQL执行失败: ' . $e->getMessage() . ' | SQL: ' . substr($statement, 0, 200));
                // 继续执行后续语句
            }
        }

        return true;
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
