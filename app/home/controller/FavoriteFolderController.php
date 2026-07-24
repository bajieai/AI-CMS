<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\FavoriteFolderService;

class FavoriteFolderController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $memberId = $this->memberInfo['id'] ?? 0;
        if ($memberId <= 0) {
            return redirect('/member/login');
        }
        $service = new FavoriteFolderService();
        $folders = $service->getUserFolders($memberId);
        $this->assign('folders', $folders);
        return $this->view('/favorite_folders');
    }

    public function create()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }
        $memberId = $this->memberInfo['id'] ?? 0;
        $name = $this->request->post('name', '');
        $description = $this->request->post('description', '');
        $isPublic = (bool)$this->request->post('is_public', 0);
        if (empty($name)) {
            return json(['code' => 1, 'msg' => '收藏夹名称不能为空']);
        }
        $service = new FavoriteFolderService();
        $folder = $service->create($memberId, $name, $description, $isPublic);
        return json(['code' => 0, 'msg' => '创建成功', 'data' => $folder]);
    }

    public function edit(int $id)
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }
        $data = [
            'name' => $this->request->post('name', ''),
            'description' => $this->request->post('description', ''),
            'is_public' => (int)$this->request->post('is_public', 0),
        ];
        $service = new FavoriteFolderService();
        $result = $service->update($id, $data);
        return json(['code' => $result ? 0 : 1, 'msg' => $result ? '更新成功' : '更新失败']);
    }

    public function delete(int $id)
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }
        $service = new FavoriteFolderService();
        $result = $service->delete($id);
        return json(['code' => $result ? 0 : 1, 'msg' => $result ? '删除成功' : '删除失败']);
    }
}
