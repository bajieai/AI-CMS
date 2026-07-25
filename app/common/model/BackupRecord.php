<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.27 V-5: 备份记录模型（定时备份日志）
 * 表名: i8j_backup_record
 */
class BackupRecord extends Model
{
    protected $name = 'backup_record';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 备份类型
    const TYPE_AUTO = 'auto';
    const TYPE_MANUAL = 'manual';

    // 状态
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    protected $type = [
        'status' => 'integer',
        'file_size' => 'integer',
        'create_time' => 'integer',
    ];
}