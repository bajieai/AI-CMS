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
 * AI批量生成任务模型 - V2.5新增
 */
class AiBatchTask extends Model
{
    protected $name = 'ai_batch_task';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id' => 'integer',
        'template_id' => 'integer',
        'total' => 'integer',
        'completed' => 'integer',
        'status' => 'integer',
        'cate_id' => 'integer',
        'model_id' => 'integer',
    ];
}
