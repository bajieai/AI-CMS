<?php

declare(strict_types=1);

use think\facade\Db;
use think\facade\Redirect;

/**
 * 第三方登录插件主类
 * 演示OAuth回调处理+用户绑定
 */
class OauthLoginPlugin
{
    /**
     * 用户登录前：检查是否通过OAuth登录
     */
    public function beforeLogin(array $data): array
    {
        // 如果请求中带有oauth_token，走OAuth验证流程
        if (!empty($data['oauth_type']) && !empty($data['oauth_token'])) {
            $oauthUser = $this->verifyOauthToken($data['oauth_type'], $data['oauth_token']);
            if ($oauthUser) {
                // 查找绑定的本地用户
                $localUser = Db::name('member_oauth')
                    ->where('provider', $data['oauth_type'])
                    ->where('openid', $oauthUser['openid'])
                    ->find();

                if ($localUser) {
                    $data['user_id'] = $localUser['member_id'];
                    $data['skip_password'] = true;
                }
            }
        }

        return $data;
    }

    /**
     * 用户注册后：创建OAuth绑定记录
     */
    public function afterRegister(array $data): void
    {
        if (!empty($data['oauth_type']) && !empty($data['oauth_openid'])) {
            Db::name('member_oauth')->insert([
                'member_id' => $data['user_id'],
                'provider'  => $data['oauth_type'],
                'openid'    => $data['oauth_openid'],
                'nickname'  => $data['oauth_nickname'] ?? '',
                'avatar'    => $data['oauth_avatar'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 处理GitHub OAuth回调
     */
    public function handleGithubCallback()
    {
        $code = request()->get('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => '缺少授权码']);
        }

        $config = require __DIR__ . '/../config.php';
        $settings = $config['settings'] ?? [];

        // 用code换取access_token
        $tokenUrl = 'https://github.com/login/oauth/access_token';
        $tokenResp = $this->httpPost($tokenUrl, [
            'client_id'     => $settings['github_client_id'],
            'client_secret' => $settings['github_secret'],
            'code'          => $code,
        ]);

        if (empty($tokenResp['access_token'])) {
            return json(['code' => 1, 'msg' => '获取access_token失败']);
        }

        // 获取用户信息
        $userResp = $this->httpGet('https://api.github.com/user', [
            'Authorization: Bearer ' . $tokenResp['access_token'],
        ]);

        // 实际场景中处理用户绑定/注册流程
        return Redirect::to('/login')->with('oauth_type', 'github');
    }

    /**
     * 处理微信OAuth回调
     */
    public function handleWechatCallback()
    {
        $code = request()->get('code', '');
        // 微信OAuth回调处理逻辑
        return Redirect::to('/login')->with('oauth_type', 'wechat');
    }

    /**
     * 验证OAuth Token
     */
    protected function verifyOauthToken(string $type, string $token): ?array
    {
        // 实际场景中调用各平台的验证接口
        return ['openid' => $token, 'nickname' => ''];
    }

    protected function httpPost(string $url, array $data): array
    {
        // HTTP请求封装
        return [];
    }

    protected function httpGet(string $url, array $headers = []): array
    {
        // HTTP请求封装
        return [];
    }
}
