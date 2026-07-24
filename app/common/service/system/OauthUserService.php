<?php
declare(strict_types=1);

namespace app\common\service\system;

use app\common\model\OauthUser;
use app\common\model\Member;
use think\facade\Cache;
use think\facade\Log;

/**
 * 第三方登录用户服务
 * V2.9.38 SYS-INTEG-1
 * 复用V2.9.27已有OauthController的4种Provider适配器
 */
class OauthUserService
{
    protected const CACHE_TAG = 'oauth_user';
    protected const CACHE_TTL = 3600;

    /**
     * OAuth登录跳转
     */
    public function oauthLogin(string $provider): string
    {
        $config = $this->getProviderConfig($provider);
        if (!$config) throw new \RuntimeException("Provider {$provider} not configured");
        
        $state = md5(uniqid() . session_id());
        Cache::set('oauth_state_' . $state, ['provider' => $provider, 'ip' => request()->ip()], 600);
        
        $authUrls = [
            'wechat' => 'https://open.weixin.qq.com/connect/qrconnect?appid=' . $config['app_id'] . '&redirect_uri=' . urlencode($config['callback']) . '&response_type=code&scope=snsapi_login&state=' . $state,
            'qq' => 'https://graph.qq.com/oauth2.0/authorize?client_id=' . $config['app_id'] . '&redirect_uri=' . urlencode($config['callback']) . '&response_type=code&scope=get_user_info&state=' . $state,
            'github' => 'https://github.com/login/oauth/authorize?client_id=' . $config['app_id'] . '&redirect_uri=' . urlencode($config['callback']) . '&scope=user:email&state=' . $state,
            'weibo' => 'https://api.weibo.com/oauth2/authorize?client_id=' . $config['app_id'] . '&redirect_uri=' . urlencode($config['callback']) . '&state=' . $state,
        ];
        
        return $authUrls[$provider] ?? '';
    }

    /**
     * OAuth回调处理
     */
    public function oauthCallback(string $provider, string $code, string $state): array
    {
        // 验证State
        $stateData = Cache::get('oauth_state_' . $state);
        if (!$stateData || $stateData['provider'] !== $provider) {
            throw new \RuntimeException('Invalid state parameter');
        }
        Cache::delete('oauth_state_' . $state);

        // 获取Access Token
        $tokenInfo = $this->getAccessToken($provider, $code);
        
        // 获取用户信息
        $userInfo = $this->getUserInfo($provider, $tokenInfo['access_token'], $tokenInfo['openid'] ?? '');
        
        // 查找是否已绑定
        $oauthUser = OauthUser::where('oauth_provider', $provider)
            ->where('oauth_openid', $userInfo['openid'])
            ->find();
        
        if ($oauthUser) {
            // 已绑定: 更新登录信息
            $oauthUser->save([
                'oauth_nickname' => $userInfo['nickname'] ?? '',
                'oauth_avatar' => $userInfo['avatar'] ?? '',
                'oauth_data' => $userInfo,
                'last_login_time' => date('Y-m-d H:i:s'),
                'last_login_ip' => request()->ip(),
                'login_count' => $oauthUser->login_count + 1,
            ]);
            $memberId = $oauthUser->member_id;
        } else {
            // 未绑定: 自动注册新会员
            $memberId = $this->autoRegister($provider, $userInfo);
        }
        
        return [
            'member_id' => $memberId,
            'provider' => $provider,
            'is_new_user' => !$oauthUser,
        ];
    }

    /**
     * 绑定OAuth账号
     */
    public function bindOauth(int $memberId, string $provider, array $userInfo): bool
    {
        $exists = OauthUser::where('member_id', $memberId)->where('oauth_provider', $provider)->find();
        if ($exists) throw new \RuntimeException('Already bound');
        
        $oauthUser = new OauthUser();
        $oauthUser->save([
            'member_id' => $memberId,
            'oauth_provider' => $provider,
            'oauth_openid' => $userInfo['openid'] ?? '',
            'oauth_unionid' => $userInfo['unionid'] ?? '',
            'oauth_nickname' => $userInfo['nickname'] ?? '',
            'oauth_avatar' => $userInfo['avatar'] ?? '',
            'oauth_data' => $userInfo,
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => request()->ip(),
            'login_count' => 1,
            'status' => 1,
        ]);
        Cache::clear();
        return true;
    }

    /**
     * 解绑OAuth账号
     */
    public function unbindOauth(int $memberId, string $provider): bool
    {
        $oauthUser = OauthUser::where('member_id', $memberId)->where('oauth_provider', $provider)->find();
        if (!$oauthUser) return false;
        $oauthUser->delete();
        Cache::clear();
        return true;
    }

    /**
     * 获取绑定列表
     */
    public function getBindList(int $memberId): array
    {
        return OauthUser::where('member_id', $memberId)->select()->toArray();
    }

    /**
     * 获取OAuth统计
     */
    public function getOauthStats(): array
    {
        return Cache::remember('oauth_stats', function() {
            $total = OauthUser::count();
            $byProvider = [];
            foreach (['wechat', 'qq', 'github', 'weibo'] as $provider) {
                $byProvider[$provider] = OauthUser::where('oauth_provider', $provider)->count();
            }
            $todayLogins = OauthUser::whereTime('last_login_time', 'today')->count();
            $successRate = $total > 0 ? round($todayLogins / max($total, 1) * 100, 1) : 0;
            return [
                'total_binds' => $total,
                'by_provider' => $byProvider,
                'today_logins' => $todayLogins,
                'success_rate' => $successRate,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 自动注册会员
     */
    protected function autoRegister(string $provider, array $userInfo): int
    {
        $member = new Member();
        $member->save([
            'username' => $provider . '_' . substr($userInfo['openid'] ?? uniqid(), 0, 10),
            'nickname' => $userInfo['nickname'] ?? '用户' . rand(1000, 9999),
            'avatar' => $userInfo['avatar'] ?? '',
            'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'status' => 1,
            'reg_time' => time(),
            'reg_ip' => request()->ip(),
        ]);
        $memberId = (int) $member->id;
        
        // 创建绑定记录
        $this->bindOauth($memberId, $provider, $userInfo);
        
        return $memberId;
    }

    /**
     * 获取Provider配置
     */
    protected function getProviderConfig(string $provider): ?array
    {
        $config = \think\facade\Config::get('oauth.' . $provider, []);
        if (empty($config['app_id']) || empty($config['app_secret'])) return null;
        $config['callback'] = $config['callback'] ?? (request()->root(true) . '/oauth/callback/' . $provider);
        return $config;
    }

    /**
     * 获取Access Token
     */
    protected function getAccessToken(string $provider, string $code): array
    {
        $config = $this->getProviderConfig($provider);
        $tokenUrls = [
            'wechat' => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['app_id'] . '&secret=' . $config['app_secret'] . '&code=' . $code . '&grant_type=authorization_code',
            'qq' => 'https://graph.qq.com/oauth2.0/token?client_id=' . $config['app_id'] . '&client_secret=' . $config['app_secret'] . '&code=' . $code . '&grant_type=authorization_code&redirect_uri=' . urlencode($config['callback']),
            'github' => 'https://github.com/login/oauth/access_token',
            'weibo' => 'https://api.weibo.com/oauth2/access_token',
        ];
        
        // 简化: 使用file_get_contents或curl
        $url = $tokenUrls[$provider] ?? '';
        $response = $this->httpGet($url, $provider === 'github' ? ['Accept' => 'application/json'] : []);
        $result = json_decode($response, true) ?: [];
        
        return [
            'access_token' => $result['access_token'] ?? '',
            'openid' => $result['openid'] ?? $result['uid'] ?? '',
        ];
    }

    /**
     * 获取用户信息
     */
    protected function getUserInfo(string $provider, string $accessToken, string $openid): array
    {
        $userUrls = [
            'wechat' => 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken . '&openid=' . $openid,
            'qq' => 'https://graph.qq.com/user/get_user_info?access_token=' . $accessToken . '&oauth_consumer_key=' . $openid . '&openid=' . $openid,
            'github' => 'https://api.github.com/user?access_token=' . $accessToken,
            'weibo' => 'https://api.weibo.com/2/users/show.json?access_token=' . $accessToken . '&uid=' . $openid,
        ];
        
        $url = $userUrls[$provider] ?? '';
        $response = $this->httpGet($url);
        $data = json_decode($response, true) ?: [];
        
        // 标准化字段
        return [
            'openid' => $openid,
            'unionid' => $data['unionid'] ?? '',
            'nickname' => $data['nickname'] ?? $data['name'] ?? $data['login'] ?? '',
            'avatar' => $data['headimgurl'] ?? $data['avatar_url'] ?? $data['avatar'] ?? $data['profile_image_url'] ?? '',
            'raw' => $data,
        ];
    }

    protected function httpGet(string $url, array $headers = []): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }
}
