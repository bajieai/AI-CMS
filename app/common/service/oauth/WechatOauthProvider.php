<?php
declare(strict_types=1);

namespace app\common\service\oauth;

use app\common\service\ConfigService;
use GuzzleHttp\Client;

/**
 * 微信OAuth Provider
 * 支持PC端扫码登录 + 移动端H5授权登录
 */
class WechatOauthProvider implements OauthProviderInterface
{
    protected string $appid;
    protected string $secret;
    protected string $redirectUri;
    protected Client $client;

    public function __construct()
    {
        $this->appid = (string) ConfigService::get('wechat_open_appid', '');
        $this->secret = (string) ConfigService::get('wechat_open_secret', '');
        $this->redirectUri = request()->domain() . '/oauth/wechat/callback';
        $this->client = new Client(['timeout' => 10]);
    }

    public function getAuthUrl(string $state): string
    {
        // PC端扫码登录
        return 'https://open.weixin.qq.com/connect/qrconnect?' . http_build_query([
            'appid'         => $this->appid,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'snsapi_login',
            'state'         => $state,
        ]) . '#wechat_redirect';
    }

    /**
     * 移动端H5授权URL
     */
    public function getMobileAuthUrl(string $state): string
    {
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query([
            'appid'         => $this->appid,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'snsapi_userinfo',
            'state'         => $state,
        ]) . '#wechat_redirect';
    }

    public function getAccessToken(string $code): array
    {
        $response = $this->client->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'query' => [
                'appid'      => $this->appid,
                'secret'     => $this->secret,
                'code'       => $code,
                'grant_type' => 'authorization_code',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['errcode'])) {
            throw new \Exception('微信授权失败: ' . ($data['errmsg'] ?? '未知错误'));
        }

        return [
            'access_token'  => $data['access_token'] ?? '',
            'refresh_token' => $data['refresh_token'] ?? '',
            'expires_in'    => $data['expires_in'] ?? 7200,
            'openid'        => $data['openid'] ?? '',
            'unionid'       => $data['unionid'] ?? '',
        ];
    }

    public function getUserInfo(string $accessToken, string $openid): array
    {
        $response = $this->client->get('https://api.weixin.qq.com/sns/userinfo', [
            'query' => [
                'access_token' => $accessToken,
                'openid'       => $openid,
                'lang'         => 'zh_CN',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['errcode'])) {
            throw new \Exception('获取微信用户信息失败: ' . ($data['errmsg'] ?? '未知错误'));
        }

        return $data;
    }

    public function mapUserData(array $raw): array
    {
        return [
            'openid'   => $raw['openid'] ?? '',
            'unionid'  => $raw['unionid'] ?? '',
            'nickname' => $raw['nickname'] ?? '微信用户',
            'avatar'   => $raw['headimgurl'] ?? '',
        ];
    }

    public function getName(): string
    {
        return 'wechat';
    }
}
