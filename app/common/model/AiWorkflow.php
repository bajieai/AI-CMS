<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\db\exception\DbException;

/**
 * AI工作流定义模型
 * V2.9.38 AI-PLUS-1
 */
class AiWorkflow extends Model
{
    protected $name = 'ai_workflow';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $type = [
        'workflow_definition' => 'json',
        'trigger_config' => 'json',
    ];

    // 工作流类型
    const TYPE_CONTENT_GEN = 'content_gen';
    const TYPE_TRANSLATION = 'translation';
    const TYPE_QUALITY = 'quality';
    const TYPE_RECOMMEND = 'recommend';
    const TYPE_AGENT_TEMPLATE = 'agent_template';
    const TYPE_CUSTOM = 'custom';

    // 触发类型
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_SCHEDULED = 'scheduled';
    const TRIGGER_EVENT = 'event';
    const TRIGGER_CONDITION = 'condition';

    // 状态
    const STATUS_ACTIVE = 'active';
    const STATUS_DRAFT = 'draft';
    const STATUS_ARCHIVED = 'archived';

    /**
     * 关联执行记录
     */
    public function execs()
    {
        return $this->hasMany(AiWorkflowExec::class, 'workflow_id', 'id');
    }

    /**
     * 获取成功率
     */
    public function getSuccessRate(): float
    {
        if ($this->exec_count === 0) {
            return 0.0;
        }
        return round(($this->success_count / $this->exec_count) * 100, 1);
    }
}
