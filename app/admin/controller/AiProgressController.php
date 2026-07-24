<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\service\ai\AiTaskQueueService;
use app\common\model\AiTaskQueue;
use think\facade\Cache;

/**
 * AI进度控制器 - V2.9.14
 *
 * SSE端点 + 批量SEO控制
 * 继承AdminBaseController获得认证体系
 */
class AiProgressController extends AdminBaseController
{
    /** SSE最大连接时间（秒） */
    protected int $sseMaxDuration = 1800; // 30分钟

    /**
     * SSE批量SEO进度流（P0-1修正：connection_aborted + 超时保护）
     */
    public function stream(string $bizKey)
    {
        // P0-1: SSE Worker耗尽防护
        set_time_limit(0);
        ignore_user_abort(true);
        session_write_close(); // 释放session锁

        // SSE响应头
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Nginx禁用缓冲

        $cacheKey = 'batch_seo_progress_' . $bizKey;
        $startTime = time();
        $lastProgress = null;

        while (true) {
            // P0-1: 检测连接是否已断开
            if (connection_aborted()) {
                break;
            }

            // P0-1: 超时保护
            if (time() - $startTime > $this->sseMaxDuration) {
                echo "event: error\ndata: " . json_encode(['message' => '连接超时，请刷新页面重试']) . "\n\n";
                ob_flush();
                flush();
                break;
            }

            $progress = Cache::get($cacheKey);

            if (!$progress) {
                echo "event: error\ndata: " . json_encode(['message' => '任务不存在或已过期']) . "\n\n";
                ob_flush();
                flush();
                break;
            }

            // 只在进度变化时推送（减少带宽）
            if ($lastProgress !== json_encode($progress)) {
                $lastProgress = json_encode($progress);
                echo "event: progress\ndata: " . $lastProgress . "\n\n";
                ob_flush();
                flush();
            }

            // 完成/取消则退出
            if (!empty($progress['completed']) && $progress['completed'] >= $progress['total']) {
                echo "event: complete\ndata: " . json_encode($progress) . "\n\n";
                ob_flush();
                flush();
                break;
            }
            if (!empty($progress['cancelled'])) {
                echo "event: cancelled\ndata: " . json_encode($progress) . "\n\n";
                ob_flush();
                flush();
                break;
            }

            // 暂停状态：降低推送频率到5秒（P1-4）
            $sleepSec = !empty($progress['paused']) ? 5 : 2;
            sleep($sleepSec);
        }

        return '';
    }

    /**
     * 启动批量SEO处理
     */
    public function batchSeoStart()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要操作的内容');
        }

        $ids = array_slice($ids, 0, 50); // 最多50篇
        $batchId = uniqid('batch_seo_');
        $bizKey = 'batch_seo:' . $batchId;

        // 初始化Cache进度
        Cache::set("batch_seo_progress_{$bizKey}", [
            'batch_id'      => $batchId,
            'biz_key'       => $bizKey,
            'total'         => count($ids),
            'completed'     => 0,
            'success'       => 0,
            'failed'        => 0,
            'current_id'    => 0,
            'current_title' => '',
            'paused'        => false,
            'cancelled'     => false,
            'start_time'    => time(),
            'results'       => [],
        ], 3600);

        // 将每篇文章入队
        $queueService = new AiTaskQueueService();
        foreach ($ids as $index => $id) {
            $queueService->enqueue('single_seo_optimize', [
                'biz_id'  => 0,
                'biz_key' => $bizKey,
                'payload' => [
                    'content_id' => (int) $id,
                    'index'      => $index,
                    'biz_key'    => $bizKey,
                ],
                'priority' => 0,
            ]);
        }

        return $this->success('批量SEO任务已创建', [
            'batch_id' => $batchId,
            'biz_key'  => $bizKey,
            'total'    => count($ids),
        ]);
    }

    /**
     * 暂停批量SEO
     */
    public function batchSeoPause()
    {
        $bizKey = $this->request->post('biz_key', '');
        if (empty($bizKey)) {
            return $this->error('缺少业务标识');
        }

        $cacheKey = 'batch_seo_progress_' . $bizKey;
        $progress = Cache::get($cacheKey);
        if (!$progress) {
            return $this->error('任务不存在');
        }

        $progress['paused'] = true;
        Cache::set($cacheKey, $progress, 3600);

        return $this->success('已暂停', [
            'completed' => $progress['completed'] ?? 0,
            'total'     => $progress['total'] ?? 0,
        ]);
    }

    /**
     * 恢复批量SEO
     */
    public function batchSeoResume()
    {
        $bizKey = $this->request->post('biz_key', '');
        if (empty($bizKey)) {
            return $this->error('缺少业务标识');
        }

        $cacheKey = 'batch_seo_progress_' . $bizKey;
        $progress = Cache::get($cacheKey);
        if (!$progress) {
            return $this->error('任务不存在');
        }

        $progress['paused'] = false;
        Cache::set($cacheKey, $progress, 3600);

        // 将暂停的任务恢复为pending
        AiTaskQueue::where('biz_key', $bizKey)
            ->where('status', AiTaskQueue::STATUS_PAUSED)
            ->update([
                'status'      => AiTaskQueue::STATUS_PENDING,
                'update_time' => time(),
            ]);

        return $this->success('已恢复');
    }

    /**
     * 查询批量SEO状态（页面刷新后恢复入口）
     */
    public function batchSeoStatus(string $bizKey)
    {
        $cacheKey = 'batch_seo_progress_' . $bizKey;
        $progress = Cache::get($cacheKey);

        if (!$progress) {
            return $this->error('任务不存在或已过期');
        }

        // 同时查询队列表获取更精确状态
        $queueService = new AiTaskQueueService();
        $bizStatus = $queueService->getBizStatus($bizKey);

        return $this->success('查询成功', [
            'progress'    => $progress,
            'queue_status'=> $bizStatus,
        ]);
    }

    /**
     * SSE降级轮询端点（EventSource不支持时的fallback）
     */
    public function batchSeoPoll(string $bizKey)
    {
        $cacheKey = 'batch_seo_progress_' . $bizKey;
        $progress = Cache::get($cacheKey);

        if (!$progress) {
            return $this->error('任务不存在');
        }

        return $this->success('查询成功', $progress);
    }
}
