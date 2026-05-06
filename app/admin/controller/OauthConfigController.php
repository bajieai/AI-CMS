<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Config;

/**
 * OAuth配置管理控制器 - V2.6
 * 管理第三方登录平台的应用密钥配置
 */
class OauthConfigController extends AdminBaseController
{
    /**
     * OAuth配置页面
     */
    public function index()
    {
        $this->app->view->assign('menuActive', 'oauth_config');

        $config = [
            'gitee_client_id' => Config::get('oauth.gitee_client_id', ''),
            'gitee_client_secret' => '******',
            'wechat_open_appid' => Config::get('oauth.wechat_open_appid', ''),
            'wechat_open_secret' => '******',
            'qq_appid' => Config::get('oauth.qq_appid', ''),
            'qq_appkey' => '******',
        ];

        $this->assign('config', $config);
        return $this->view('/oauth_config');
    }

    /**
     * 保存OAuth配置
     */
    public function save()
    {
        $data = $this->request->post();

        // 读取现有配置，合并更新
        $configPath = config_path() . 'oauth.php';
        $current = include $configPath;

        $updateKeys = [
            'gitee_client_id', 'gitee_client_secret',
            'wechat_open_appid', 'wechat_open_secret',
            'qq_appid', 'qq_appkey',
        ];

        foreach ($updateKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== '******') {
                $current[$key] = $data[$key];
            }
        }

        // 写入配置文件
        $content = "<?php\n// OAuth 第三方登录配置 - V2.6\n// 建议在 .env 中配置敏感信息\n\nreturn " . var_export($current, true) . ";\n";
        $result = file_put_contents($configPath, $content);

        if ($result === false) {
            return json(['code' => 1, 'msg' => '配置文件写入失败，请检查目录权限']);
        }

        $this->recordLog('保存OAuth配置', '', array_keys($data));
        return json(['code' => 0, 'msg' => '保存成功']);
    }
}
