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

namespace app\common\service\oauth;

use app\common\service\ConfigService;
use GuzzleHttp\Client;

/**
 * QQ OAuth Provider
 * QQ互联OAuth2.0协议
 */
class QqOauthProvider implements OauthProviderInterface
{
    protected string $appId;
    protected string $appKey;
    protected string $redirectUri;
    protected Client $client;

    public function __construct()
    {
        $this->appId = (string) ConfigService::get('qq_appid', '');
        $this->appKey = (string) ConfigService::get('qq_appkey', '');
        $this->redirectUri = request()->domain() . '/oauth/qq/callback';
        $this->client = new Client(['timeout' => 10]);
    }

    public function getAuthUrl(string $state): string
    {
        return 'https://graph.qq.com/oauth2.0/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
            'scope'         => 'get_user_info',
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = $this->client->get('https://graph.qq.com/oauth2.0/token', [
            'query' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->appId,
                'client_secret' => $this->appKey,
                'code'          => $code,
                'redirect_uri'  => $this->redirectUri,
            ],
        ]);

        // QQ返回的是callback格式，需解析
        $body = (string) $response->getBody();
        parse_str($body, $data);

        if (empty($data['access_token'])) {
            throw new \Exception('获取QQ Token失败');
        }

        return [
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 7776000,
            'openid'        => '', // 需要单独获取
        ];
    }

    /**
     * 通过Access Token获取OpenID
     */
    public function getOpenId(string $accessToken): string
    {
        $response = $this->client->get('https://graph.qq.com/oauth2.0/me', [
            'query' => [
                'access_token' => $accessToken,
            ],
        ]);

        $body = (string) $response->getBody();
        // QQ返回callback( {"client_id":"...","openid":"..."} );
        preg_match('/"openid"\s*:\s*"([^"]+)"/', $body, $matches);
        return $matches[1] ?? '';
    }

    public function getUserInfo(string $accessToken, string $openid): array
    {
        if (empty($openid)) {
            $openid = $this->getOpenId($accessToken);
        }

        $response = $this->client->get('https://graph.qq.com/user/get_user_info', [
            'query' => [
                'access_token'       => $accessToken,
                'oauth_consumer_key' => $this->appId,
                'openid'             => $openid,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    public function mapUserData(array $raw, string $openid = ''): array
    {
        return [
            'openid'   => $openid ?: ($raw['openid'] ?? ''),
            'unionid'  => $raw['unionid'] ?? '',
            'nickname' => $raw['nickname'] ?? 'QQ用户',
            'avatar'   => $raw['figureurl_qq_2'] ?? $raw['figureurl_qq_1'] ?? $raw['figureurl'] ?? '',
        ];
    }

    public function getName(): string
    {
        return 'qq';
    }
}
