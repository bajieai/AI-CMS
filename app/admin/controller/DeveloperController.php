<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\admin\service\DeveloperAdminService;

/**
 * 开发者管理后台控制器 - V2.9.29 Sprint D-1
 */
class DeveloperController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $page = (int) $this->request->get('page', 1);
        $pageSize = 15;
        $status = $this->request->get('status', '');
        $keyword = $this->request->get('keyword', '');

        $filter = [];
        if ($status !== '') $filter['status'] = (int) $status;
        if ($keyword !== '') $filter['keyword'] = $keyword;

        $service = new DeveloperAdminService();
        $result = $service->getList($page, $pageSize, $filter);

        $this->assign([
            'list' => $result['list'],
            'total' => $result['total'],
            'page' => $result['page'],
            'status' => $status,
            'keyword' => $keyword,
            'stats' => $service->getStats(),
        ]);

        return $this->view('/developer_list');
    }

    public function detail(int $id = 0)
    {
        $service = new DeveloperAdminService();
        $developer = $service->getById($id);
        if (empty($developer)) return $this->error('开发者不存在');

        $this->assign('developer', $developer);
        return $this->view('/developer_detail');
    }

    public function audit()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $id = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 0);
        $remark = $this->request->post('remark', '');
        if ($id <= 0) return $this->error('参数错误');

        $service = new DeveloperAdminService();
        return $service->audit($id, $status, $remark)
            ? $this->success('审核操作成功')
            : $this->error('审核操作失败');
    }

    public function disable()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $id = (int) $this->request->post('id', 0);
        $service = new DeveloperAdminService();
        return $service->disable($id) ? $this->success('已禁用') : $this->error('操作失败');
    }

    public function enable()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $id = (int) $this->request->post('id', 0);
        $service = new DeveloperAdminService();
        return $service->enable($id) ? $this->success('已启用') : $this->error('操作失败');
    }
}
