<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateComponentService;

class TemplateComponentController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $type = $this->request->get('type', '');
        $service = new TemplateComponentService();
        $components = $service->getAll($type);
        $this->assign(['components' => $components, 'menuActive' => 'template_component']);
        return $this->view('/template_component/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $service = new TemplateComponentService();
        // 保存逻辑...
        return $this->success('保存成功');
    }
}
