<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Member as MemberModel;
use app\common\model\MemberOauth as MemberOauthModel;
use app\common\service\CacheService;
use app\common\service\ConfigService;
use app\common\service\PointsService;
use app\common\service\MemberLevelService;
use app\common\service\oauth\OauthProviderInterface;
use app\common\service\oauth\WechatOauthProvider;
use app\common\service\oauth\GiteeOauthProvider;
use think\facade\Cache;
use think\facade\Cookie;

use app\common\service\oauth\QqOauthProvider;

/**
 * OAuth统一控制器
 * V2.4重构：通过provider路由参数分发到不同OAuth Provider
 */
class OauthController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /** @var array 支持的Provider映射 */
    protected array $providerMap = [
        'wechat' => WechatOauthProvider::class,
        'qq'     => QqOauthProvider::class,
        'gitee'  => GiteeOauthProvider::class,
    ];

    /**
     * 跳转到第三方授权页
     * GET /oauth/{provider}
     */
    public function redirect(string $provider)
    {
        $oauthProvider = $this->getProvider($provider);
        if (!$oauthProvider) {
            return $this->error('不支持的登录方式');
        }

        // 检查OAuth是否已配置
        if (!$this->isOAuthConfigured($provider)) {
            return $this->error('该登录方式暂未开放');
        }

        $state = md5(uniqid((string) rand(), true));
        Cache::tag(CacheService::TAG_MEMBER)->set('oauth_state_' . $state, $provider, 300);

        $authUrl = $oauthProvider->getAuthUrl($state);
        return redirect($authUrl);
    }

    /**
     * OAuth授权回调
     * GET /oauth/{provider}/callback
     */
    public function callback(string $provider)
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        if (empty($code)) {
            return $this->error('授权码为空');
        }

        // 验证state
        $cachedProvider = Cache::get('oauth_state_' . $state);
        if (!$cachedProvider || $cachedProvider !== $provider) {
            return $this->error('授权状态异常，请重试');
        }
        Cache::delete('oauth_state_' . $state);

        $oauthProvider = $this->getProvider($provider);
        if (!$oauthProvider) {
            return $this->error('不支持的登录方式');
        }

        try {
            // 获取Access Token
            $tokenData = $oauthProvider->getAccessToken($code);

            // 获取用户信息
            $rawUser = $oauthProvider->getUserInfo($tokenData['access_token'], $tokenData['openid'] ?? '');
            $userData = $oauthProvider->mapUserData($rawUser);

            if (empty($userData['openid'])) {
                return $this->error('获取用户标识失败');
            }

            // 处理登录/注册
            return $this->handleOauthLogin($provider, $userData, $tokenData);

        } catch (\Exception $e) {
            return $this->error('授权失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理OAuth登录/注册统一流程
     */
    protected function handleOauthLogin(string $provider, array $userData, array $tokenData)
    {
        $openid = $userData['openid'];
        $unionid = $userData['unionid'] ?? '';

        // 优先通过unionid查找（微信多应用场景）
        $oauth = null;
        if ($unionid) {
            $oauth = MemberOauthModel::where('provider', $provider)
                ->where('unionid', $unionid)
                ->find();
        }
        if (!$oauth) {
            $oauth = MemberOauthModel::where('provider', $provider)
                ->where('openid', $openid)
                ->find();
        }

        if ($oauth) {
            // 已有绑定，更新Token并登录
            $oauth->access_token = $tokenData['access_token'] ?? $oauth->access_token;
            $oauth->refresh_token = $tokenData['refresh_token'] ?? $oauth->refresh_token;
            $oauth->expire_time = time() + ($tokenData['expires_in'] ?? 7200);
            $oauth->save();

            $member = MemberModel::find($oauth->member_id);
        } else {
            // 新用户，自动注册
            $member = new MemberModel;
            $member->save([
                'username' => $provider . '_' . $openid,
                'email'    => '',
                'password' => bin2hex(random_bytes(16)),
                'nickname' => $userData['nickname'] ?? ($provider . '用户'),
                'avatar'   => $userData['avatar'] ?? '',
                'status'   => 1,
            ]);

            // V2.4: 赋予默认等级
            $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->find();
            if ($defaultLevel) {
                $member->level_id = $defaultLevel->id;
                $member->save();
            }

            // V2.4: 注册奖励积分
            $registerPoints = (int) ConfigService::get('points_register', 50);
            if ($registerPoints > 0) {
                try {
                    PointsService::add($member->id, $registerPoints, 'register', 0, '注册奖励');
                } catch (\Throwable) {}
            }

            // 绑定OAuth
            $oauth = new MemberOauthModel;
            $oauth->save([
                'member_id'     => $member->id,
                'provider'      => $provider,
                'openid'        => $openid,
                'unionid'       => $unionid,
                'access_token'  => $tokenData['access_token'] ?? '',
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'expire_time'   => time() + ($tokenData['expires_in'] ?? 7200),
                'nickname'      => $userData['nickname'] ?? '',
                'avatar'        => $userData['avatar'] ?? '',
            ]);
        }

        if (!$member || $member->status != 1) {
            return $this->error('账号异常');
        }

        // 写入登录态
        $this->setMemberLogin($member);

        return redirect('/member/profile');
    }

    /**
     * 设置会员登录态
     */
    protected function setMemberLogin(MemberModel $member): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = sha1($token);
        $cacheKey = 'i8j_member_token_' . $hash;
        $memberData = [
            'id'       => $member->id,
            'username' => $member->username,
            'nickname' => $member->nickname,
            'avatar'   => $member->avatar,
        ];

        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey, $memberData, 7200);
        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey . '_id', $member->id, 7200);
        Cookie::set('member_token', $token, ['expire' => 7200, 'httponly' => true]);

        // 更新登录信息
        $member->last_login_time = time();
        $member->last_login_ip = request()->ip();
        $member->save();
    }

    /**
     * 获取Provider实例
     */
    protected function getProvider(string $provider): ?OauthProviderInterface
    {
        if (!isset($this->providerMap[$provider])) {
            return null;
        }

        $class = $this->providerMap[$provider];
        return new $class();
    }

    /**
     * 检查OAuth是否已配置
     */
    protected function isOAuthConfigured(string $provider): bool
    {
        return match($provider) {
            'wechat' => !empty(ConfigService::get('wechat_open_appid')) && !empty(ConfigService::get('wechat_open_secret')),
            'qq'     => !empty(ConfigService::get('qq_appid')) && !empty(ConfigService::get('qq_appkey')),
            'gitee'  => !empty(ConfigService::get('gitee_client_id')) || !empty(config('oauth.gitee_client_id')),
            default  => false,
        };
    }

    // ========== 向后兼容方法 ==========

    /**
     * Gitee回调（兼容旧路由）
     * @deprecated 使用callback('gitee')替代
     */
    public function giteeCallback()
    {
        return $this->callback('gitee');
    }
}
