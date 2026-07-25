<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
use app\common\service\oauth\QqOauthProvider;
use app\common\service\oauth\GitHubOauthProvider;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * OAuth统一控制器
 * V2.5完善：移动端UA检测+getMobileAuthUrl+启用开关+unionid写入修复+错误友好提示
 */
class OauthController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /** @var array 支持的Provider映射 */
    protected array $providerMap = [
        'wechat' => WechatOauthProvider::class,
        'qq'     => QqOauthProvider::class,
        'gitee'  => GiteeOauthProvider::class,
        'github' => GitHubOauthProvider::class,
    ];

    /**
     * 跳转到第三方授权页
     * GET /oauth/{provider}
     * V2.5增强：移动端UA检测+启用开关
     */
    public function redirect(string $provider)
    {
        $oauthProvider = $this->getProvider($provider);
        if (!$oauthProvider) {
            return $this->oauthError('不支持的登录方式');
        }

        // V2.5：检查是否已启用（独立开关配置）
        if (!$this->isOAuthEnabled($provider)) {
            return $this->oauthError('该登录方式暂未开放', '/member/login');
        }

        // V2.5：检查是否已配置
        if (!$this->isOAuthConfigured($provider)) {
            return $this->oauthError('该登录方式暂未配置', '/member/login');
        }

        $state = md5(uniqid((string) rand(), true));
        Cache::set('oauth_state_' . $state, $provider, 300);

        // V2.5：微信登录根据UA自动选择PC扫码/移动端H5授权
        if ($provider === 'wechat' && $this->isWechatBrowser()) {
            // 微信内浏览器 → 使用公众号授权URL（getMobileAuthUrl）
            if (method_exists($oauthProvider, 'getMobileAuthUrl')) {
                $authUrl = $oauthProvider->getMobileAuthUrl($state);
            } else {
                $authUrl = $oauthProvider->getAuthUrl($state);
            }
        } elseif ($provider === 'wechat' && $this->isMobile() && !$this->isWechatBrowser()) {
            // V2.5修复：移动端非微信浏览器 → 跳转提示页（扫码体验差）
            return redirect('/member/login')->with('error', '请在微信内打开或PC端扫码登录');
        } else {
            $authUrl = $oauthProvider->getAuthUrl($state);
        }

        return redirect($authUrl);
    }

    /**
     * OAuth授权回调
     * GET /oauth/{provider}/callback
     * V2.5增强：错误处理更友好
     */
    public function callback(string $provider)
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        // 用户取消授权
        if ($this->request->get('error') || empty($code)) {
            $errorDesc = $this->request->get('error_description', '用户取消授权');
            return $this->oauthError('授权已取消: ' . $errorDesc, '/member/login');
        }

        // 验证state
        $cachedProvider = Cache::get('oauth_state_' . $state);
        if (!$cachedProvider || $cachedProvider !== $provider) {
            return $this->oauthError('授权状态异常，请重新登录', '/member/login');
        }
        Cache::delete('oauth_state_' . $state);

        $oauthProvider = $this->getProvider($provider);
        if (!$oauthProvider) {
            return $this->oauthError('不支持的登录方式', '/member/login');
        }

        try {
            // 获取Access Token
            $tokenData = $oauthProvider->getAccessToken($code);

            // V2.5修复：QQ的openid需要单独获取
            $openid = $tokenData['openid'] ?? '';
            if (empty($openid) && $provider === 'qq' && method_exists($oauthProvider, 'getOpenId')) {
                $openid = $oauthProvider->getOpenId($tokenData['access_token']);
                $tokenData['openid'] = $openid;
            }

            // 获取用户信息
            $rawUser = $oauthProvider->getUserInfo($tokenData['access_token'], $openid);
            // V2.5修复：QQ的mapUserData需要传入openid
            if ($provider === 'qq' && method_exists($oauthProvider, 'mapUserData')) {
                $refMethod = new \ReflectionMethod($oauthProvider, 'mapUserData');
                $params = $refMethod->getParameters();
                if (count($params) > 1) {
                    $userData = $oauthProvider->mapUserData($rawUser, $openid);
                } else {
                    $userData = $oauthProvider->mapUserData($rawUser);
                    if (empty($userData['openid'])) {
                        $userData['openid'] = $openid;
                    }
                }
            } else {
                $userData = $oauthProvider->mapUserData($rawUser);
            }

            // V2.5修复：确保openid从rawUser中补充
            if (empty($userData['openid']) && !empty($openid)) {
                $userData['openid'] = $openid;
            }

            if (empty($userData['openid'])) {
                return $this->oauthError('获取用户标识失败，请重试', '/member/login');
            }

            // 处理登录/注册
            return $this->handleOauthLogin($provider, $userData, $tokenData);

        } catch (\Exception $e) {
            return $this->oauthError('授权失败: ' . $e->getMessage(), '/member/login');
        }
    }

    /**
     * 处理OAuth登录/注册统一流程
     * V2.5增强：unionid写入修复
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
            // V2.5修复：更新unionid（首次可能未写入）
            if (!empty($unionid) && empty($oauth->unionid)) {
                $oauth->unionid = $unionid;
            }
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

            // 赋予默认等级
            $defaultLevel = \app\common\model\MemberLevel::where('is_default', 1)->find();
            if ($defaultLevel) {
                $member->level_id = $defaultLevel->id;
                $member->save();
            }

            // 注册奖励积分
            $registerPoints = (int) ConfigService::get('points_register', 50);
            if ($registerPoints > 0) {
                try {
                    PointsService::add($member->id, $registerPoints, 'register', 0, '注册奖励');
                } catch (\Throwable) {}
            }

            // 绑定OAuth — V2.5修复：确保unionid写入
            $oauth = new MemberOauthModel;
            $oauth->save([
                'member_id'     => $member->id,
                'provider'      => $provider,
                'openid'        => $openid,
                'unionid'       => $unionid,  // V2.5修复：确保写入
                'access_token'  => $tokenData['access_token'] ?? '',
                'refresh_token' => $tokenData['refresh_token'] ?? '',
                'expire_time'   => time() + ($tokenData['expires_in'] ?? 7200),
                'nickname'      => $userData['nickname'] ?? '',
                'avatar'        => $userData['avatar'] ?? '',
            ]);
        }

        if (!$member || $member->status != 1) {
            return $this->oauthError('账号异常', '/member/login');
        }

        // 写入登录态
        $this->setMemberLogin($member);

        // V2.5：触发插件Hook
        \app\common\service\PluginService::fire('member.login', ['member_id' => $member->id]);

        return redirect('/member/profile');
    }

    /**
     * 设置会员登录态
     */
    protected function setMemberLogin(MemberModel $member): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = sha1($token);
        $cacheKey = 'member_token_' . $hash;
        $memberData = [
            'id'       => $member->id,
            'username' => $member->username,
            'nickname' => $member->nickname,
            'avatar'   => $member->avatar,
        ];

        Cache::set($cacheKey, $memberData, 7200);
        Cache::set($cacheKey . '_id', $member->id, 7200);
        Cookie::set('member_token', $token, ['expire' => 7200, 'httponly' => true]);

        $member->last_login_time = time();
        $member->last_login_ip = request()->ip();
        $member->save();
    }

    /**
     * 获取Provider实例
     */
    protected function getProvider(string $provider): ?OauthProviderInterface
    {
        if (!isset($this->providerMap[$provider])) return null;
        $class = $this->providerMap[$provider];
        return new $class();
    }

    /**
     * V2.5：检查OAuth是否已启用（独立开关）
     */
    protected function isOAuthEnabled(string $provider): bool
    {
        return match($provider) {
            'wechat' => (bool) ConfigService::get('oauth_wechat_enabled', 0),
            'qq'     => (bool) ConfigService::get('oauth_qq_enabled', 0),
            'gitee'  => true,
            'github' => (bool) ConfigService::get('oauth_github_enabled', 0),
            default  => false,
        };
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
            'github' => !empty(config('oauth.github.client_id')) && !empty(config('oauth.github.client_secret')),
            default  => false,
        };
    }

    /**
     * 判断是否移动端
     */
    protected function isMobile(): bool
    {
        $ua = request()->header('user-agent', '');
        return (bool) preg_match('/Android|iPhone|iPad|iPod|Mobile/i', $ua);
    }

    /**
     * 判断是否微信浏览器
     */
    protected function isWechatBrowser(): bool
    {
        $ua = request()->header('user-agent', '');
        return str_contains($ua, 'MicroMessenger');
    }

    /**
     * OAuth错误提示（支持跳转）
     * 注意：不与父类error()冲突
     */
    protected function oauthError(string $msg = '操作失败', string $redirectUrl = ''): \think\Response
    {
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => $msg]);
        }

        if (!empty($redirectUrl)) {
            return redirect($redirectUrl)->with('error', $msg);
        }

        return json(['code' => 1, 'msg' => $msg]);
    }

    /**
     * Gitee回调（兼容旧路由）
     * @deprecated 使用callback('gitee')替代
     */
    public function giteeCallback()
    {
        return $this->callback('gitee');
    }
}
