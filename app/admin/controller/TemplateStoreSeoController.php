<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateStoreSeoService;

/**
 * 模板商店SEO控制器 — V2.9.28 M-8
 */
class TemplateStoreSeoController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * SEO配置页面
     */
    public function index()
    {
        $service = new TemplateStoreSeoService();
        $config = $service->getStoreSeoConfig();

        $list = \app\common\model\TemplateStore::where('status', 1)
            ->field('id, name, seo_title, seo_description, seo_keywords')
            ->order('id', 'desc')
            ->paginate(20, false, ['page' => $this->request->get('page', 1)]);

        $this->assign([
            'config' => $config,
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'menuActive' => 'template_store_seo',
        ]);

        return $this->view('/template_store/seo_settings');
    }

    /**
     * 保存SEO配置
     */
    public function save()
    {
        $data = [
            'home_title' => $this->request->post('home_title', ''),
            'home_description' => $this->request->post('home_description', ''),
            'home_keywords' => $this->request->post('home_keywords', ''),
        ];

        $service = new TemplateStoreSeoService();
        $result = $service->saveStoreSeoConfig($data);
        if ($result['success']) {
            $this->recordLog('保存商店SEO配置', '');
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 模板SEO列表
     */
    public function templateList()
    {
        $list = \app\common\model\TemplateStore::where('status', 1)
            ->field('id, name, slug, seo_title, seo_description, seo_keywords')
            ->order('id', 'desc')
            ->paginate(20, false, ['page' => $this->request->get('page', 1)]);

        $this->assign([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
            'menuActive' => 'template_store_seo',
        ]);

        return $this->view('/template_store/seo_template_list');
    }

    /**
     * 编辑模板SEO
     */
    public function editTemplateSeo(int $id)
    {
        $service = new TemplateStoreSeoService();

        if ($this->request->isPost()) {
            $data = [
                'seo_title' => $this->request->post('seo_title', ''),
                'seo_description' => $this->request->post('seo_description', ''),
                'seo_keywords' => $this->request->post('seo_keywords', ''),
            ];
            $result = $service->saveTemplateSeo($id, $data);
            if ($result['success']) {
                $this->recordLog('编辑模板SEO', "模板ID:{$id}");
                return $this->success($result['message'], ['redirect' => '/admin/template_store_seo/templateList']);
            }
            return $this->error($result['message']);
        }

        $template = \app\common\model\TemplateStore::find($id);
        $this->assign([
            'template' => $template,
            'menuActive' => 'template_store_seo',
        ]);

        return $this->view('/template_store/seo_template_edit');
    }
}
