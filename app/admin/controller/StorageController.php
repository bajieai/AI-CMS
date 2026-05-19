<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Config;

/**
 * 存储配置管理控制器 - V2.6
 */
class StorageController extends AdminBaseController
{
    /**
     * 存储配置页面
     */
    public function config()
    {
        $this->app->view->assign('menuActive', 'storage_config');

        $config = [
            'driver' => Config::get('storage.default', 'local'),
            'oss_access_key_id' => Config::get('storage.drivers.oss.access_key_id', ''),
            'oss_access_key_secret' => Config::get('storage.drivers.oss.access_key_secret', ''),
            'oss_bucket' => Config::get('storage.drivers.oss.bucket', ''),
            'oss_endpoint' => Config::get('storage.drivers.oss.endpoint', ''),
            'oss_cdn_domain' => Config::get('storage.drivers.oss.cdn_domain', ''),
            'cos_secret_id' => Config::get('storage.drivers.cos.secret_id', ''),
            'cos_secret_key' => Config::get('storage.drivers.cos.secret_key', ''),
            'cos_bucket' => Config::get('storage.drivers.cos.bucket', ''),
            'cos_region' => Config::get('storage.drivers.cos.region', ''),
            'cos_cdn_domain' => Config::get('storage.drivers.cos.cdn_domain', ''),
        ];

        $this->assign('config', $config);
        return $this->view('/storage_config');
    }

    /**
     * 保存存储配置
     */
    public function saveConfig()
    {
        $data = $this->request->post();

        $configPath = config_path() . 'storage.php';
        $current = include $configPath;

        if (isset($data['driver'])) {
            $current['default'] = $data['driver'];
        }

        $ossKeys = ['access_key_id', 'access_key_secret', 'bucket', 'endpoint', 'cdn_domain'];
        foreach ($ossKeys as $key) {
            if (isset($data['oss_' . $key])) {
                $current['drivers']['oss'][$key] = $data['oss_' . $key];
            }
        }

        $cosKeys = ['secret_id', 'secret_key', 'bucket', 'region', 'cdn_domain'];
        foreach ($cosKeys as $key) {
            if (isset($data['cos_' . $key])) {
                $current['drivers']['cos'][$key] = $data['cos_' . $key];
            }
        }

        $content = "<?php\nreturn " . var_export($current, true) . ";\n";
        $result = file_put_contents($configPath, $content);

        if ($result === false) {
            return json(['code' => 1, 'msg' => '配置文件写入失败，请检查目录权限']);
        }

        $this->recordLog('保存存储配置', '', array_keys($data));
        return json(['code' => 0, 'msg' => '保存成功']);
    }
}
