<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginStoreFrontService;

/**
 * 插件商店前端控制器 — V2.9.36 Sprint PLUG-SHOP
 */
class PluginStoreFrontController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 商店首页
     */
    public function index()
    {
        $service = new PluginStoreFrontService();
        $data = $service->getHomePage();

        $this->assign([
            'featured'    => $data['featured'] ?? [],
            'hot'         => $data['hot'] ?? [],
            'latest'      => $data['latest'] ?? [],
            'recommended' => $data['recommended'] ?? [],
            'stats'       => $data['stats'] ?? [],
            'menuActive'  => 'plugin_store',
        ]);

        return $this->view('/plugin_store/store_index');
    }

    /**
     * 插件列表
     */
    public function list()
    {
        $page = (int) $this->request->get('page', 1);
        $filter = [
            'category_id' => $this->request->get('category_id', ''),
            'price_type'  => $this->request->get('price_type', ''),
            'keyword'     => $this->request->get('keyword', ''),
            'sort'        => $this->request->get('sort', 'download'),
        ];

        $service = new PluginStoreFrontService();
        $result = $service->getPluginList($page, 20, $filter);
        $categories = $service->getCategories();

        $this->assign([
            'list'        => $result['list'],
            'total'       => $result['total'],
            'page'        => $result['page'],
            'filter'      => $filter,
            'categories'  => $categories,
            'menuActive'  => 'plugin_store',
        ]);

        return $this->view('/plugin_store/store_list');
    }

    /**
     * 插件详情
     */
    public function detail(int $id)
    {
        $service = new PluginStoreFrontService();
        $result = $service->getPluginDetail($id);

        if ($result['code'] !== 0) {
            return $this->error($result['msg']);
        }

        $this->assign([
            'plugin'       => $result['data']['plugin'],
            'screenshots'  => $result['data']['screenshots'],
            'versions'     => $result['data']['versions'],
            'rating_stats' => $result['data']['rating_stats'],
            'compatibility'=> $result['data']['compatibility'],
            'menuActive'   => 'plugin_store',
        ]);

        return $this->view('/plugin_store/store_detail');
    }

    /**
     * 搜索
     */
    public function search()
    {
        $keyword = $this->request->get('keyword', '');
        $page = (int) $this->request->get('page', 1);

        $service = new PluginStoreFrontService();
        $result = $service->searchPlugins($keyword, $page);

        $this->assign([
            'list'       => $result['list'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'keyword'    => $keyword,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/store_list');
    }

    /**
     * 分类列表
     */
    public function category()
    {
        $service = new PluginStoreFrontService();
        $categories = $service->getCategories();

        $this->assign([
            'categories' => $categories,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/store_list');
    }
}
