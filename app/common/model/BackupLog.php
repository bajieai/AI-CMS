<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 备份操作日志模型 - V2.9.4新增
 */
class BackupLog extends Model
{
    protected $name = 'backup_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'file_size' => 'integer',
        'status' => 'integer',
    ];

    const STATUS_RUNNING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
}
