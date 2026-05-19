<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 系统通知已读记录模型 - V2.6
 */
class MessageSystemRead extends Model
{
    protected $name = 'message_system_read';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = false;
    protected $updateTime = false;

    protected $type = [
        'message_id' => 'integer',
        'user_id' => 'integer',
        'read_time' => 'integer',
    ];
}
