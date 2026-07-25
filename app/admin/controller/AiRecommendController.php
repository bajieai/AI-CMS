<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiRecommendEngineService;
use think\facade\Cache;

/**
 * AI推荐引擎后台控制器 - V2.9.40 AI-DEEP2-3
 */
class AiRecommendController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 推荐引擎配置页面
     */
    public function index()
    {
        $service = new AiRecommendEngineService();
        $config = $service->getConfig();
        $stats = $service->getStats();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => ['config' => $config, 'stats' => $stats]]);
        }

        $this->assign('config', $config);
        $this->assign('stats', $stats);
        return $this->view('/ai/recommend_index');
    }

    /**
     * 更新推荐配置
     */
    public function updateConfig()
    {
        $data = [
            'collaborative_weight' => (float) $this->request->post('collaborative_weight', 0.40),
            'content_weight'       => (float) $this->request->post('content_weight', 0.30),
            'hot_weight'           => (float) $this->request->post('hot_weight', 0.20),
            'fresh_weight'         => (float) $this->request->post('fresh_weight', 0.10),
            'cold_start_count'     => (int) $this->request->post('cold_start_count', 10),
            'cache_ttl'            => (int) $this->request->post('cache_ttl', 300),
        ];

        // 权重归一化验证
        $total = $data['collaborative_weight'] + $data['content_weight'] + $data['hot_weight'] + $data['fresh_weight'];
        if (abs($total - 1.0) > 0.01) {
            return json(['code' => 1, 'msg' => '策略权重总和必须为1.0，当前为' . $total]);
        }

        $service = new AiRecommendEngineService();
        $service->saveConfig($data);
        Cache::clear();

        return json(['code' => 0, 'msg' => '配置已更新']);
    }

    /**
     * 获取推荐结果预览
     */
    public function preview()
    {
        $userId = (int) $this->request->get('user_id', 0);
        $count = (int) $this->request->get('count', 10);

        $service = new AiRecommendEngineService();
        $results = $service->recommend($userId, $count);

        return json(['code' => 0, 'msg' => 'success', 'data' => $results]);
    }

    /**
     * 推荐效果统计
     */
    public function stats()
    {
        $service = new AiRecommendEngineService();
        $stats = $service->getStats();
        return json(['code' => 0, 'msg' => 'success', 'data' => $stats]);
    }

    /**
     * 清除推荐缓存
     */
    public function clearCache()
    {
        Cache::clear();
        return json(['code' => 0, 'msg' => '推荐缓存已清除']);
    }
}
