<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ml\LangSiteService;

/**
 * 多语言站点管理控制器 — V2.9.34 ML-1
 */
class LangSiteController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new LangSiteService();
        $list = $service->getList($this->request->param());
        $this->assign('list', $list);
        $this->assign('menuActive', 'lang_site');
        return $this->view('/lang_site/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $service = new LangSiteService();
        $id = (int)($data['id'] ?? 0);
        $result = $service->save($data, $id);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function delete()
    {
        $id = (int)$this->request->param('id', 0);
        $service = new LangSiteService();
        $result = $service->delete($id);
        if ($result['success'] ?? false) {
            return $this->success('删除成功');
        }
        return $this->error($result['message'] ?? '删除失败');
    }

    public function toggle()
    {
        $id = (int)$this->request->param('id', 0);
        $status = (int)$this->request->post('status', 0);
        $service = new LangSiteService();
        $result = $service->save(['id' => $id, 'status' => $status], $id);
        if ($result['success'] ?? false) {
            return $this->success('操作成功');
        }
        return $this->error($result['message'] ?? '操作失败');
    }
}
