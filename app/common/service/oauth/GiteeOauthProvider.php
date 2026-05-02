<?php
declare(strict_types=1);

namespace app\common\service\oauth;

use app\common\service\ConfigService;
use GuzzleHttp\Client;

/**
 * Gitee OAuth Provider
 * 从OauthController迁移，实现统一接口
 */
class GiteeOauthProvider implements OauthProviderInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected Client $client;

    public function __construct()
    {
        $this->clientId = (string) ConfigService::get('gitee_client_id', config('oauth.gitee_client_id', ''));
        $this->clientSecret = (string) ConfigService::get('gitee_client_secret', config('oauth.gitee_client_secret', ''));
        $this->redirectUri = request()->domain() . '/oauth/gitee/callback';
        $this->client = new Client(['timeout' => 10]);
    }

    public function getAuthUrl(string $state): string
    {
        return 'https://gitee.com/oauth/authorize?' . http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'state'         => $state,
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = $this->client->post('https://gitee.com/oauth/token', [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        if (empty($data['access_token'])) {
            throw new \Exception('获取Gitee Token失败');
        }

        return [
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 7200,
            'openid'        => '',
        ];
    }

    public function getUserInfo(string $accessToken, string $openid = ''): array
    {
        $response = $this->client->get('https://gitee.com/api/v5/user', [
            'headers' => ['Authorization' => 'token ' . $accessToken],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    public function mapUserData(array $raw): array
    {
        return [
            'openid'   => (string) ($raw['id'] ?? ''),
            'unionid'  => '',
            'nickname' => $raw['name'] ?? $raw['login'] ?? 'Gitee用户',
            'avatar'   => $raw['avatar_url'] ?? '',
        ];
    }

    public function getName(): string
    {
        return 'gitee';
    }
}
