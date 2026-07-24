<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\CdnOptimizeService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PERF-3: CDN配置控制器
 */
class CdnController extends AdminBaseController
{
    protected CdnOptimizeService $cdnService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->cdnService = new CdnOptimizeService();
    }

    public function index()
    {
        $config = $this->cdnService->getConfig();
        $stats = $this->cdnService->getHitRateStats();
        View::assign(['config' => $config, 'stats' => $stats]);
        return $this->view('/cdn/index');
    }

    public function save()
    {
        $data = [
            'enabled'        => !empty($this->request->post('enabled')),
            'domain'         => $this->request->post('domain', ''),
            'static_version' => $this->request->post('static_version', ''),
            'webp_enabled'   => !empty($this->request->post('webp_enabled')),
        ];
        $this->cdnService->saveConfig($data);
        return json(['code' => 0, 'msg' => 'CDN配置已保存']);
    }

    public function purge()
    {
        $urls = $this->request->post('urls', []);
        $result = $this->cdnService->purge($urls);
        return json(['code' => 0, 'msg' => '缓存已清除', 'data' => $result]);
    }
}
