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
 * 推送重试队列模型 - V2.9.19 D-1
 */
class PushRetry extends Model
{
    protected $name = 'push_retry';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'push_id'       => 'integer',
        'status'        => 'integer',
        'retry_count'   => 'integer',
        'next_retry_at' => 'integer',
    ];

    /** 状态：待重试 */
    const STATUS_PENDING = 0;
    /** 状态：成功 */
    const STATUS_SUCCESS = 1;
    /** 状态：失败 */
    const STATUS_FAILED = -1;
}
