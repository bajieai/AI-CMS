<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\User;

/**
 * 用户管理控制器
 */
class UserController extends AdminBaseController
{
    /**
     * 用户列表
     */
    public function index()
    {
        $list = User::order('id', 'desc')->paginate(20);

        $this->assign(['list' => $list]);
        return $this->view('/user_list');
    }

    /**
     * 添加用户
     */
    public function add()
    {
        if ($this->request->isGet()) {
            $this->assign(['info' => null]);
            return $this->view('/user_edit');
        }

        $data = $this->request->post();
        $data['password'] = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
        
        $user = new User();
        if ($user->save($data)) {
            $this->recordLog('添加用户', $data['username'] ?? '', $data);
            return $this->success('添加成功', ['redirect' => '/admin/user/index']);
        }
        return $this->error('添加失败');
    }

    /**
     * 编辑用户
     */
    public function edit(int $id)
    {
        $info = User::find($id);
        if (empty($info)) {
            return $this->error('用户不存在');
        }

        if ($this->request->isGet()) {
            $this->assign(['info' => $info]);
            return $this->view('/user_edit');
        }

        $data = $this->request->post();
        // 如果提交了新密码则更新
        if (!empty($data['new_password'])) {
            $data['password'] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }
        unset($data['new_password']);

        if ($info->save($data)) {
            $this->recordLog('编辑用户', $info->username ?? '', $data);
            return $this->success('更新成功');
        }
        return $this->error('更新失败');
    }

    /**
     * 删除用户
     */
    public function delete(int $id)
    {
        if ($id === session('user_id')) {
            return $this->error('不能删除当前登录用户');
        }

        $info = User::find($id);
        if (empty($info)) {
            return $this->error('用户不存在');
        }

        if ($info->delete()) {
            $this->recordLog('删除用户', $info->username ?? '');
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }
}
