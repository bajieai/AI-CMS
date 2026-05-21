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
