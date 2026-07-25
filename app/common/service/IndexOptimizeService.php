<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PERF-2: 索引优化建议服务
 */
class IndexOptimizeService
{
    /**
     * 检测缺失索引
     */
    public function detectMissingIndexes(): array
    {
        $suggestions = [];
        $prefix = config('database.connections.mysql.prefix', '');

        // 获取所有表
        $tables = Db::query("SHOW TABLES");
        $dbName = config('database.connections.mysql.database');

        foreach ($tables as $tableRow) {
            $tableName = array_values($tableRow)[0];
            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }

            // 获取表索引
            $indexes = Db::query("SHOW INDEX FROM `{$tableName}`");
            $indexedColumns = [];
            foreach ($indexes as $idx) {
                $indexedColumns[$idx['Column_name']] = true;
            }

            // 获取表结构
            $columns = Db::query("SHOW COLUMNS FROM `{$tableName}`");
            foreach ($columns as $col) {
                $colName = $col['Field'];
                // 检查常见需要索引的字段
                if (in_array($colName, ['user_id', 'status', 'created_at', 'type', 'category_id', 'parent_id'], true)) {
                    if (!isset($indexedColumns[$colName])) {
                        $suggestions[] = [
                            'table'      => $tableName,
                            'column'     => $colName,
                            'suggestion' => "建议为 `{$tableName}`.{$colName} 添加索引",
                            'sql'        => "ALTER TABLE `{$tableName}` ADD INDEX `idx_{$colName}` (`{$colName}`);",
                        ];
                    }
                }
            }
        }

        return $suggestions;
    }

    /**
     * 检测冗余索引
     */
    public function detectRedundantIndexes(): array
    {
        $redundant = [];
        $prefix = config('database.connections.mysql.prefix', '');

        $tables = Db::query("SHOW TABLES");
        foreach ($tables as $tableRow) {
            $tableName = array_values($tableRow)[0];
            if (!str_starts_with($tableName, $prefix)) {
                continue;
            }

            $indexes = Db::query("SHOW INDEX FROM `{$tableName}`");
            $indexGroups = [];
            foreach ($indexes as $idx) {
                $keyName = $idx['Key_name'];
                if (!isset($indexGroups[$keyName])) {
                    $indexGroups[$keyName] = [];
                }
                $indexGroups[$keyName][] = $idx['Column_name'];
            }

            // 检查重复索引（相同列组合）
            $seen = [];
            foreach ($indexGroups as $keyName => $columns) {
                $sig = implode(',', $columns);
                if (isset($seen[$sig])) {
                    $redundant[] = [
                        'table'      => $tableName,
                        'index1'     => $seen[$sig],
                        'index2'     => $keyName,
                        'columns'    => $sig,
                        'suggestion' => "索引 `{$keyName}` 与 `{$seen[$sig]}` 重复，建议删除其中一个",
                    ];
                } else {
                    $seen[$sig] = $keyName;
                }
            }
        }

        return $redundant;
    }
}
