<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiConfigService;

/**
 * AI编辑器配置管理控制器 — V2.9.28 A-8
 */
class AiConfigController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 配置页面
     */
    public function index()
    {
        $service = new AiConfigService();
        $config = $service->getConfig();

        $this->assign([
            'config' => $config,
            'menuActive' => 'ai_config',
        ]);

        return $this->view('/ai/config_editor');
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $data = $this->request->post();
        $service = new AiConfigService();
        $result = $service->saveConfig($data);
        if ($result['success']) {
            $this->recordLog('保存AI编辑器配置', '');
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * API消耗统计
     */
    public function apiStats()
    {
        $days = (int)$this->request->get('days', 30);
        $service = new AiConfigService();
        $stats = $service->getApiUsageStats($days);

        $this->assign([
            'stats' => $stats['stats'],
            'days' => $days,
            'menuActive' => 'ai_config',
        ]);

        return $this->view('/ai/api_stats');
    }
}
