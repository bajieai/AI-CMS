<?php
declare(strict_types=1);

namespace app\common\service\publish;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 头条号发布适配器 - V2.7
 * 基于字节跳动开放平台的头条号API
 * 新增：OAuth2.0授权流程、Token自动刷新、降级处理
 */
class ToutiaoPlatform implements PublishPlatformInterface
{
    private const BASE_URI = 'https://open-api.toutiao.com/';

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
            ['name' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => false],
            ['name' => 'refresh_token', 'label' => 'Refresh Token', 'type' => 'password', 'required' => false],
            ['name' => 'expires_in', 'label' => 'Token过期时间', 'type' => 'number', 'required' => false],
        ];
    }

    public function validateConfig(PublishPlatform $platform): bool
    {
        $config = $platform->config_json;
        return !empty($config['client_key']) && !empty($config['client_secret']);
    }

    /**
     * 获取OAuth授权URL
     */
    public static function getAuthUrl(string $clientKey, string $redirectUri, string $state = ''): string
    {
        $params = [
            'client_key' => $clientKey,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => 'user_info article',
            'state' => $state,
        ];
        return self::BASE_URI . 'platform/oauth/connect/?' . http_build_query($params);
    }

    /**
     * 通过code换取access_token
     */
    public static function fetchToken(string $clientKey, string $clientSecret, string $code, string $redirectUri): array
    {
        $client = new Client(['timeout' => 30, 'base_uri' => self::BASE_URI]);
        $response = $client->post('oauth/access_token/', [
            'form_params' => [
                'client_key' => $clientKey,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);
        if (empty($result['data']['access_token'])) {
            throw new \Exception('获取Token失败: ' . ($result['message'] ?? '未知错误'));
        }
        return $result['data'];
    }

    /**
     * 刷新access_token
     */
    public static function refreshToken(string $clientKey, string $refreshToken): array
    {
        $client = new Client(['timeout' => 30, 'base_uri' => self::BASE_URI]);
        $response = $client->post('oauth/refresh_token/', [
            'form_params' => [
                'client_key' => $clientKey,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        $result = json_decode((string) $response->getBody(), true);
        if (empty($result['data']['access_token'])) {
            throw new \Exception('刷新Token失败: ' . ($result['message'] ?? '未知错误'));
        }
        return $result['data'];
    }

    /**
     * 获取有效的access_token（自动刷新）
     */
    public function getValidAccessToken(PublishPlatform $platform): string
    {
        $config = $platform->config_json;
        $accessToken = $config['access_token'] ?? '';
        $expiresIn = (int) ($config['expires_in'] ?? 0);

        // 如果Token将在30分钟内过期，尝试刷新
        if ($expiresIn > 0 && $expiresIn < time() + 1800) {
            $refreshToken = $config['refresh_token'] ?? '';
            if ($refreshToken) {
                try {
                    $newToken = self::refreshToken($config['client_key'], $refreshToken);
                    $config['access_token'] = $newToken['access_token'];
                    $config['refresh_token'] = $newToken['refresh_token'] ?? $refreshToken;
                    $config['expires_in'] = time() + ($newToken['expires_in'] ?? 7200);
                    $platform->config_json = $config;
                    $platform->save();
                    return $config['access_token'];
                } catch (\Exception $e) {
                    Log::warning('头条号Token刷新失败: ' . $e->getMessage());
                }
            }
        }

        if (empty($accessToken)) {
            throw new \Exception('头条号Access Token为空，请重新授权');
        }

        return $accessToken;
    }

    public function publish(Content $content, PublishPlatform $platform): array
    {
        if (!$this->validateConfig($platform)) {
            throw new \Exception('头条号配置不完整，请检查Client Key和Client Secret');
        }

        $accessToken = $this->getValidAccessToken($platform);

        $client = new Client(['timeout' => 30, 'base_uri' => self::BASE_URI]);

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
