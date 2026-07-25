<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginStoreService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 PLUG-3: 插件商店V2控制器（区别于V2.9.28 PluginStoreController）
 */
class PluginStoreV2Controller extends AdminBaseController
{
    protected PluginStoreService $storeService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->storeService = new PluginStoreService();
    }

    public function index()
    {
        $params = [
            'category' => $this->request->get('category', ''),
            'keyword'  => $this->request->get('keyword', ''),
            'sort'     => $this->request->get('sort', 'latest'),
            'page'     => (int) $this->request->get('page', 1),
        ];
        $result = $this->storeService->getStoreList($params);
        View::assign($result);
        return $this->view('/plugin_store_v2/index');
    }

    public function detail()
    {
        $storeId = $this->request->get('store_id', '');
        $detail = $this->storeService->getStoreDetail($storeId);
        View::assign(['detail' => $detail]);
        return $this->view('/plugin_store_v2/detail');
    }

    public function install()
    {
        $storeId = $this->request->post('store_id', '');
        $result = $this->storeService->installFromStore($storeId);
        return json(['code' => $result['success'] ? 0 : 1, 'msg' => $result['message'] ?? '']);
    }
}
