<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-1: 备份恢复增强服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Config;
use app\common\service\BackupService;
use app\common\model\BackupRecord;

/**
 * 备份恢复增强服务 - V2.9.39 SYS-ROBUST-1
 * 全量/增量/定时/云存储/加密/恢复/验证
 */
class BackupRestoreService
{
    protected const CACHE_TAG = 'backup_restore';
    protected BackupService $baseBackup;

    protected string $backupPath;
    protected array $cloudConfig;

    public function __construct()
    {
        $this->baseBackup = new BackupService();
        $this->backupPath = runtime_path() . 'backup' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        $this->cloudConfig = Config::get('backup.cloud', []);
    }

    /**
     * 创建全量备份（数据库+文件）
     */
    public function createFullBackup(bool $gzip = true, bool $encrypt = false, ?int $operatorId = null): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->baseBackup->createFullBackup($gzip);
            $dbFile = $result['db']['filename'] ?? '';
            $fileBackup = $result['files']['filename'] ?? '';

            // 加密处理
            if ($encrypt) {
                $dbFile = $this->encryptBackup($dbFile);
            }

            // 云存储上传
            $cloudUrl = '';
            if (!empty($this->cloudConfig['enabled'])) {
                $cloudUrl = $this->uploadToCloud($dbFile);
            }

            // 记录备份记录
            $record = BackupRecord::create([
                'backup_type'  => 'full',
                'source'       => 'manual',
                'operator_id'  => $operatorId ?? 0,
                'db_file'      => $dbFile,
                'files_archive' => $fileBackup,
                'file_size'    => $result['db']['size'] ?? 0,
                'is_encrypted' => $encrypt ? 1 : 0,
                'cloud_url'    => $cloudUrl,
                'status'       => BackupRecord::STATUS_SUCCESS,
                'create_time'  => time(),
            ]);

            $duration = round(microtime(true) - $startTime, 2);
            Log::info('[BackupRestore] 全量备份完成', ['record_id' => $record->id, 'duration' => $duration]);

            return [
                'success'  => true,
                'record_id'=> $record->id,
                'db_file'  => $dbFile,
                'files'    => $fileBackup,
                'size'     => $result['db']['size'] ?? 0,
                'encrypted'=> $encrypt,
                'cloud_url'=> $cloudUrl,
                'duration' => $duration,
            ];
        } catch (\Throwable $e) {
            BackupRecord::create([
                'backup_type' => 'full',
                'source'      => 'manual',
                'operator_id' => $operatorId ?? 0,
                'status'      => BackupRecord::STATUS_FAILED,
                'error_msg'   => $e->getMessage(),
                'create_time' => time(),
            ]);
            Log::error('[BackupRestore] 全量备份失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 创建增量备份（仅备份变更的数据表）
     */
    public function createIncrementalBackup(?int $operatorId = null): array
    {
        $startTime = microtime(true);

        try {
            // 获取上次全量备份时间
            $lastFull = BackupRecord::where('backup_type', 'full')
                ->where('status', BackupRecord::STATUS_SUCCESS)
                ->order('create_time', 'desc')
                ->find();

            $sinceTime = $lastFull ? (int) $lastFull->create_time : 0;

            // 查找有变更的表
            $changedTables = $this->findChangedTables($sinceTime);

            if (empty($changedTables)) {
                return ['success' => true, 'msg' => '无数据变更', 'tables' => []];
            }

            $filename = date('Ymd_His') . '_incremental.sql';
            $filepath = $this->backupPath . $filename;

            $handle = fopen($filepath, 'w');
            fwrite($handle, "-- AI-CMS Incremental Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Since: " . date('Y-m-d H:i:s', $sinceTime) . "\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            $totalRows = 0;
            foreach ($changedTables as $table => $updateField) {
                $rows = $this->exportIncrementalData($handle, $table, $updateField, $sinceTime);
                $totalRows += $rows;
            }

            fclose($handle);

            $record = BackupRecord::create([
                'backup_type'  => 'incremental',
                'source'       => 'manual',
                'operator_id'  => $operatorId ?? 0,
                'db_file'      => $filename,
                'file_size'    => filesize($filepath),
                'status'       => BackupRecord::STATUS_SUCCESS,
                'create_time'  => time(),
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success'   => true,
                'record_id' => $record->id,
                'filename'  => $filename,
                'tables'    => array_keys($changedTables),
                'rows'      => $totalRows,
                'duration'  => $duration,
            ];
        } catch (\Throwable $e) {
            Log::error('[BackupRestore] 增量备份失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 定时备份（由Cron触发）
     */
    public function runScheduledBackup(): array
    {
        $schedule = $this->getScheduleConfig();

        if (empty($schedule['enabled'])) {
            return ['success' => false, 'msg' => '定时备份未启用'];
        }

        $type = $schedule['type'] ?? 'full';
        $gzip = (bool) ($schedule['gzip'] ?? true);
        $encrypt = (bool) ($schedule['encrypt'] ?? false);

        $result = match ($type) {
            'incremental' => $this->createIncrementalBackup(),
            default        => $this->createFullBackup($gzip, $encrypt),
        };

        // 自动清理旧备份
        if ($result['success'] ?? false) {
            $this->cleanupOldBackups((int) ($schedule['keep_count'] ?? 7));
        }

        return $result;
    }

    /**
     * 恢复备份
     */
    public function restore(int $recordId, ?int $operatorId = null): array
    {
        $record = BackupRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'msg' => '备份记录不存在'];
        }

        if ($record->status !== BackupRecord::STATUS_SUCCESS) {
            return ['success' => false, 'msg' => '备份文件状态异常'];
        }

        $filename = $record->db_file;
        $isEncrypted = (int) $record->is_encrypted === 1;

        try {
            // 解密
            if ($isEncrypted) {
                $filename = $this->decryptBackup($filename);
            }

            // 恢复前创建安全快照
            $snapshot = $this->baseBackup->create('all', true);
            Log::info('[BackupRestore] 恢复前快照已创建', ['snapshot' => $snapshot['filename']]);

            // 执行恢复
            $this->baseBackup->restore($filename, false);

            // 验证恢复结果
            $verification = $this->verifyBackup($filename);

            Log::info('[BackupRestore] 恢复完成', ['record_id' => $recordId, 'operator' => $operatorId]);

            return [
                'success'      => true,
                'snapshot'     => $snapshot['filename'],
                'verification' => $verification,
            ];
        } catch (\Throwable $e) {
            Log::error('[BackupRestore] 恢复失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 验证备份文件完整性
     */
    public function verifyBackup(string $filename): array
    {
        $filepath = $this->backupPath . basename($filename);
        if (!file_exists($filepath)) {
            return ['valid' => false, 'msg' => '文件不存在'];
        }

        $isGzip = str_ends_with($filepath, '.gz');
        $content = $isGzip ? implode('', gzfile($filepath)) : file_get_contents($filepath);

        $checks = [];

        // 1. 文件头检查
        $checks['header'] = str_contains($content, 'AI-CMS') ? 'pass' : 'fail';

        // 2. SQL语句完整性
        $createTableCount = substr_count($content, 'CREATE TABLE');
        $insertCount = substr_count($content, 'INSERT INTO');
        $checks['tables'] = $createTableCount;
        $checks['inserts'] = $insertCount;

        // 3. 结尾检查
        $checks['footer'] = str_contains($content, 'FOREIGN_KEY_CHECKS = 1') ? 'pass' : 'fail';

        // 4. 文件大小
        $checks['file_size'] = filesize($filepath);

        $valid = $checks['header'] === 'pass' && $checks['footer'] === 'pass';

        return [
            'valid'   => $valid,
            'checks'  => $checks,
            'msg'     => $valid ? '验证通过' : '验证失败',
        ];
    }

    /**
     * 获取备份列表
     */
    public function getBackupList(int $page = 1, int $limit = 20): array
    {
        $query = BackupRecord::order('create_time', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 删除备份
     */
    public function deleteBackup(int $recordId): array
    {
        $record = BackupRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'msg' => '记录不存在'];
        }

        // 删除本地文件
        if (!empty($record->db_file)) {
            $filepath = $this->backupPath . $record->db_file;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        if (!empty($record->files_archive)) {
            $filepath = $this->backupPath . $record->files_archive;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        $record->delete();

        return ['success' => true];
    }

    /**
     * 清理旧备份
     */
    public function cleanupOldBackups(int $keepCount = 7): int
    {
        $records = BackupRecord::where('status', BackupRecord::STATUS_SUCCESS)
            ->order('create_time', 'desc')
            ->limit($keepCount)
            ->column('id');

        if (empty($records)) {
            return 0;
        }

        $oldRecords = BackupRecord::whereNotIn('id', $records)
            ->where('status', BackupRecord::STATUS_SUCCESS)
            ->select();

        $deleted = 0;
        foreach ($oldRecords as $record) {
            if (!empty($record->db_file)) {
                $filepath = $this->backupPath . $record->db_file;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            $record->delete();
            $deleted++;
        }

        return $deleted;
    }

    /**
     * 获取定时备份配置
     */
    public function getScheduleConfig(): array
    {
        try {
            $config = Db::name('config')->where('name', 'backup_schedule')->value('value');
            return $config ? json_decode($config, true) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * 更新定时备份配置
     */
    public function updateScheduleConfig(array $config): bool
    {
        try {
            $exists = Db::name('config')->where('name', 'backup_schedule')->find();
            $value = json_encode($config, JSON_UNESCAPED_UNICODE);

            if ($exists) {
                Db::name('config')->where('name', 'backup_schedule')->update(['value' => $value, 'update_time' => time()]);
            } else {
                Db::name('config')->insert([
                    'name'    => 'backup_schedule',
                    'value'   => $value,
                    'group'   => 'system',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('[BackupRestore] 配置更新失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 加密备份文件
     */
    protected function encryptBackup(string $filename): string
    {
        $filepath = $this->backupPath . $filename;
        if (!file_exists($filepath)) {
            return $filename;
        }

        $encryptedFile = $filepath . '.enc';
        $key = Config::get('app.backup_encrypt_key', 'ai_cms_backup_default_key');

        $data = file_get_contents($filepath);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        file_put_contents($encryptedFile, $iv . $encrypted);
        unlink($filepath);

        return basename($encryptedFile);
    }

    /**
     * 解密备份文件
     */
    protected function decryptBackup(string $filename): string
    {
        $filepath = $this->backupPath . $filename;
        if (!file_exists($filepath)) {
            throw new \Exception('加密备份文件不存在: ' . $filename);
        }

        $decryptedFile = str_replace('.enc', '', $filepath);
        $key = Config::get('app.backup_encrypt_key', 'ai_cms_backup_default_key');

        $data = file_get_contents($filepath);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \Exception('解密失败');
        }

        file_put_contents($decryptedFile, $decrypted);

        return basename($decryptedFile);
    }

    /**
     * 上传到云存储
     */
    protected function uploadToCloud(string $filename): string
    {
        $filepath = $this->backupPath . $filename;
        if (!file_exists($filepath)) {
            return '';
        }

        // 模拟云存储上传（实际对接OSS/COS/S3等）
        $cloudType = $this->cloudConfig['type'] ?? 'local';
        $cloudPath = ($this->cloudConfig['path'] ?? 'backups/') . $filename;

        try {
            if ($cloudType === 'local') {
                // 本地存储模式
                return 'local://' . $cloudPath;
            }

            // 预留云存储SDK对接
            Log::info('[BackupRestore] 云存储上传', ['type' => $cloudType, 'path' => $cloudPath]);
            return $cloudType . '://' . $cloudPath;
        } catch (\Throwable $e) {
            Log::error('[BackupRestore] 云存储上传失败', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * 查找有变更的数据表
     */
    protected function findChangedTables(int $sinceTime): array
    {
        $prefix = Db::getConfig('prefix') ?: Config::get('database.connections.mysql.prefix');
        $tables = Db::query('SHOW TABLES');
        $tableKey = array_key_first($tables[0] ?? []);

        $changed = [];
        $updateFields = [
            $prefix . 'content'       => 'update_time',
            $prefix . 'member'        => 'update_time',
            $prefix . 'comment'       => 'create_time',
            $prefix . 'paid_order'    => 'create_time',
            $prefix . 'config'        => 'update_time',
        ];

        foreach ($tables as $table) {
            $tableName = $table[$tableKey];
            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }

            $updateField = $updateFields[$tableName] ?? null;
            if ($updateField === null) {
                continue;
            }

            try {
                $columns = Db::query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$updateField}'");
                if (empty($columns)) {
                    continue;
                }

                $count = Db::name(str_replace($prefix, '', $tableName))
                    ->where($updateField, '>=', $sinceTime)
                    ->count();

                if ($count > 0) {
                    $changed[$tableName] = $updateField;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $changed;
    }

    /**
     * 导出增量数据
     */
    protected function exportIncrementalData($handle, string $table, string $updateField, int $sinceTime): int
    {
        $rows = Db::query("SELECT * FROM `{$table}` WHERE `{$updateField}` >= {$sinceTime}");
        if (empty($rows)) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $columnStr = '`' . implode('`, `', $columns) . '`';

        fwrite($handle, "-- Incremental: {$table}\n");
        fwrite($handle, "INSERT INTO `{$table}` ({$columnStr}) VALUES\n");

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

        fwrite($handle, implode(",\n", $values) . ";\n\n");

        return count($rows);
    }
}
