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
 * AI调用日志模型
 */
class AiLog extends Model
{
    protected $name = 'ai_log';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'model_id'       => 'integer',
        'prompt_length'  => 'integer',
        'response_length' => 'integer',
        'tokens_used'    => 'integer',
        'duration_ms'    => 'integer',
        'status'         => 'integer',
    ];
}
