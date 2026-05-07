<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\PublishPlatform;
use app\common\service\publish\ToutiaoPlatform;
use think\facade\Log;

/**
 * 头条号OAuth回调控制器 - V2.7
 */
class ToutiaoOAuthController extends FrontBaseController
{
    /**
     * OAuth授权回调
     * 路由: /oauth/toutiao/callback
     */
    public function callback()
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');
        $error = $this->request->get('error', '');

        if ($error) {
            return $this->error('授权失败: ' . $this->request->get('error_description', '用户取消授权'));
        }

        if (empty($code) || empty($state)) {
            return $this->error('授权参数缺失');
        }

        // state 格式: platform_id|随机码
        $parts = explode('|', $state);
        $platformId = (int) ($parts[0] ?? 0);

        $platform = PublishPlatform::find($platformId);
        if (!$platform || $platform->name !== 'toutiao') {
            return $this->error('平台配置不存在');
        }

        $config = $platform->config_json;
        $clientKey = $config['client_key'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $redirectUri = (string) url('/oauth/toutiao/callback', [], true, true);

        try {
            $tokenData = ToutiaoPlatform::fetchToken($clientKey, $clientSecret, $code, $redirectUri);

            $config['access_token'] = $tokenData['access_token'];
            $config['refresh_token'] = $tokenData['refresh_token'] ?? '';
            $config['expires_in'] = time() + ($tokenData['expires_in'] ?? 7200);
            $config['open_id'] = $tokenData['open_id'] ?? '';

            $platform->config_json = $config;
            $platform->save();

            // 关闭弹窗并提示成功
            return response('<script>window.opener && window.opener.postMessage({type:"toutiao_oauth",success:true}, "*");window.close();</script>');
        } catch (\Exception $e) {
            Log::error('头条号OAuth回调处理失败: ' . $e->getMessage());
            return response('<script>window.opener && window.opener.postMessage({type:"toutiao_oauth",success:false,msg:"' . addslashes($e->getMessage()) . '"}, "*");window.close();</script>');
        }
    }
}
