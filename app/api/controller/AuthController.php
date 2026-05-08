<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\ApiController;
use app\common\model\Member;
use think\facade\Config;
use think\facade\Log;

/**
 * 小程序授权接口 - V2.9 M4新增
 *
 * 路由前缀：/api/v1/auth
 */
class AuthController extends ApiController
{
    /**
     * 微信小程序登录
     * POST /auth/wxLogin
     * Body: {code: "wx.login()返回的code"}
     */
    public function wxLogin(): \think\Response
    {
        $code = $this->request->param('code', '');
        if (empty($code)) {
            return json(['code' => 1, 'msg' => 'code不能为空']);
        }

        // 调用微信接口获取 openid/session_key/unionid
        $appId = Config::get('wechat.mini_appid', '');
        $appSecret = Config::get('wechat.mini_appsecret', '');

        if (empty($appId) || empty($appSecret)) {
            return json(['code' => 1, 'msg' => '微信小程序配置缺失']);
        }

        $url = 'https://api.weixin.qq.com/sns/jscode2session?' . http_build_query([
            'appid'      => $appId,
            'secret'     => $appSecret,
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
        ]);

        try {
            $wxResult = json_decode(file_get_contents($url), true);

            if (empty($wxResult['openid'])) {
                return json(['code' => 1, 'msg' => '微信登录失败: ' . ($wxResult['errmsg'] ?? '未知错误')]);
            }

            $openId   = $wxResult['openid'];
            $unionId = $wxResult['unionid'] ?? '';
            $sessionKey = $wxResult['session_key'] ?? '';

            // 查找或创建会员
            $member = Member::where('wx_openid', $openId)->find();
            if (!$member && $unionId) {
                $member = Member::where('wx_unionid', $unionId)->find();
            }

            if (!$member) {
                // 新用户注册
                $member = Member::create([
                    'username'    => 'wx_' . substr(md5($openId), 0, 16),
                    'password'    => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'nickname'   => '微信用户',
                    'wx_openid'   => $openId,
                    'wx_unionid'  => $unionId,
                    'register_way' => 'wechat_miniprogram',
                    'status'       => 1,
                    'regdate'      => date('Y-m-d H:i:s'),
                ]);

                // 新用户事件：触发新人券发放（M6联动）
                $this->triggerNewbieCoupon($member->id);

            } else {
                // 更新 openid/unionid
                $updateData = [];
                if (empty($member->wx_openid)) {
                    $updateData['wx_openid'] = $openId;
                }
                if ($unionId && empty($member->wx_unionid)) {
                    $updateData['wx_unionid'] = $unionId;
                }
                if (!empty($updateData)) {
                    $member->save($updateData);
                }
            }

            // 生成 token（使用现有的API token机制）
            $token = $this->generateToken($member->id);

            return json([
                'code' => 0,
                'msg'  => '登录成功',
                'data' => [
                    'token'      => $token,
                    'member_id'  => $member->id,
                    'nickname'   => $member->nickname,
                    'avatar'     => $member->avatar,
                    'is_new'     => (int) ($member->create_time >= date('Y-m-d H:i:s', strtotime('-5 minutes'))) ? 1 : 0,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[AuthController::wxLogin] ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '登录失败，请重试']);
        }
    }

    /**
     * 触发新人券发放（M6联动）
     */
    private function triggerNewbieCoupon(int $memberId): void
    {
        try {
            $enabled = Config::get('coupon.newbie_enabled', 1);
            if (!$enabled) return;

            $templateId = (int) Config::get('coupon.newbie_template_id', 0);
            if ($templateId <= 0) return;

            // 异步触发，不阻塞登录流程
            \app\common\service\CouponService::sendNewbieCoupon($memberId, $templateId);
        } catch (\Throwable $e) {
            Log::warning('[triggerNewbieCoupon] ' . $e->getMessage());
        }
    }

    /**
     * 生成API Token（HMAC模式，复用现有机制）
     */
    private function generateToken(int $memberId): string
    {
        $member = Member::find($memberId);
        if (!$member) {
            throw new \Exception('会员不存在');
        }

        // 复用现有 token 生成逻辑（与 ApiController 一致）
        $plain = $member->id . '|' . $member->username . '|' . time();
        $token = hash_hmac('sha256', $plain, Config::get('api.hmac_secret', 'default_secret'));

        // 存储到 Redis/缓存
        $cacheKey = 'api_token:' . $token;
        cache($cacheKey, ['member_id' => $memberId, 'username' => $member->username], 86400 * 7);

        return $token;
    }
}
