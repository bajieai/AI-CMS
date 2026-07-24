<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\FeatureRegistryService;

/**
 * 功能看板控制器 — V2.9.30 Q-2
 */
class FeatureRegistryController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 功能看板列表页
     */
    public function index()
    {
        $service = new FeatureRegistryService();
        $grouped = $service->getGroupedFeatures();
        $scores = $service->calculateHealthScore();

        $this->assign([
            'grouped' => $grouped,
            'scores' => $scores,
            'menuActive' => 'feature_registry',
        ]);

        return $this->view('/feature_registry/index');
    }

    /**
     * 单个功能点健康检查
     */
    public function healthCheck(int $id)
    {
        $service = new FeatureRegistryService();
        $result = $service->healthCheck($id);

        if ($result['status'] === 1) {
            return $this->success('功能点运行正常', $result);
        } else {
            return $this->error('功能点异常', $result);
        }
    }

    /**
     * 全量健康检查
     */
    public function fullCheck()
    {
        $service = new FeatureRegistryService();
        $result = $service->fullHealthCheck();

        return $this->success('全量健康检查完成', $result);
    }
}
