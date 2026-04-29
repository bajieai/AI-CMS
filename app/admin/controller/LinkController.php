<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\LinkGroup;
use app\common\service\LinkService;
use think\facade\Request;

/**
 * 友情链接管理控制器
 */
class LinkController extends AdminBaseController
{
    /**
     * 友情链接列表
     */
    public function index()
    {
        $params = [
            'status' => Request::get('status', ''),
        ];

        $linkService = new LinkService();
        $list = $linkService->getList($params, 20);

        $this->app->view->assign('list', $list);
        $this->app->view->assign('params', $params);

        return $this->app->view->fetch('/link_list');
    }

    /**
     * 添加友情链接
     */
    public function add()
    {
        if (Request::isPost()) {
            $data = [
                'title'     => Request::post('title', ''),
                'url'       => Request::post('url', ''),
                'logo'      => Request::post('logo', ''),
                'group_id'  => (int) Request::post('group_id', 0),
                'sort'      => (int) Request::post('sort', 0),
                'status'    => (int) Request::post('status', 1),
            ];

            if (empty($data['title'])) {
                return $this->error('请输入网站名称');
            }
            if (empty($data['url'])) {
                return $this->error('请输入网站地址');
            }

            $linkService = new LinkService();
            if ($linkService->create($data)) {
                $this->recordLog('create', '添加友情链接：' . $data['title']);
                return $this->success('添加成功', ['redirect' => '/admin/link/index']);
            }

            return $this->error('添加失败');
        }

        $groups = LinkGroup::where('status', 1)->order('sort', 'asc')->column('name', 'id');
        $this->app->view->assign('groups', $groups);
        $this->app->view->assign('info', null);
        return $this->app->view->fetch('/link_edit');
    }

    /**
     * 编辑友情链接
     */
    public function edit(int $id)
    {
        $linkService = new LinkService();
        $info = $linkService->getById($id);

        if (empty($info)) {
            return $this->error('友情链接不存在');
        }

        if (Request::isPost()) {
            $data = [
                'title'     => Request::post('title', ''),
                'url'       => Request::post('url', ''),
                'logo'      => Request::post('logo', ''),
                'group_id'  => (int) Request::post('group_id', 0),
                'sort'      => (int) Request::post('sort', 0),
                'status'    => (int) Request::post('status', 1),
            ];

            if (empty($data['title'])) {
                return $this->error('请输入网站名称');
            }
            if (empty($data['url'])) {
                return $this->error('请输入网站地址');
            }

            if ($linkService->update($id, $data)) {
                $this->recordLog('update', '编辑友情链接：' . $data['title']);
                return $this->success('保存成功', ['redirect' => '/admin/link/index']);
            }

            return $this->error('保存失败');
        }

        $groups = LinkGroup::where('status', 1)->order('sort', 'asc')->column('name', 'id');
        $this->app->view->assign('groups', $groups);
        $this->app->view->assign('info', $info);
        return $this->app->view->fetch('/link_edit');
    }

    /**
     * 删除友情链接
     */
    public function delete(int $id)
    {
        $linkService = new LinkService();
        $info = $linkService->getById($id);

        if (empty($info)) {
            return $this->error('友情链接不存在');
        }

        if ($linkService->delete($id)) {
            $this->recordLog('delete', '删除友情链接：' . $info['title']);
            return $this->success('删除成功');
        }

        return $this->error('删除失败');
    }
}
