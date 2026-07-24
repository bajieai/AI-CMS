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

namespace app\common\service\mini;

use app\common\model\MiniConfig;
use app\common\model\Member;
use think\facade\Cache;
use think\facade\Http;

/**
 * MINI-1 API基础架构
 * 小程序登录/Token/微信API调用
 */
class MiniApiService
{
    /** Access Token有效期: 2小时 */
    protected const ACCESS_TTL = 7200;

    /** Refresh Token有效期: 30天 */
    protected const REFRESH_TTL = 2592000;

    /**
     * 微信code登录
     */
    public function loginByCode(string $code): array
    {
        if (empty($code)) {
            return ['code' => 1, 'msg' => 'code不能为空', 'data' => null];
        }

        $result = $this->callWechatApi($code);
        if (!isset($result['openid'])) {
            return ['code' => 1, 'msg' => $result['errmsg'] ?? '微信登录失败', 'data' => null];
        }

        $openid = $result['openid'];
        $unionid = $result['unionid'] ?? '';

        // 查找或创建用户
        $member = Member::where('wechat_openid', $openid)->find();
        if (!$member) {
            $member = Member::create([
                'username'        => 'wx_' . substr($openid, 0, 16),
                'nickname'        => '微信用户',
                'wechat_openid'   => $openid,
                'wechat_unionid'  => $unionid,
                'status'          => 1,
                'mini_login_time' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $member->mini_login_time = date('Y-m-d H:i:s');
            if ($unionid && empty($member->wechat_unionid)) {
                $member->wechat_unionid = $unionid;
            }
            $member->save();
        }

        $tokens = $this->generateToken((int) $member->id);

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'user_id'        => (int) $member->id,
                'nickname'       => $member->nickname,
                'avatar'         => $member->wechat_avatar ?: '',
                'access_token'   => $tokens['access_token'],
                'refresh_token'  => $tokens['refresh_token'],
                'expires_in'     => self::ACCESS_TTL,
            ],
        ];
    }

    /**
     * 刷新Token
     */
    public function refreshToken(string $refreshToken): array
    {
        if (empty($refreshToken)) {
            return ['code' => 1, 'msg' => 'refresh_token不能为空', 'data' => null];
        }

        $userId = Cache::get('mini_refresh:' . $refreshToken);
        if (empty($userId)) {
            return ['code' => 1, 'msg' => 'refresh_token无效或已过期', 'data' => null];
        }

        // 清除旧Token
        Cache::delete('mini_refresh:' . $refreshToken);

        $tokens = $this->generateToken((int) $userId);

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in'    => self::ACCESS_TTL,
            ],
        ];
    }

    /**
     * 生成Access Token + Refresh Token
     */
    public function generateToken(int $userId): array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        Cache::set('mini_token:' . $accessToken, $userId, self::ACCESS_TTL);
        Cache::set('mini_refresh:' . $refreshToken, $userId, self::REFRESH_TTL);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * 验证Token，返回userId或false
     */
    public function verifyToken(string $token): int|false
    {
        $userId = Cache::get('mini_token:' . $token);
        return $userId ? (int) $userId : false;
    }

    /**
     * 生成唯一请求ID
     */
    public function generateRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * 调用微信jscode2session接口
     */
    public function callWechatApi(string $code): array
    {
        $appid = $this->getMiniConfig('appid');
        $secret = $this->getMiniConfig('secret');

        if (empty($appid) || empty($secret)) {
            return ['errmsg' => '小程序AppID或Secret未配置'];
        }

        $url = sprintf(
            'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
            $appid,
            $secret,
            $code
        );

        try {
            $response = Http::get($url);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (\Throwable $e) {
            return ['errmsg' => '调用微信API失败: ' . $e->getMessage()];
        }
    }

    /**
     * 从i8j_mini_config读取配置
     */
    public function getMiniConfig(string $key = ''): mixed
    {
        if ($key === '') {
            return MiniConfig::getAll();
        }
        return MiniConfig::getValue($key);
    }

    /**
     * 保存配置
     */
    public function saveMiniConfig(string $key, string $value): array
    {
        $item = MiniConfig::where('config_key', $key)->find();
        if ($item) {
            $item->config_value = $value;
            $item->save();
        } else {
            MiniConfig::create([
                'config_key'   => $key,
                'config_value' => $value,
                'config_group' => 'basic',
            ]);
        }

        return ['code' => 0, 'msg' => '保存成功', 'data' => ['key' => $key, 'value' => $value]];
    }
}
