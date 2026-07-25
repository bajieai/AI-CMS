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

namespace app\common\service;

use app\common\model\ReviewLog;
use app\common\model\ReviewRecord;
use app\common\model\ReviewWorkflow;
use think\facade\Db;

/**
 * 内容工作流审批服务 - V2.6
 * 状态机: 0待审核 -> 1审核中 -> 2已通过 -> 3已拒绝 -> 4已撤回
 */
class WorkflowService
{
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_REVIEWING = 1; // 审核中
    const STATUS_PASSED = 2;    // 已通过
    const STATUS_REJECTED = 3;  // 已拒绝
    const STATUS_WITHDRAWN = 4; // 已撤回

    /**
     * 提交内容进入审批流程
     */
    public static function submit(int $targetId, string $targetType = 'content', int $submitterId = 0, ?int $workflowId = null): array
    {
        $workflow = $workflowId ? ReviewWorkflow::find($workflowId) : self::getDefaultWorkflow($targetType);
        if (!$workflow || empty($workflow->is_enabled)) {
            // 无工作流，直接通过
            return ['success' => true, 'status' => self::STATUS_PASSED, 'msg' => '无审批流程，直接通过'];
        }

        $steps = $workflow->steps ?: [];
        if (empty($steps)) {
            return ['success' => true, 'status' => self::STATUS_PASSED, 'msg' => '流程步骤为空，直接通过'];
        }

        $record = ReviewRecord::create([
            'workflow_id' => $workflow->id,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'current_step' => 1,
            'total_steps' => count($steps),
            'status' => self::STATUS_PENDING,
            'submitter_id' => $submitterId,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return ['success' => true, 'record_id' => $record->id, 'status' => self::STATUS_PENDING, 'msg' => '已提交审核'];
    }

    /**
     * 审核处理
     */
    public static function review(int $recordId, int $reviewerId, string $action, string $comment = ''): array
    {
        $record = ReviewRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'msg' => '审核记录不存在'];
        }

        if (!in_array($record->status, [self::STATUS_PENDING, self::STATUS_REVIEWING])) {
            return ['success' => false, 'msg' => '该记录已处理完毕'];
        }

        $workflow = ReviewWorkflow::find($record->workflow_id);
        $steps = $workflow ? ($workflow->steps ?: []) : [];
        $currentStep = $record->current_step;

        Db::startTrans();
        try {
            // 记录审核日志
            ReviewLog::create([
                'record_id' => $recordId,
                'step' => $currentStep,
                'reviewer_id' => $reviewerId,
                'action' => $action,
                'comment' => $comment,
                'create_time' => time(),
            ]);

            if ($action === 'pass') {
                if ($currentStep >= $record->total_steps) {
                    // 最后一步通过
                    $record->status = self::STATUS_PASSED;
                } else {
                    $record->current_step += 1;
                    $record->status = self::STATUS_REVIEWING;
                }
            } elseif ($action === 'reject') {
                $record->status = self::STATUS_REJECTED;
            } elseif ($action === 'withdraw') {
                $record->status = self::STATUS_WITHDRAWN;
            }

            $record->update_time = time();
            $record->save();

            Db::commit();
            return ['success' => true, 'status' => $record->status, 'msg' => '审核操作成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取默认工作流
     */
    public static function getDefaultWorkflow(string $module = 'content'): ?ReviewWorkflow
    {
        return ReviewWorkflow::where('module', $module)
            ->where('is_default', 1)
            ->where('is_enabled', 1)
            ->find();
    }

    /**
     * 获取目标对象的审核状态
     */
    public static function getStatus(int $targetId, string $targetType = 'content'): array
    {
        $record = ReviewRecord::where('target_id', $targetId)
            ->where('target_type', $targetType)
            ->order('id', 'desc')
            ->find();

        if (!$record) {
            return ['has_record' => false, 'status' => null, 'status_text' => ''];
        }

        $statusMap = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_REVIEWING => '审核中',
            self::STATUS_PASSED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_WITHDRAWN => '已撤回',
        ];

        return [
            'has_record' => true,
            'record_id' => $record->id,
            'status' => $record->status,
            'status_text' => $statusMap[$record->status] ?? '未知',
            'current_step' => $record->current_step,
            'total_steps' => $record->total_steps,
        ];
    }

    /**
     * 获取待审核列表
     */
    public static function getPendingList(string $targetType = 'content', int $page = 1, int $limit = 20): array
    {
        $list = ReviewRecord::where('target_type', $targetType)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_REVIEWING])
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select();

        return $list->toArray();
    }
}
