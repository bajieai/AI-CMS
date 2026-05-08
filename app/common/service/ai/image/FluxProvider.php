<?php
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
     * 生成图片（FLUX API）
     *
     * 使用 BFL API 格式（非 OpenAI 兼容）
     * 注意：FLUX API 是异步的，需要轮询结果
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

            // 轮询获取结果（最多30秒）
            $imageUrl = $this->pollForResult($body['polling_url']);

            return [
                'url'          => $imageUrl,
                'width'        => $size['width'],
                'height'       => $size['height'],
                'format'       => $options['format'] ?? 'png',
                '_provider'    => 'flux',
                '_request_id'  => $body['id'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('[FluxProvider] ' . $e->getMessage());
            throw new \Exception('FLUX API 调用失败: ' . $e->getMessage());
        }
    }

    /**
     * 轮询异步结果
     */
    protected function pollForResult(string $pollingUrl): string
    {
        $startTime = time();
        $maxWait = 30; // 最多等待30秒

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
