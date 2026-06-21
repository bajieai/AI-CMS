<?php
declare(strict_types=1);
namespace app\common\service\oauth;

use think\facade\Config;
use think\facade\Http;

class GitHubOauthProvider implements OauthProviderInterface
{
    private array $config;

    public function __construct() { $this->config = Config::get('oauth.github', []); }

    public function getAuthorizeUrl(string $state = ''): string
    {
        $params = [
            'client_id' => $this->config['client_id'] ?? '',
            'redirect_uri' => $this->config['redirect_uri'] ?? '',
            'scope' => 'user:email', 'state' => $state,
        ];
        return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::post('https://github.com/login/oauth/access_token', [
            'client_id' => $this->config['client_id'] ?? '',
            'client_secret' => $this->config['client_secret'] ?? '',
            'code' => $code, 'redirect_uri' => $this->config['redirect_uri'] ?? '',
        ], ['Accept' => 'application/json']);
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = Http::get('https://api.github.com/user', [], [
            'Authorization' => 'token ' . $accessToken, 'Accept' => 'application/vnd.github.v3+json',
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        return [
            'openid' => (string)($data['id'] ?? ''), 'nickname' => $data['login'] ?? '',
            'avatar' => $data['avatar_url'] ?? '', 'email' => $data['email'] ?? '', 'raw' => $data,
        ];
    }

    public function getProviderName(): string { return 'github'; }
    public function isAvailable(): bool { return !empty($this->config['client_id']) && !empty($this->config['client_secret']); }
}
