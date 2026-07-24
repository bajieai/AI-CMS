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

namespace app\api\controller\mini;

use app\api\controller\BaseController;
use app\common\model\MiniConfig;
use app\common\service\mini\MiniTemplateService;
use think\facade\Cache;

/**
 * 小程序系统API
 */
class SystemController extends BaseController
{
    /**
     * 统一JSON响应
     */
    protected function miniJson(mixed $data, string $message = 'success', int $code = 0): \think\Response
    {
        return json([
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'timestamp'  => time(),
            'request_id' => bin2hex(random_bytes(8)),
        ]);
    }

    /**
     * 系统配置 (公开)
     */
    public function config(): \think\Response
    {
        $data = Cache::remember('mini:system_config', function () {
            return [
                'mini_name'       => MiniConfig::getValue('mini_name', ''),
                'theme_color'     => MiniConfig::getValue('theme_color', '#0d6efd'),
                'enable_comment'  => (int) MiniConfig::getValue('enable_comment', '1'),
                'enable_favorite' => (int) MiniConfig::getValue('enable_favorite', '1'),
                'enable_like'     => (int) MiniConfig::getValue('enable_like', '1'),
            ];
        }, 3600);

        return $this->miniJson($data);
    }

    /**
     * 菜单配置 (导航栏)
     */
    public function menu(): \think\Response
    {
        $data = Cache::remember('mini:menu', function () {
            $template = new MiniTemplateService();
            $pageConfig = $template->getPageConfig('index');
            return $pageConfig['components'] ?? [];
        }, 3600);

        return $this->miniJson($data);
    }

    /**
     * 广告位
     */
    public function ad(): \think\Response
    {
        $position = $this->request->param('position', 'home_banner');
        $data = Cache::remember('mini:ad:' . $position, function () use ($position) {
            return \think\facade\Db::name('ad')
                ->where('position', $position)
                ->where('status', 1)
                ->field('id,title,image,url')
                ->order('sort', 'asc')
                ->select()
                ->toArray();
        }, 300);

        return $this->miniJson($data);
    }

    /**
     * 站点信息
     */
    public function site(): \think\Response
    {
        $data = Cache::remember('mini:site', function () {
            $configs = \think\facade\Db::name('config')
                ->whereIn('name', ['site_name', 'site_logo', 'site_description', 'site_icp'])
                ->column('value', 'name');
            return [
                'site_name'        => $configs['site_name'] ?? '',
                'site_logo'        => $configs['site_logo'] ?? '',
                'site_description' => $configs['site_description'] ?? '',
                'site_icp'         => $configs['site_icp'] ?? '',
            ];
        }, 3600);

        return $this->miniJson($data);
    }

    /**
     * 更新时间 (用于客户端检查更新)
     */
    public function updateTime(): \think\Response
    {
        $data = [
            'content_update_time' => Cache::get('mini_content_update_time', 0),
            'config_update_time'  => Cache::get('mini_config_update_time', 0),
            'server_time'         => time(),
        ];
        return $this->miniJson($data);
    }

    /**
     * 版本信息
     */
    public function version(): \think\Response
    {
        $data = [
            'version'       => MiniConfig::getValue('version', '1.0.0'),
            'min_client'    => MiniConfig::getValue('min_client_version', '1.0.0'),
            'update_url'    => MiniConfig::getValue('update_url', ''),
            'force_update'  => (int) MiniConfig::getValue('force_update', '0'),
        ];
        return $this->miniJson($data);
    }
}
