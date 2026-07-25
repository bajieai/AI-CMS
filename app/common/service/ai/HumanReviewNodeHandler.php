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

namespace app\common\service\ai;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 人工审核节点处理器 — V2.9.39 AI-DEEP-3
 *
 * 工作流执行到此类节点时暂停，等待人工审核
 * 审核通过后继续执行后续节点，拒绝则中断工作流
 *
 * 审核状态：pending → approved/rejected
 */
class HumanReviewNodeHandler
{
    private const CACHE_TAG = 'workflow_review';

    /** 审核状态：待审核 */
    public const STATUS_PENDING = 'pending';
    /** 审核状态：已通过 */
    public const STATUS_APPROVED = 'approved';
    /** 审核状态：已拒绝 */
    public const STATUS_REJECTED = 'rejected';
    /** 审核状态：已超时 */
    public const STATUS_TIMEOUT = 'timeout';

    /** 默认超时时间（秒）— 24小时 */
    private const DEFAULT_TIMEOUT = 86400;

    /**
     * 执行人工审核节点
     *
     * 此方法会创建审核任务并阻塞等待，直到审核完成或超时
     * 在实际异步工作流中，此节点应返回pending状态，由外部审核回调驱动
     *
     * @param array $config 节点配置
     * @param array $targetIds 目标内容ID列表
     * @param array $context 上游节点输出上下文
     * @return array ['output' => [], 'ai_calls' => int, 'ai_cost' => float, 'status' => string]
     */
    public function execute(array $config, array $targetIds, array $context = []): array
    {
        $reviewerId = (int) ($config['reviewer_id'] ?? 0);
        $reviewRole = $config['review_role'] ?? '';
        $instruction = $config['instruction'] ?? '请审核以下内容';
        $timeout = (int) ($config['timeout'] ?? self::DEFAULT_TIMEOUT);

        // 构建审核内容
        $reviewContent = $this->buildReviewContent($context, $targetIds, $instruction);

        // 创建审核任务
        $reviewId = $this->createReviewTask([
            'reviewer_id'   => $reviewerId,
            'review_role'   => $reviewRole,
            'instruction'   => $instruction,
            'content'       => $reviewContent,
            'context'       => json_encode($context, JSON_UNESCAPED_UNICODE),
            'target_ids'    => $targetIds,
            'timeout'       => $timeout,
            'status'        => self::STATUS_PENDING,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        // 异步模式：返回pending状态，等待外部回调
        return [
            'output' => [
                'review_id'     => $reviewId,
                'status'        => self::STATUS_PENDING,
                'instruction'   => $instruction,
                'reviewer_id'   => $reviewerId,
                'review_role'   => $reviewRole,
            ],
            'ai_calls' => 0,
            'ai_cost'  => 0,
            'status'   => self::STATUS_PENDING,
            'blocking' => true,
        ];
    }

    /**
     * 审核通过
     * @param int $reviewId 审核任务ID
     * @param int $reviewerId 审核人ID
     * @param string $comment 审核意见
     * @return bool
     */
    public function approve(int $reviewId, int $reviewerId, string $comment = ''): bool
    {
        $result = $this->updateReviewStatus($reviewId, self::STATUS_APPROVED, $reviewerId, $comment);

        if ($result) {
            // 通知工作流继续执行
            Cache::set('review_result_' . $reviewId, [
                'status'   => self::STATUS_APPROVED,
                'reviewer' => $reviewerId,
                'comment'  => $comment,
            ], 3600);
        }

        return $result;
    }

    /**
     * 审核拒绝
     * @param int $reviewId 审核任务ID
     * @param int $reviewerId 审核人ID
     * @param string $reason 拒绝原因
     * @return bool
     */
    public function reject(int $reviewId, int $reviewerId, string $reason = ''): bool
    {
        $result = $this->updateReviewStatus($reviewId, self::STATUS_REJECTED, $reviewerId, $reason);

        if ($result) {
            Cache::set('review_result_' . $reviewId, [
                'status'   => self::STATUS_REJECTED,
                'reviewer' => $reviewerId,
                'comment'  => $reason,
            ], 3600);
        }

        return $result;
    }

    /**
     * 检查审核状态
     * @param int $reviewId 审核任务ID
     * @return array|null
     */
    public function checkStatus(int $reviewId): ?array
    {
        $cached = Cache::get('review_result_' . $reviewId);
        if ($cached) {
            return $cached;
        }

        $task = Db::name('ai_workflow_review')->where('id', $reviewId)->find();
        if (!$task) {
            return null;
        }

        // 检查超时
        if ($task['status'] === self::STATUS_PENDING) {
            $createdAt = strtotime($task['created_at']);
            $timeout = (int) ($task['timeout'] ?? self::DEFAULT_TIMEOUT);
            if (time() - $createdAt > $timeout) {
                $this->updateReviewStatus($reviewId, self::STATUS_TIMEOUT, 0, '审核超时自动关闭');
                $task['status'] = self::STATUS_TIMEOUT;
            }
        }

        return [
            'status'    => $task['status'],
            'reviewer'  => $task['reviewer_id'] ?? 0,
            'comment'   => $task['comment'] ?? '',
        ];
    }

    /**
     * 获取待审核任务列表
     * @param int $reviewerId 审核人ID（0=全部）
     * @param string $reviewRole 审核角色
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getPendingReviews(int $reviewerId = 0, string $reviewRole = '', int $page = 1, int $limit = 20): array
    {
        $query = Db::name('ai_workflow_review')->where('status', self::STATUS_PENDING);

        if ($reviewerId > 0) {
            $query->where('reviewer_id', $reviewerId);
        }
        if (!empty($reviewRole)) {
            $query->where('review_role', $reviewRole);
        }

        $total = $query->count();
        $list = $query->order('id', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 创建审核任务
     * @param array $data 审核数据
     * @return int 审核任务ID
     */
    private function createReviewTask(array $data): int
    {
        try {
            return (int) Db::name('ai_workflow_review')->insertGetId($data);
        } catch (\Throwable $e) {
            Log::error("Create review task failed: " . $e->getMessage());
            // 表不存在时降级处理，返回临时ID
            return 0;
        }
    }

    /**
     * 更新审核状态
     * @param int $reviewId 审核任务ID
     * @param string $status 状态
     * @param int $reviewerId 审核人ID
     * @param string $comment 审核意见
     * @return bool
     */
    private function updateReviewStatus(int $reviewId, string $status, int $reviewerId, string $comment): bool
    {
        try {
            $result = Db::name('ai_workflow_review')
                ->where('id', $reviewId)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status'      => $status,
                    'reviewer_id' => $reviewerId,
                    'comment'     => $comment,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ]);
            return (bool) $result;
        } catch (\Throwable $e) {
            Log::error("Update review status failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 构建审核内容
     * @param array $context 上下文
     * @param array $targetIds 目标ID
     * @param string $instruction 审核说明
     * @return string
     */
    private function buildReviewContent(array $context, array $targetIds, string $instruction): string
    {
        $parts = [$instruction];

        if (!empty($targetIds)) {
            $parts[] = "关联内容ID: " . implode(', ', $targetIds);
        }

        foreach ($context as $nodeId => $data) {
            $parts[] = "节点 {$nodeId} 输出: " . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : (string) $data);
        }

        return implode("\n\n", $parts);
    }

    /**
     * 获取节点配置schema
     * @return array
     */
    public static function getConfigSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'reviewer_id',
                    'label' => '指定审核人ID',
                    'type' => 'number',
                    'required' => false,
                    'description' => '指定具体审核人，留空则由review_role匹配',
                ],
                [
                    'name' => 'review_role',
                    'label' => '审核角色',
                    'type' => 'text',
                    'required' => false,
                    'description' => '如 editor/admin，匹配该角色的用户可审核',
                ],
                [
                    'name' => 'instruction',
                    'label' => '审核说明',
                    'type' => 'textarea',
                    'required' => true,
                    'default' => '请审核以下内容',
                ],
                [
                    'name' => 'timeout',
                    'label' => '超时时间（秒）',
                    'type' => 'number',
                    'default' => self::DEFAULT_TIMEOUT,
                ],
            ],
        ];
    }
}
