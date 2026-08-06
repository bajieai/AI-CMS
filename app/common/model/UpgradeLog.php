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
 * 系统在线升级日志模型 - V2.9.44新增
 */
class UpgradeLog extends Model
{
    protected $name = 'upgrade_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'status' => 'integer',
        'from_version' => 'string',
        'to_version' => 'string',
        'backup_db_path' => 'string',
        'backup_files_path' => 'string',
        'error_message' => 'string',
        'upgrade_steps' => 'json',
    ];

    // 状态：0待执行/1成功/2失败/3已回滚
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_ROLLED_BACK = 3;
}
