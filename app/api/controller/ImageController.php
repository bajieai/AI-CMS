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

namespace app\api\controller;

use app\common\model\ImageTask;
use think\facade\Log;

/**
 * 配图任务状态查询API - V2.9.1 M14a
 *
 * 公开接口（无需认证），供前端AJAX轮询配图进度
 */
class ImageController extends BaseController
{
    /**
     * 查询配图任务状态
     *
     * GET /api/image/status?task_id=xxx
     *
     * 响应:
     * {
     *   "code": 0,
     *   "msg": "success",
     *   "data": {
     *     "task_id": "bfl-xxx",
     *     "status": "processing",   // pending/processing/completed/failed
     *     "progress": 33,           // 0-100
     *     "image_url": "",          // 完成后返回
     *     "local_path": "",         // 本地路径(M17)
     *     "error_msg": "",
     *     "attempts": 10,
     *     "max_attempts": 30,
     *     "retry_count": 0
     *   }
     * }
     */
    public function status()
    {
        $taskId = $this->request->get('task_id', '');
        if (empty($taskId)) {
            return $this->error('缺少task_id参数');
        }

        try {
            $task = ImageTask::findByTaskId($taskId);
            if (!$task) {
                return $this->error('任务不存在', 404);
            }

            $statusText = $task->status_text;
            $progress = $task->progress;
            $result = $task->result ?? [];

            // 构造响应
            $data = [
                'task_id'      => $task->task_id,
                'status'       => $statusText,
                'progress'     => $progress,
                'image_url'    => $result['sample'] ?? '',
                'local_path'   => $task->local_path ?? '',
                'error_msg'    => $task->error_msg ?? '',
                'attempts'     => (int) $task->attempts,
                'max_attempts' => (int) $task->max_attempts,
                'retry_count'  => (int) $task->retry_count,
            ];

            // completed状态时，优先返回本地路径(M17)，否则返回远程URL
            if ($statusText === 'completed') {
                if (!empty($data['local_path'])) {
                    $data['image_url'] = \app\common\service\StorageService::getUrl($data['local_path']);
                }
            }

            return $this->success($data);

        } catch (\Exception $e) {
            Log::error('[ImageController] 查询任务状态失败: ' . $e->getMessage());
            return $this->error('查询失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量查询任务状态
     *
     * POST /api/image/batch_status
     * body: { "task_ids": ["id1", "id2"] }
     */
    public function batchStatus()
    {
        $taskIds = $this->request->post('task_ids', []);
        if (!is_array($taskIds) || empty($taskIds)) {
            return $this->error('缺少task_ids参数');
        }

        try {
            $tasks = ImageTask::whereIn('task_id', $taskIds)
                ->field('task_id,status,result,local_path,error_msg,attempts,max_attempts,retry_count')
                ->select();

            $list = [];
            foreach ($tasks as $task) {
                $result = $task->result ?? [];
                $imageUrl = $result['sample'] ?? '';
                if ($task->status == ImageTask::STATUS_COMPLETED && !empty($task->local_path)) {
                    $imageUrl = \app\common\service\StorageService::getUrl($task->local_path);
                }

                $list[] = [
                    'task_id'      => $task->task_id,
                    'status'       => $task->status_text,
                    'progress'     => $task->progress,
                    'image_url'    => $imageUrl,
                    'local_path'   => $task->local_path ?? '',
                    'error_msg'    => $task->error_msg ?? '',
                    'attempts'     => (int) $task->attempts,
                    'max_attempts' => (int) $task->max_attempts,
                    'retry_count'  => (int) $task->retry_count,
                ];
            }

            return $this->success(['list' => $list]);

        } catch (\Exception $e) {
            Log::error('[ImageController] 批量查询失败: ' . $e->getMessage());
            return $this->error('查询失败: ' . $e->getMessage(), 500);
        }
    }
}
