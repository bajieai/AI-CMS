<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 审核工作流定义模型 - V2.6
 */
class ReviewWorkflow extends Model
{
    protected $name = 'review_workflow';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_default' => 'integer',
        'is_enabled' => 'integer',
    ];

    protected function getStepsAttr($value): array
    {
        return json_decode($value ?: '[]', true);
    }

    protected function setStepsAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
