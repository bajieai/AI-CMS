<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Member as MemberModel;
use app\common\model\MemberOauth as MemberOauthModel;
use app\common\service\CacheService;
use app\common\service\MemberService;
use GuzzleHttp\Client;
use think\facade\Cache;
use think\facade\Cookie;
use think\Request;

/**
 * OAuth回调控制器
 * Gitee登录回调放在home应用，避开api全局AdminAuth中间件
 */
class OauthController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * Gitee OAuth回调
     */
    public function giteeCallback(Request $request)
    {
        $code = $request->get('code', '');
        if (empty($code)) {
            return $this->error('授权码为空');
        }

        $client = new Client(['timeout' => 10]);

        // 1. 获取access_token
        try {
            $tokenRes = $client->post('https://gitee.com/oauth/token', [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'client_id'     => config('oauth.gitee_client_id'),
                    'client_secret' => config('oauth.gitee_client_secret'),
                    'redirect_uri'  => url('/home/oauth/gitee_callback', [], true, true),
                ],
            ]);
            $tokenData = json_decode((string) $tokenRes->getBody(), true);
            if (empty($tokenData['access_token'])) {
                return $this->error('获取Token失败');
            }
        } catch (\Exception $e) {
            return $this->error('Gitee接口异常: ' . $e->getMessage());
        }

        // 2. 获取用户信息
        try {
            $userRes = $client->get('https://gitee.com/api/v5/user', [
                'headers' => ['Authorization' => 'token ' . $tokenData['access_token']],
            ]);
            $userData = json_decode((string) $userRes->getBody(), true);
        } catch (\Exception $e) {
            return $this->error('获取用户信息失败');
        }

        $openid = (string) ($userData['id'] ?? '');
        if (empty($openid)) {
            return $this->error('用户标识为空');
        }

        // 3. 查找或创建会员
        $oauth = MemberOauthModel::where('provider', 'gitee')->where('openid', $openid)->find();

        if ($oauth) {
            $member = MemberModel::find($oauth->member_id);
        } else {
            // 创建新会员
            $member = new MemberModel;
            $member->save([
                'username' => 'gitee_' . $openid,
                'email'    => $userData['email'] ?? '',
                'password' => bin2hex(random_bytes(16)),
                'nickname' => $userData['name'] ?? $userData['login'] ?? 'Gitee用户',
                'avatar'   => $userData['avatar_url'] ?? '',
                'status'   => 1,
            ]);

            // 绑定OAuth
            $oauth = new MemberOauthModel;
            $oauth->save([
                'member_id'   => $member->id,
                'provider'    => 'gitee',
                'openid'      => $openid,
                'access_token'=> $tokenData['access_token'] ?? '',
                'refresh_token'=> $tokenData['refresh_token'] ?? '',
                'expire_time' => time() + ($tokenData['expires_in'] ?? 7200),
                'nickname'    => $userData['name'] ?? $userData['login'] ?? '',
                'avatar'      => $userData['avatar_url'] ?? '',
            ]);
        }

        // 4. 登录态写入
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

        return redirect('/member/profile');
    }
}