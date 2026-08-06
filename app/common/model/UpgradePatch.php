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
 * SQL补丁执行记录模型 - V2.9.44新增
 * 用于幂等执行升级SQL，防止重复执行导致报错
 */
class UpgradePatch extends Model
{
    protected $name = 'upgrade_patch';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'executed_at';
    protected $updateTime = false;

    protected $type = [
        'status' => 'integer',
        'version' => 'string',
        'patch_file' => 'string',
        'checksum' => 'string',
    ];

    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
}
