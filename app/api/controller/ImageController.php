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
 * 配图任务状态查询API
 * @api_group 配图任务
 * @api_desc AI配图异步任务状态查询，供前端轮询进度
 */
class ImageController extends BaseController
{
    /**
     * 查询配图任务状态
     * @api 查询配图任务状态
     * @api_desc 查询单个配图任务的状态和进度（pending/processing/completed/failed）
     * @param string $task_id 任务ID
     * @return json 返回任务状态、进度(0-100)、图片URL、错误信息等
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
     * @api 批量查询配图任务
     * @api_desc 批量查询多个配图任务的状态和进度
     * @param array $task_ids 任务ID数组
     * @return json 返回任务状态列表
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
