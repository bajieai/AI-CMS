<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI工作流执行记录模型
 * V2.9.38 AI-PLUS-1
 */
class AiWorkflowExec extends Model
{
    protected $name = 'ai_workflow_exec';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    protected $type = [
        'target_ids' => 'json',
        'node_results' => 'json',
    ];

    // 执行状态
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';

    /**
     * 关联工作流
     */
    public function workflow()
    {
        return $this->belongsTo(AiWorkflow::class, 'workflow_id', 'id');
    }

    /**
     * 获取执行时长(可读格式)
     */
    public function getDurationText(): string
    {
        if ($this->total_duration < 1000) {
            return $this->total_duration . 'ms';
        }
        return round($this->total_duration / 1000, 1) . 's';
    }
}
