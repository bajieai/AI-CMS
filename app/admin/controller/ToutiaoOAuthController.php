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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PublishPlatform;
use app\common\service\publish\ToutiaoPlatform;

/**
 * 头条号OAuth授权控制器 - V2.7 Sprint2
 * 处理OAuth回调、保存Token
 */
class ToutiaoOAuthController extends AdminBaseController
{
    /**
     * 发起授权跳转
     */
    public function authorize()
    {
        $platform = PublishPlatform::where('name', 'toutiao')->find();
        if (!$platform) {
            return $this->error('请先添加头条号发布平台配置');
        }

        $config = $platform->config_json;
        $clientKey = $config['client_key'] ?? '';
        if (empty($clientKey)) {
            return $this->error('Client Key 未配置');
        }

        $redirectUri = request()->domain() . '/admin/toutiaoOAuth/callback';
        $state = md5(uniqid((string) mt_rand(), true));
        session('toutiao_oauth_state', $state);

        $authUrl = ToutiaoPlatform::getAuthUrl($clientKey, $redirectUri, $state);
        return redirect($authUrl);
    }

    /**
     * OAuth回调处理
     */
    public function callback()
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');
        $savedState = session('toutiao_oauth_state');

        if (empty($code)) {
            return $this->error('授权失败：未获取到授权码');
        }
        if ($state !== $savedState) {
            return $this->error('授权失败：State校验失败');
        }

        $platform = PublishPlatform::where('name', 'toutiao')->find();
        if (!$platform) {
            return $this->error('发布平台配置不存在');
        }

        $config = $platform->config_json;
        $clientKey = $config['client_key'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';

        try {
            $redirectUri = request()->domain() . '/admin/toutiaoOAuth/callback';
            $tokenData = ToutiaoPlatform::fetchToken($clientKey, $clientSecret, $code, $redirectUri);

            $config['access_token'] = $tokenData['access_token'];
            $config['refresh_token'] = $tokenData['refresh_token'] ?? '';
            $config['expires_in'] = time() + ($tokenData['expires_in'] ?? 7200);
            $config['open_id'] = $tokenData['open_id'] ?? '';

            $platform->config_json = $config;
            $platform->save();

            return $this->success('头条号授权成功');
        } catch (\Exception $e) {
            return $this->error('授权失败：' . $e->getMessage());
        }
    }
}
