<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI主题生成记录模型 - V3.0 Phase 2
 * 对应表: i8j_ai_theme_record
 *
 * 状态机:
 *   0: GENERATING     → 生成中
 *   1: PENDING_REVIEW → 待审核（生成+校验完成）
 *   2: VALIDATED      → 校验通过
 *   3: PUBLISHED      → 已发布
 *   4: REJECTED       → 已拒绝
 *  -1: GENERATE_FAILED→ 生成失败
 *  -2: VALIDATE_FAILED→ 校验失败
 */
class AiThemeRecord extends Model
{
    protected $name = 'ai_theme_record';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'status'         => 'integer',
        'user_id'        => 'integer',
        'version'        => 'integer',
        'token_cost'     => 'integer',
        'cost'           => 'float',
        'retry_count'    => 'integer',
        'options'        => 'json',
        'validate_result'=> 'json',
        'files_tree'     => 'json',
        'prompt_log'     => 'string',
        'batch_id'       => 'string',
        'quality_score'  => 'integer',
        'quality_detail' => 'json',
    ];

    // 状态常量
    public const STATUS_GENERATING      = 0;
    public const STATUS_PENDING_REVIEW  = 1;
    public const STATUS_VALIDATED       = 2;
    public const STATUS_PUBLISHED       = 3;
    public const STATUS_REJECTED        = 4;
    public const STATUS_GENERATE_FAILED = -1;
    public const STATUS_VALIDATE_FAILED = -2;

    // 状态文本映射
    protected static array $statusMap = [
        self::STATUS_GENERATING      => '生成中',
        self::STATUS_PENDING_REVIEW  => '待审核',
        self::STATUS_VALIDATED       => '校验通过',
        self::STATUS_PUBLISHED       => '已发布',
        self::STATUS_REJECTED        => '已拒绝',
        self::STATUS_GENERATE_FAILED => '生成失败',
        self::STATUS_VALIDATE_FAILED => '校验失败',
    ];

    // 状态样式映射（后台展示用）
    protected static array $statusStyleMap = [
        self::STATUS_GENERATING      => 'info',
        self::STATUS_PENDING_REVIEW  => 'warning',
        self::STATUS_VALIDATED       => 'primary',
        self::STATUS_PUBLISHED       => 'success',
        self::STATUS_REJECTED        => 'danger',
        self::STATUS_GENERATE_FAILED => 'dark',
        self::STATUS_VALIDATE_FAILED => 'secondary',
    ];

    // 有效状态转换映射
    protected static array $validTransitions = [
        self::STATUS_GENERATING      => [self::STATUS_PENDING_REVIEW, self::STATUS_GENERATE_FAILED],
        self::STATUS_PENDING_REVIEW  => [self::STATUS_VALIDATED, self::STATUS_REJECTED, self::STATUS_GENERATING],
        self::STATUS_VALIDATED       => [self::STATUS_PUBLISHED, self::STATUS_REJECTED],
        self::STATUS_PUBLISHED       => [],
        self::STATUS_REJECTED        => [self::STATUS_GENERATING],
        self::STATUS_GENERATE_FAILED => [self::STATUS_GENERATING],
        self::STATUS_VALIDATE_FAILED => [self::STATUS_GENERATING, self::STATUS_REJECTED],
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        return self::$statusMap[$data['status'] ?? 0] ?? '未知';
    }

    /**
     * 获取状态样式标签
     */
    public function getStatusStyleAttr($value, $data): string
    {
        return self::$statusStyleMap[$data['status'] ?? 0] ?? 'default';
    }

    /**
     * 检查状态是否可以转换到目标状态
     */
    public static function canTransition(int $fromStatus, int $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return true;
        }
        $allowed = self::$validTransitions[$fromStatus] ?? [];
        return in_array($toStatus, $allowed, true);
    }

    /**
     * 安全地转换状态
     * @throws \RuntimeException
     */
    public function transitionTo(int $toStatus): static
    {
        $fromStatus = (int) $this->getAttr('status');
        if (!self::canTransition($fromStatus, $toStatus)) {
            $fromText = self::$statusMap[$fromStatus] ?? '未知';
            $toText = self::$statusMap[$toStatus] ?? '未知';
            throw new \RuntimeException("非法状态转换: {$fromText}({$fromStatus}) → {$toText}({$toStatus})");
        }
        $this->setAttr('status', $toStatus);
        return $this;
    }

    /**
     * 获取所有生成中的记录
     */
    public static function getGeneratingRecords(int $limit = 20): array
    {
        return self::where('status', self::STATUS_GENERATING)
            ->order('created_at', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取今日已生成数量（用于上限控制）
     */
    public static function getTodayCount(): int
    {
        return self::where('status', '>=', self::STATUS_PENDING_REVIEW)
            ->whereTime('created_at', 'today')
            ->count();
    }

    /**
     * 标记生成成功，进入待审核状态
     */
    public static function markPendingReview(int $id, array $filesTree, ?int $tokenCost = null, ?float $cost = null): void
    {
        $update = [
            'status'      => self::STATUS_PENDING_REVIEW,
            'files_tree'  => $filesTree,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        if ($tokenCost !== null) {
            $update['token_cost'] = $tokenCost;
        }
        if ($cost !== null) {
            $update['cost'] = $cost;
        }
        self::where('id', $id)->update($update);
    }

    /**
     * 标记生成失败
     */
    public static function markFailed(int $id, string $errorMsg): void
    {
        self::where('id', $id)->update([
            'status'     => self::STATUS_GENERATE_FAILED,
            'error_msg'  => $errorMsg,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 标记校验失败
     */
    public static function markValidateFailed(int $id, array $validateResult): void
    {
        self::where('id', $id)->update([
            'status'          => self::STATUS_VALIDATE_FAILED,
            'validate_result' => $validateResult,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 标记校验通过
     */
    public static function markValidated(int $id, array $validateResult): void
    {
        self::where('id', $id)->update([
            'status'          => self::STATUS_VALIDATED,
            'validate_result' => $validateResult,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 标记已发布
     */
    public static function markPublished(int $id): void
    {
        self::where('id', $id)->update([
            'status'     => self::STATUS_PUBLISHED,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 标记已拒绝
     */
    public static function markRejected(int $id): void
    {
        self::where('id', $id)->update([
            'status'     => self::STATUS_REJECTED,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 递增重试次数
     */
    public static function incrementRetry(int $id): void
    {
        self::where('id', $id)->inc('retry_count', 1)->update();
    }

    /**
     * 获取当前版本号（用于增量修改）
     */
    public function getCurrentVersion(): int
    {
        return (int) ($this->getAttr('version') ?? 0);
    }

    /**
     * 递增版本号
     */
    public function bumpVersion(): static
    {
        $this->setAttr('version', $this->getCurrentVersion() + 1);
        return $this;
    }

    /**
     * 获取关联的对话日志
     */
    public function chatLogs(): \think\model\relation\HasMany
    {
        return $this->hasMany(AiThemeChatLog::class, 'record_id', 'id');
    }

    /**
     * 获取对话日志列表
     */
    public function getChatLogs(?int $version = null): array
    {
        return AiThemeChatLog::getLogsByRecord((int) $this->id, $version);
    }

    /**
     * 检查是否允许增量修改
     * 允许状态: PENDING_REVIEW / VALIDATED / PUBLISHED / REJECTED
     */
    public function canModify(): bool
    {
        $status = (int) $this->getAttr('status');
        return in_array($status, [
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_PUBLISHED,
            self::STATUS_REJECTED,
        ], true);
    }
}
