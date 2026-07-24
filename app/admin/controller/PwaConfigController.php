<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Db;

/**
 * PWA配置管理控制器 — V2.9.28 MO-5
 */
class PwaConfigController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * PWA配置页面
     */
    public function index()
    {
        $config = [
            'pwa_enabled' => Db::name('config')->where('name', 'pwa_enabled')->value('value') ?: '1',
            'pwa_app_name' => Db::name('config')->where('name', 'pwa_app_name')->value('value') ?: 'AI-CMS',
            'pwa_app_short_name' => Db::name('config')->where('name', 'pwa_app_short_name')->value('value') ?: 'AI-CMS',
            'pwa_theme_color' => Db::name('config')->where('name', 'pwa_theme_color')->value('value') ?: '#0d6efd',
            'pwa_bg_color' => Db::name('config')->where('name', 'pwa_bg_color')->value('value') ?: '#ffffff',
            'pwa_push_enabled' => Db::name('config')->where('name', 'pwa_push_enabled')->value('value') ?: '0',
        ];

        $this->assign([
            'config' => $config,
            'menuActive' => 'pwa_config',
        ]);

        return $this->view('/pwa/config');
    }

    /**
     * 保存PWA配置
     */
    public function save()
    {
        $keys = ['pwa_enabled', 'pwa_app_name', 'pwa_app_short_name', 'pwa_theme_color', 'pwa_bg_color', 'pwa_push_enabled'];

        foreach ($keys as $key) {
            $value = $this->request->post($key, '');
            $existing = Db::name('config')->where('name', $key)->find();
            if ($existing) {
                Db::name('config')->where('name', $key)->update(['value' => $value]);
            } else {
                Db::name('config')->insert(['name' => $key, 'value' => $value, 'group' => 'pwa']);
            }
        }

        $this->recordLog('保存PWA配置', '');
        return $this->success('PWA配置已保存');
    }
}
