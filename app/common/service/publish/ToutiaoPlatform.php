<?php
declare(strict_types=1);

namespace app\common\service\publish;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 头条号发布适配器 - V2.5
 * 基于字节跳动开放平台的头条号API
 */
class ToutiaoPlatform implements PublishPlatformInterface
{
    public function getName(): string
    {
        return 'toutiao';
    }

    public function getDisplayName(): string
    {
        return '头条号';
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'client_key', 'label' => 'Client Key', 'type' => 'text', 'required' => true],
            ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
            ['name' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
        ];
    }

    public function validateConfig(PublishPlatform $platform): bool
    {
        $config = $platform->config_json;
        return !empty($config['client_key']) && !empty($config['client_secret']) && !empty($config['access_token']);
    }

    public function publish(Content $content, PublishPlatform $platform): array
    {
        if (!$this->validateConfig($platform)) {
            throw new \Exception('头条号配置不完整，请检查Client Key、Client Secret和Access Token是否均已配置');
        }

        $config = $platform->config_json;
        $accessToken = $config['access_token'] ?? '';

        $client = new Client(['timeout' => 30, 'base_uri' => 'https://open-api.toutiao.com/']);

        $article = [
            'title' => $content->title,
            'content' => $this->formatContent($content->content),
            'abstract' => mb_substr(strip_tags($content->content), 0, 200),
            'source' => 'AI-CMS',
            'article_type' => 0, // 0: 图文
        ];

        try {
            $response = $client->post('article/v1/create/', [
                'headers' => [
                    'Access-Token' => $accessToken,
                ],
                'json' => $article,
            ]);

            $result = json_decode((string) $response->getBody(), true);

            if (!isset($result['data']['article_id'])) {
                $msg = $result['message'] ?? $result['data']['description'] ?? '头条号发布失败';
                Log::error('头条号发布失败: ' . $msg);
                throw new \Exception('头条号发布失败: ' . $msg);
            }

            return ['article_id' => $result['data']['article_id']];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = json_decode((string) $e->getResponse()->getBody(), true);
            $msg = $errorBody['message'] ?? $e->getMessage();
            Log::error('头条号API错误: ' . $msg);
            throw new \Exception('头条号发布失败: ' . $msg);
        }
    }

    /**
     * 格式化内容为头条号格式
     */
    protected function formatContent(string $html): string
    {
        return strip_tags($html, '<p><br><strong><em><h1><h2><h3><img><a><ul><ol><li><blockquote><span>');
    }
}
