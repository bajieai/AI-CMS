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
 * 审核记录模型 - V2.6
 */
class ReviewRecord extends Model
{
    protected $name = 'review_record';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'workflow_id' => 'integer',
        'target_id' => 'integer',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'status' => 'integer',
        'submitter_id' => 'integer',
    ];
}
