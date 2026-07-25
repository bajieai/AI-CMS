<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\CustomWhitelistService;

class CustomWhitelistController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    public function index()
    {
        $service = new CustomWhitelistService();
        $whitelist = $service->getAll();
        $this->assign(['whitelist' => $whitelist, 'menuActive' => 'custom_whitelist']);
        return $this->view('/custom_whitelist/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);
        $service = new CustomWhitelistService();
        $result = $service->save($data, $id);
        return $this->success($result['message']);
    }

    public function check()
    {
        $code = $this->request->post('code', '');
        $type = $this->request->post('type', 'css');
        $service = new CustomWhitelistService();
        $result = $service->check($code, $type);
        return json(['success' => true, 'data' => $result]);
    }

    public function delete(int $id)
    {
        $service = new CustomWhitelistService();
        $service->delete($id);
        return $this->success('删除成功');
    }
}
