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
 * OpenAI DALL-E 配图生成 Provider - V2.9新增
 *
 * 支持 DALL-E 3 / DALL-E 2 模型
 * API文档：https://platform.openai.com/docs/api-reference/images
 *
 * 配置示例（config/ai.php）：
 * 'providers' => [
 *     'dalle' => [
 *         'enabled'  => true,
 *         'api_key' => 'sk-xxx',
 *         'model'    => 'dall-e-3',  // 或 dall-e-2
 *         'timeout'  => 30,
 *         'base_url' => 'https://api.openai.com/v1',  // 可自定义（如代理）
 *     ],
 * ],
 */
class DalleProvider implements ImageProviderInterface
{
    protected array $config;
    protected Client $client;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config  = $config ?: config('ai.image.providers.dalle', []);
        $this->apiKey = $this->config['api_key'] ?? '';
        $this->model  = $this->config['model'] ?? 'dall-e-3';
        $this->baseUrl = $this->config['base_url'] ?? 'https://api.openai.com/v1';

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => (int) ($this->config['timeout'] ?? 30),
        ]);
    }

    /**
     * 生成图片（OpenAI DALL-E API）
     *
     * DALL-E 3 限制：n 只能为 1
     * DALL-E 2 支持 n=1~10
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $style  = $options['style'] ?? 'natural';   // natural / vivid（仅DALL-E 3）
        $count  = min((int) ($options['count'] ?? 1), $this->model === 'dall-e-3' ? 1 : 10);
        $size   = $options['size'] ?? '1024x1024';
        $quality = $options['quality'] ?? 'standard';  // standard / hd（仅DALL-E 3）
        $format  = $options['format'] ?? 'url';      // url / b64_json

        // DALL-E 3 只支持特定尺寸
        $validSizes = $this->model === 'dall-e-3'
            ? ['1024x1024', '1792x1024', '1024x1792']
            : ['256x256', '512x512', '1024x1024'];
        if (!in_array($size, $validSizes)) {
            $size = '1024x1024';
        }

        $requestBody = [
            'model'           => $this->model,
            'prompt'          => $prompt,
            'n'               => $count,
            'size'            => $size,
            'response_format' => $format,
        ];

        // DALL-E 3 专属参数
        if ($this->model === 'dall-e-3') {
            $requestBody['quality'] = $quality;
            $requestBody['style']   = $style;
            $requestBody['n']       = 1;  // DALL-E 3 强制 n=1
        }

        try {
            $response = $this->client->post('/images/generations', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => $requestBody,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $results = [];

            if (!empty($body['data'])) {
                foreach ($body['data'] as $item) {
                    $results[] = [
                        'url'          => $item['url'] ?? '',
                        'b64_json'     => $item['b64_json'] ?? null,
                        'width'        => (int) explode('x', $size)[0],
                        'height'       => (int) explode('x', $size)[1],
                        'format'       => $format === 'b64_json' ? 'png' : 'png',
                        '_provider'    => 'dalle',
                        '_model'       => $this->model,
                        '_revised_prompt' => $item['revised_prompt'] ?? null,
                    ];
                }
            }

            return $results[0] ?? ['url' => '', 'width' => 0, 'height' => 0, 'format' => '', '_provider' => 'dalle', '_request_id' => ''];

        } catch (\Exception $e) {
            Log::error('[DalleProvider] ' . $e->getMessage());
            throw new \Exception('DALL-E API 调用失败: ' . $e->getMessage());
        }
    }

    public function getImageInfo(): array
    {
        return [
            'provider'        => 'dalle',
            'model'          => $this->model,
            'max_resolution' => $this->model === 'dall-e-3' ? '1792x1792' : '1024x1024',
            'supported_styles' => $this->model === 'dall-e-3'
                ? ['natural', 'vivid']
                : ['natural'],
        ];
    }

    public function getSupportedStyles(): array
    {
        if ($this->model === 'dall-e-3') {
            return ['natural', 'vivid'];
        }
        return ['natural'];
    }
}
