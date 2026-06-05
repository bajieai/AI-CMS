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

namespace app\common\service\push;

use app\common\model\PushChannel;
use app\common\model\PushLog;
use app\common\model\Content;

/**
 * 推送分发服务 - V2.9.18 D-1
 * 
 * 核心分发引擎：
 * 1. 根据内容ID获取所有已启用的自动推送通道
 * 2. 对每个通道调用对应的 Channel 实现类
 * 3. 记录推送日志到 i8j_push_log
 */
class PushDispatchService
{
    /** 通道实现类映射 */
    protected array $channelMap = [
        PushChannel::TYPE_WEBHOOK   => ChannelWebhook::class,
        PushChannel::TYPE_WECHAT    => ChannelWechat::class,
        PushChannel::TYPE_BROADCAST => ChannelBroadcast::class,
    ];

    /**
     * 推送内容到所有已启用的自动通道（发布时调用）
     *
     * @param int $contentId 内容ID
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'results' => array]
     */
    public function dispatch(int $contentId): array
    {
        $channels = PushChannel::getAutoChannels();
        return $this->doDispatch($contentId, $channels);
    }

    /**
     * 手动推送到所有已启用通道
     *
     * @param int $contentId 内容ID
     * @param array|null $channelIds 指定通道ID，null=全部已启用通道
     */
    public function dispatchManual(int $contentId, ?array $channelIds = null): array
    {
        if ($channelIds) {
            $channels = PushChannel::whereIn('id', $channelIds)
                ->where('status', PushChannel::STATUS_ENABLED)
                ->select()->toArray();
        } else {
            $channels = PushChannel::getEnabledChannels();
        }
        return $this->doDispatch($contentId, $channels);
    }

    /**
     * 推送到单个指定通道
     */
    public function dispatchToChannel(int $channelId, int $contentId): array
    {
        $channel = PushChannel::find($channelId);
        if (!$channel) {
            return $this->buildError('通道不存在');
        }

        $channelData = $channel->toArray();
        return $this->doDispatch($contentId, [$channelData]);
    }

    /**
     * 测试推送通道
     */
    public function testChannel(int $channelId): array
    {
        $channel = PushChannel::find($channelId);
        if (!$channel) {
            return $this->buildError('通道不存在');
        }

        $testPayload = [
            'event'     => 'test',
            'timestamp' => date('c'),
            'site_name' => $this->getSiteName(),
            'data'      => [
                'id'      => 0,
                'title'   => '【AI-CMS 测试消息】',
                'summary' => '这是一条来自 AI-CMS V2.9.18 推送引擎的测试消息，恭喜配置成功！',
                'url'     => $this->getSiteUrl(),
            ],
        ];

        $channelData = $channel->toArray();
        $result = $this->pushToChannel($channelData, $testPayload);

        // 测试推送也记录日志
        PushLog::record([
            'channel_id'    => $channelId,
            'content_id'    => 0,
            'request_url'   => $channelData['config']['url'] ?? '',
            'request_body'  => json_encode($testPayload, JSON_UNESCAPED_UNICODE),
            'response_code' => $result['response_code'],
            'response_body' => $result['response_body'],
            'duration_ms'   => $result['duration_ms'],
            'status'        => $result['success'] ? PushLog::STATUS_SUCCESS : PushLog::STATUS_FAILED,
            'error_msg'     => $result['error_msg'],
        ]);

        return $result;
    }

    /**
     * 重试失败推送
     */
    public function retry(int $logId): array
    {
        $log = PushLog::find($logId);
        if (!$log) {
            return $this->buildError('日志记录不存在');
        }
        if ($log->status != PushLog::STATUS_FAILED) {
            return $this->buildError('仅可重试失败的推送');
        }

        $channel = PushChannel::find($log->channel_id);
        if (!$channel) {
            return $this->buildError('推送通道已删除');
        }

        $contentId = $log->content_id;
        $payload = $this->buildPayload($contentId);

        $channelData = $channel->toArray();
        $result = $this->pushToChannel($channelData, $payload);

        // 更新重试结果
        $log->save([
            'response_code' => $result['response_code'],
            'response_body' => $result['response_body'],
            'duration_ms'   => $result['duration_ms'],
            'status'        => $result['success'] ? PushLog::STATUS_SUCCESS : PushLog::STATUS_FAILED,
            'error_msg'     => $result['error_msg'],
            'retried_at'    => date('Y-m-d H:i:s'),
        ]);

        return $result;
    }

    /**
     * 核心分发逻辑
     */
    protected function doDispatch(int $contentId, array $channels): array
    {
        $payload   = $this->buildPayload($contentId);
        $total     = count($channels);
        $success   = 0;
        $failed    = 0;
        $results   = [];

        foreach ($channels as $channelData) {
            $result = $this->pushToChannel($channelData, $payload);

            // 记录日志
            PushLog::record([
                'channel_id'    => $channelData['id'],
                'content_id'    => $contentId,
                'request_url'   => $channelData['config']['url'] ?? '',
                'request_body'  => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'response_code' => $result['response_code'],
                'response_body' => $result['response_body'],
                'duration_ms'   => $result['duration_ms'],
                'status'        => $result['success'] ? PushLog::STATUS_SUCCESS : PushLog::STATUS_FAILED,
                'error_msg'     => $result['error_msg'],
            ]);

            // 更新通道最后推送时间
            PushChannel::where('id', $channelData['id'])->update([
                'last_push_at' => date('Y-m-d H:i:s'),
            ]);

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }

            $results[] = [
                'channel_id' => $channelData['id'],
                'channel_name' => $channelData['name'],
                'success'    => $result['success'],
                'error_msg'  => $result['error_msg'],
            ];
        }

        return [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
            'results' => $results,
        ];
    }

    /**
     * 通过特定通道推送
     */
    protected function pushToChannel(array $channelData, array $payload): array
    {
        $type       = $channelData['type'] ?? PushChannel::TYPE_WEBHOOK;
        $config     = $channelData['config'] ?? [];
        $handlerClass = $this->channelMap[$type] ?? ChannelWebhook::class;

        try {
            $handler = new $handlerClass();
            return $handler->push($payload, $config);
        } catch (\Throwable $e) {
            return [
                'success'       => false,
                'response_code' => 0,
                'response_body' => '',
                'duration_ms'   => 0,
                'error_msg'     => $e->getMessage(),
            ];
        }
    }

    /**
     * 构建推送数据 Payload
     */
    protected function buildPayload(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return [
                'event'     => 'content.published',
                'timestamp' => date('c'),
                'data'      => ['id' => $contentId, 'title' => '未知内容'],
            ];
        }

        $contentData = $content->toArray();

        return [
            'event'     => 'content.published',
            'timestamp' => date('c'),
            'site_name' => $this->getSiteName(),
            'data'      => [
                'id'           => (int) $contentData['id'],
                'title'        => $contentData['title'] ?? '',
                'summary'      => mb_substr(strip_tags($contentData['description'] ?? ''), 0, 200),
                'content'      => mb_substr(strip_tags($contentData['content'] ?? ''), 0, 500),
                'url'          => $this->getSiteUrl() . '/info/' . $contentId . '.html',
                'cover'        => $contentData['cover'] ?? '',
                'category'     => $contentData['cate_name'] ?? '',
                'category_id'  => (int) ($contentData['cate_id'] ?? 0),
                'tags'         => $this->getTags($contentData),
                'author'       => $contentData['author'] ?? '',
                'published_at' => $contentData['update_time'] ?? date('Y-m-d H:i:s'),
                'language'     => $contentData['lang'] ?? 'zh-cn',
            ],
        ];
    }

    protected function getTags(array $contentData): array
    {
        if (!empty($contentData['tags'])) {
            return is_array($contentData['tags'])
                ? $contentData['tags']
                : explode(',', $contentData['tags']);
        }
        return [];
    }

    protected function getSiteName(): string
    {
        try {
            return \app\common\model\Config::where('name', 'site_name')->value('value') ?: 'AI-CMS';
        } catch (\Throwable $e) {
            return 'AI-CMS';
        }
    }

    protected function getSiteUrl(): string
    {
        try {
            return \app\common\model\Config::where('name', 'site_url')->value('value') ?: '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function buildError(string $msg): array
    {
        return ['success' => false, 'error_msg' => $msg];
    }
}
