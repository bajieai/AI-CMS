<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI任务模型
 */
class AiTask extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_ai_tasks';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = false;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 时间戳格式
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 类型转换
     */
    protected $type = [
        'user_id' => 'integer',
        'status' => 'string',
        'priority' => 'integer',
        'retry' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 类型常量
     */
    const TYPE_GENERATE = 'generate';
    const TYPE_OPTIMIZE = 'optimize';
    const TYPE_GEO_CHECK = 'geo_check';
    const TYPE_SUMMARY = 'summary';
    const TYPE_TAG = 'tag';

    /**
     * 状态文本映射
     */
    const STATUS_TEXT = [
        self::STATUS_PENDING => '等待中',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_COMPLETED => '已完成',
        self::STATUS_FAILED => '失败',
        self::STATUS_CANCELLED => '已取消',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return self::STATUS_TEXT[$this->status] ?? '未知';
    }

    /**
     * 获取类型文本
     */
    public function getTypeText(): string
    {
        $types = [
            self::TYPE_GENERATE => '内容生成',
            self::TYPE_OPTIMIZE => '内容优化',
            self::TYPE_GEO_CHECK => '地理核查',
            self::TYPE_SUMMARY => '摘要生成',
            self::TYPE_TAG => '标签生成',
        ];
        return $types[$this->type] ?? '未知';
    }

    /**
     * 获取参数
     */
    public function getParams(): array
    {
        return json_decode($this->params, true) ?? [];
    }

    /**
     * 设置参数
     */
    public function setParams(array $params): void
    {
        $this->params = json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取结果
     */
    public function getResult(): array
    {
        return json_decode($this->result, true) ?? [];
    }

    /**
     * 设置结果
     */
    public function setResult(array $result): void
    {
        $this->result = json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 标记为处理中
     */
    public function markProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_time = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 标记为完成
     */
    public function markCompleted(array $result): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->result = json_encode($result, JSON_UNESCAPED_UNICODE);
        $this->completed_time = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 标记为失败
     */
    public function markFailed(string $error): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->error = $error;
        $this->retry = $this->retry + 1;
        $this->completed_time = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 取消任务
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->completed_time = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 根据TaskId查找
     */
    public static function findByTaskId(string $taskId): ?AiTask
    {
        return self::where('task_id', '=', $taskId)->find();
    }

    /**
     * 获取用户待处理任务
     */
    public static function getPendingTasks(int $userId, int $limit = 10): array
    {
        return self::where('user_id', '=', $userId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING])
            ->order('priority', 'desc')
            ->order('created_at', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取任务详情
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'type' => $this->type,
            'type_text' => $this->getTypeText(),
            'user_id' => $this->user_id,
            'params' => $this->getParams(),
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'priority' => $this->priority,
            'retry' => $this->retry,
            'error' => $this->error,
            'result' => $this->getResult(),
            'created_at' => $this->created_at,
            'started_time' => $this->started_time,
            'completed_time' => $this->completed_time,
        ];
    }
}
