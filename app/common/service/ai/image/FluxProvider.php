<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\ai\image;

use app\common\service\ai\ImageProviderInterface;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * FLUX 配图生成 Provider - V2.9新增
 *
 * 支持 FLUX.1 [pro/ultra/dev] 系列模型
 * API文档：https://api-docs.bfl.ai/
 *
 * 配置示例（config/ai.php）：
 * 'providers' => [
 *     'flux' => [
 *         'enabled' => true,
 *         'api_key' => 'your-bfl-api-key',
 *         'model'   => 'flux-pro',
 *         'timeout' => 30,
 *     ],
 * ],
 */
class FluxProvider implements ImageProviderInterface
{
    protected array $config;
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    public function __construct(array $config = [])
    {
        $this->config  = $config ?: config('ai.image.providers.flux', []);
        $this->apiKey = $this->config['api_key'] ?? '';
        $this->model  = $this->config['model'] ?? 'flux-pro';
        $this->client = new Client([
            'base_uri' => 'https://api.bfl.ai',
            'timeout'  => (int) ($this->config['timeout'] ?? 30),
        ]);
    }

    /**
     * 生成图片（FLUX API）— V2.9.1 M14a 异步化改造
     *
     * 使用 BFL API 格式（非 OpenAI 兼容）
     * 注意：FLUX API 是异步的，V2.9.1改为写入DB任务表+前端轮询，不再阻塞PHP-FPM
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $style   = $options['style'] ?? 'realistic';
        $size    = $this->parseSize($options['size'] ?? '1024x1024');

        // 根据 style 调整 prompt
        $styledPrompt = $this->applyStyle($prompt, $style);

        try {
            // BFL FLUX API: POST /v1/image/generate/{model}
            $response = $this->client->post("/v1/image/generate/{$this->model}", [
                'headers' => [
                    'X-Key'       => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'prompt'        => $styledPrompt,
                    'width'         => $size['width'],
                    'height'        => $size['height'],
                    'steps'         => (int) ($this->config['steps'] ?? 25),
                    'guidance_scale' => (float) ($this->config['guidance_scale'] ?? 3.5),
                    'safety_tolerance' => (int) ($this->config['safety_tolerance'] ?? 2),
                    'output_format' => $options['format'] ?? 'png',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // BFL API 返回：{id, polling_url, ...}
            if (empty($body['polling_url'])) {
                throw new \Exception('FLUX API 未返回 polling_url');
            }

            // V2.9.1: 异步化 — 写入任务表，立即返回task_id供前端轮询
            $taskId = $body['id'] ?? uniqid('flux_', true);
            $task = \app\common\model\ImageTask::create([
                'task_id'      => $taskId,
                'provider'     => 'flux',
                'poll_url'     => $body['polling_url'],
                'status'       => \app\common\model\ImageTask::STATUS_PENDING,
                'prompt'       => $styledPrompt,
                'result'       => null,
                'attempts'     => 0,
                'max_attempts' => 30, // 30次×3秒≈90秒
                'related_type' => $options['related_type'] ?? '',
                'related_id'   => $options['related_id'] ?? 0,
                'error_msg'    => '',
                'retry_count'  => 0,
                'local_path'   => '',
                'create_time'  => time(),
                'update_time'  => time(),
            ]);

            Log::info("[FluxProvider] 异步任务已创建 task_id={$taskId}, db_id={$task->id}");

            return [
                'url'          => '', // 异步模式下不立即返回URL
                'width'        => $size['width'],
                'height'       => $size['height'],
                'format'       => $options['format'] ?? 'png',
                '_provider'    => 'flux',
                '_request_id'  => $taskId,
                '_task_id'     => $taskId,         // 前端轮询用
                '_db_id'       => $task->id,       // 内部ID
                '_async'       => true,            // 标记为异步模式
            ];

        } catch (\Exception $e) {
            Log::error('[FluxProvider] ' . $e->getMessage());
            throw new \Exception('FLUX API 调用失败: ' . $e->getMessage());
        }
    }

    /**
     * 轮询异步结果 — V2.9.1已废弃，轮询逻辑迁移到ImagePollCommand CLI命令
     * 保留此方法仅用于向后兼容或本地调试
     */
    protected function pollForResult(string $pollingUrl): string
    {
        $startTime = time();
        $maxWait = 30;

        while (time() - $startTime < $maxWait) {
            sleep(2);

            $response = $this->client->get($pollingUrl, [
                'headers' => ['X-Key' => $this->apiKey],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            $status = $body['status'] ?? '';
            if ($status === 'Ready' && !empty($body['result']['sample'])) {
                return $body['result']['sample'];
            }
            if ($status === 'Failed') {
                throw new \Exception('FLUX 生成失败: ' . ($body['error'] ?? '未知错误'));
            }
        }

        throw new \Exception('FLUX 生成超时（>30s）');
    }

    /**
     * 根据 style 调整 prompt
     */
    protected function applyStyle(string $prompt, string $style): string
    {
        $suffix = [
            'realistic'    => 'photorealistic, high quality, detailed',
            'illustration' => 'digital illustration style, vibrant colors',
            'watercolor'   => 'watercolor painting style, soft edges',
            '3d_render'   => '3D render, cinematic lighting',
            'pixel_art'   => 'pixel art style, 8-bit',
        ][$style] ?? '';

        return $suffix ? $prompt . ', ' . $suffix : $prompt;
    }

    /**
     * 解析尺寸字符串
     */
    protected function parseSize(string $size): array
    {
        if (preg_match('/(\d+)x(\d+)/', $size, $matches)) {
            return ['width' => (int) $matches[1], 'height' => (int) $matches[2]];
        }
        return ['width' => 1024, 'height' => 1024];
    }

    public function getImageInfo(): array
    {
        return [
            'provider'         => 'flux',
            'model'           => $this->model,
            'max_resolution'  => '2048x2048',
            'supported_styles' => ['realistic', 'illustration', 'watercolor', '3d_render', 'pixel_art'],
        ];
    }

    public function getSupportedStyles(): array
    {
        return ['realistic', 'illustration', 'watercolor', '3d_render', 'pixel_art'];
    }
}
