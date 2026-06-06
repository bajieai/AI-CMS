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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PushChannel;
use app\common\model\PushLog;
use app\common\service\push\PushDispatchService;
use app\common\service\PushRetryService;

/**
 * 推送通道管理控制器 - V2.9.18 D-1
 */
class PushChannelController extends AdminBaseController
{
    protected PushDispatchService $pushService;

    public function __construct()
    {
        parent::__construct(app());
        $this->pushService = new PushDispatchService();
    }

    /**
     * 推送通道列表页
     */
    public function index()
    {
        return $this->view('/push_channel');
    }

    /**
     * AJAX 获取通道列表
     */
    public function list()
    {
        $channels = PushChannel::order('id', 'desc')->select();
        return $this->success('ok', ['data' => $channels->toArray()]);
    }

    /**
     * 添加推送通道
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $channel = new PushChannel();
            $channel->save([
                'name'         => $data['name'] ?? '',
                'type'         => $data['type'] ?? PushChannel::TYPE_WEBHOOK,
                'config'       => $data['config'] ?? [],
                'trigger_mode' => (int) ($data['trigger_mode'] ?? PushChannel::TRIGGER_MANUAL),
                'push_scope'   => $data['push_scope'] ?? '',
                'status'       => (int) ($data['status'] ?? PushChannel::STATUS_ENABLED),
            ]);

            return $this->success('通道添加成功');
        }

        return redirect('/admin/push/channel');
    }

    /**
     * 编辑推送通道
     */
    public function edit($id)
    {
        $channel = PushChannel::find($id);
        if (!$channel) {
            return $this->error('通道不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $channel->save([
                'name'         => $data['name'] ?? $channel->name,
                'type'         => $data['type'] ?? $channel->type,
                'config'       => $data['config'] ?? $channel->config,
                'trigger_mode' => (int) ($data['trigger_mode'] ?? $channel->trigger_mode),
                'push_scope'   => $data['push_scope'] ?? $channel->push_scope,
                'status'       => (int) ($data['status'] ?? $channel->status),
            ]);

            return $this->success('通道更新成功');
        }

        return redirect('/admin/push/channel');
    }

    /**
     * 删除推送通道
     */
    public function delete($id)
    {
        $channel = PushChannel::find($id);
        if (!$channel) {
            return $this->error('通道不存在');
        }
        $channel->delete();
        return $this->success('通道已删除');
    }

    /**
     * 测试推送通道
     */
    public function test($id)
    {
        $result = $this->pushService->testChannel((int) $id);
        if ($result['success']) {
            return $this->success('测试推送成功', $result);
        }
        return $this->error($result['error_msg'] ?? '测试推送失败', $result);
    }

    /**
     * 手动推送到所有自动通道
     */
    public function dispatch($contentId)
    {
        $result = $this->pushService->dispatchManual((int) $contentId);
        return $this->success("推送完成：{$result['success']}/{$result['total']} 成功", $result);
    }

    /**
     * 手动推送到指定通道
     */
    public function dispatchChannel()
    {
        $channelId  = (int) $this->request->post('channel_id', 0);
        $contentId  = (int) $this->request->post('content_id', 0);

        $result = $this->pushService->dispatchToChannel($channelId, $contentId);
        if ($result['success'] ?? false) {
            return $this->success('推送成功', $result);
        }
        return $this->error($result['error_msg'] ?? '推送失败', $result);
    }

    /**
     * V2.9.19 D-1d: 推送通道健康检查
     */
    public function health()
    {
        $channels = PushLog::field('channel_id, status, count(*) as total')
            ->group('channel_id, status')
            ->select()
            ->toArray();

        $channelStats = [];
        foreach ($channels as $row) {
            $cid = $row['channel_id'];
            if (!isset($channelStats[$cid])) {
                $channelStats[$cid] = ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
            }
            $channelStats[$cid]['total'] += $row['total'];
            if ((int) $row['status'] === PushLog::STATUS_SUCCESS) {
                $channelStats[$cid]['success'] += $row['total'];
            } elseif ((int) $row['status'] === PushLog::STATUS_FAILED) {
                $channelStats[$cid]['failed'] += $row['total'];
            } elseif ((int) $row['status'] === PushLog::STATUS_SKIPPED) {
                $channelStats[$cid]['skipped'] += $row['total'];
            }
        }

        $result = [];
        foreach ($channelStats as $cid => $stat) {
            if ($stat['total'] <= 0) continue; // 过滤空通道
            $rate = $stat['success'] / $stat['total'];
            $health = $rate >= 0.8 ? 'green' : ($rate >= 0.5 ? 'yellow' : 'red');
            $channel = PushChannel::find($cid);
            $result[] = [
                'channel_id'   => $cid,
                'channel_name' => $channel->name ?? '未知通道',
                'total'        => $stat['total'],
                'success'      => $stat['success'],
                'failed'       => $stat['failed'],
                'skipped'      => $stat['skipped'],
                'success_rate' => round($rate * 100, 1),
                'health'       => $health,
            ];
        }

        return $this->success('ok', ['data' => $result]);
    }

    /**
     * V2.9.19 D-1d: 重试队列统计
     */
    public function retryStats()
    {
        $stats = PushRetryService::getStats();
        return $this->success('ok', $stats);
    }
}
