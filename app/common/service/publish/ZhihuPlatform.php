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

namespace app\common\service\publish;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 知乎专栏发布适配器 - V2.9.2 M20b
 * 复用PublishPlatformInterface策略模式
 */
class ZhihuPlatform implements PublishPlatformInterface
{
    /**
     * 构建HTTP客户端（从平台配置动态创建）
     */
    protected function buildClient(PublishPlatform $platform): Client
    {
        $config = json_decode($platform->config_json ?? '{}', true);
        return new Client([
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . ($config['access_token'] ?? ''),
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function getName(): string
    {
        return 'zhihu';
    }

    public function getDisplayName(): string
    {
        return '知乎专栏';
    }

    public function validateConfig(PublishPlatform $platform): bool
    {
        $config = json_decode($platform->config_json ?? '{}', true);
        return !empty($config['access_token']);
    }

    public function getConfigFields(): array
    {
        return [
            'client_id'     => ['label' => 'Client ID',     'type' => 'text',   'required' => true],
            'client_secret' => ['label' => 'Client Secret', 'type' => 'text',   'required' => true],
            'access_token'  => ['label' => 'Access Token',  'type' => 'text',   'required' => true],
            'refresh_token' => ['label' => 'Refresh Token', 'type' => 'text',   'required' => false],
            'column_id'     => ['label' => '专栏ID',        'type' => 'text',   'required' => false],
        ];
    }

    public function publish(Content $content, PublishPlatform $platform): array
    {
        try {
            $config = json_decode($platform->config_json ?? '{}', true);
            $client = $this->buildClient($platform);

            $title = $content->title;
            $body  = $this->convertHtmlToZhihu($content->content);
            $excerpt = mb_substr(strip_tags($content->excerpt ?: $content->content), 0, 200);

            $params = [
                'title'       => $title,
                'content'     => $body,
                'excerpt'     => $excerpt,
                'cover_image' => $content->cover ?: '',
            ];

            if (!empty($config['column_id'])) {
                $params['column_id'] = $config['column_id'];
            }

            $response = $client->post('https://www.zhihu.com/api/v4/articles', [
                'json' => $params,
            ]);

            $result = json_decode((string) $response->getBody(), true);

            if (isset($result['id'])) {
                return [
                    'media_id'  => (string) $result['id'],
                    'url'       => $result['url'] ?? '',
                ];
            }

            throw new \Exception($result['error']['message'] ?? '发布失败');
        } catch (\Throwable $e) {
            Log::warning('[ZhihuPlatform] 发布失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 将HTML内容转换为知乎兼容格式
     */
    protected function convertHtmlToZhihu(string $html): string
    {
        // 知乎支持HTML子集，直接返回清理后的HTML
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><b><em><i><u><s><blockquote><pre><code><ul><ol><li><a><img><table><thead><tbody><tr><th><td>';
        $clean = strip_tags($html, $allowedTags);

        // 处理图片：知乎要求图片先上传获取URL
        // 简化处理：保留原始img src（假设已托管到可访问的CDN）
        return $clean;
    }
}
