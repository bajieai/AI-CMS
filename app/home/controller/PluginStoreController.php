<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\PluginStoreOpsService;
use app\common\model\PluginCategory;

/**
 * V2.9.25 L-3: 插件市场前端浏览控制器
 */
class PluginStoreController extends FrontBaseController
{
    public function index()
    {
        $params = [
            'keyword' => $this->request->get('keyword', ''),
            'category_id' => (int) $this->request->get('category_id', 0),
            'sort' => $this->request->get('sort', 'default'),
            'page' => (int) $this->request->get('page', 1),
            'limit' => (int) $this->request->get('limit', 20),
        ];
        $service = new PluginStoreOpsService();
        $result = $service->getFrontList($params);
        $this->assign([
            'list' => $result['list'],
            'total' => $result['total'],
            'categories' => PluginCategory::where('status', 1)->order('sort', 'asc')->select(),
            'params' => $params,
        ]);
        return $this->view('/plugin_store/index');
    }

    public function detail()
    {
        $code = $this->request->param('code', '');
        if (empty($code)) return redirect('/plugin_store/index');
        $service = new PluginStoreOpsService();
        $result = $service->getFrontDetail($code);
        if ($result['code'] !== 0) {
            $this->assign('error', $result['msg']);
            $this->assign('plugin', null);
        } else {
            $this->assign('plugin', $result['data']);
            $this->assign('error', '');
        }
        return $this->view('/plugin_store/detail');
    }
}
