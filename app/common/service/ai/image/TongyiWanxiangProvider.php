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

class TongyiWanxiangProvider implements ImageProviderInterface
{
    protected array $config;
    protected Client $client;
    protected string $apiKey;
    protected string $model;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('ai.image.providers.tongyi_wanxiang', []);
        $this->apiKey = $this->config['api_key'] ?? '';
        $this->model = $this->config['model'] ?? 'wanx-v1';
        $this->client = new Client([
            'base_uri' => 'https://dashscope.aliyuncs.com',
            'timeout' => (int)($this->config['timeout'] ?? 15),
        ]);
    }

    public function generateImage(string $prompt, array $options = []): array
    {
        $style = $options['style'] ?? 'realistic';
        $count = min((int)($options['count'] ?? 1), 5);
        $size = $options['size'] ?? '1024x1024';
        
        try {
            $response = $this->client->post('/api/v1/services/aigc/text2image/image-synthesis', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'X-DashScope-Async' => 'disable',
                ],
                'json' => [
                    'model' => $this->model,
                    'input' => ['prompt' => $prompt],
                    'parameters' => [
                        'style' => $style,
                        'size' => $size,
                        'n' => $count,
                        'seed' => rand(100000, 999999),
                    ],
                ],
            ]);
            
            $body = json_decode($response->getBody()->getContents(), true);
            $results = [];
            
            if (!empty($body['output']['results'])) {
                foreach ($body['output']['results'] as $result) {
                    $results[] = [
                        'url' => $result['url'] ?? '',
                        'width' => (int)($body['usage']['image_width'] ?? 1024),
                        'height' => (int)($body['usage']['image_height'] ?? 1024),
                        'format' => 'png',
                        '_provider' => 'tongyi_wanxiang',
                        '_request_id' => $body['request_id'] ?? '',
                    ];
                }
            }
            
            return $results[0] ?? ['url' => '', 'width' => 0, 'height' => 0, 'format' => '', '_provider' => 'tongyi_wanxiang', '_request_id' => ''];
            
        } catch (\Exception $e) {
            throw new \Exception('通义万相API调用失败: ' . $e->getMessage());
        }
    }

    public function getImageInfo(): array
    {
        return [
            'provider' => 'tongyi_wanxiang',
            'model' => $this->model,
            'max_resolution' => '1024x1024',
        ];
    }

    public function getSupportedStyles(): array
    {
        return ['realistic', 'illustration', 'watercolor', '3d_render', 'pixel_art'];
    }
}
