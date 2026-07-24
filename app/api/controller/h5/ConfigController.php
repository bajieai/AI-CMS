<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\response\Json;

/**
 * H5配置接口
 */
class ConfigController extends H5BaseController
{
    /**
     * 获取站点配置
     */
    public function site(): Json
    {
        $config = Db::name('config')->where('config_key', 'in', ['site_name', 'site_logo', 'site_description', 'site_icp', 'site_contact'])->column('config_value', 'config_key');
        return $this->success($config);
    }

    /**
     * 获取H5主题配置
     */
    public function theme(): Json
    {
        $theme = \app\common\service\h5\H5ConfigService::getTheme();
        $features = \app\common\service\h5\H5ConfigService::getFeatures();
        return $this->success(['theme' => $theme, 'features' => $features]);
    }

    /**
     * 获取H5 PWA配置
     */
    public function pwa(): Json
    {
        $pwa = \app\common\service\h5\H5ConfigService::getPwa();
        return $this->success($pwa);
    }
}
