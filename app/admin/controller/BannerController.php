<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\BannerService;
use think\facade\Request;

/**
 * 轮播图管理控制器
 */
class BannerController extends AdminBaseController
{
    /**
     * 轮播图列表
     */
    public function index()
    {
        $params = [
            'status' => Request::get('status', ''),
        ];

        $bannerService = new BannerService();
        $list = $bannerService->getList($params, 20);

        $this->app->view->assign('list', $list);
        $this->app->view->assign('params', $params);

        return $this->app->view->fetch('/banner_list');
    }

    /**
     * 添加轮播图
     */
    public function add()
    {
        if (Request::isPost()) {
            $data = [
                'title'      => Request::post('title', ''),
                'image'      => Request::post('image', ''),
                'link'       => Request::post('link', ''),
                'target'     => Request::post('target', '_self'),
                'sort'       => (int) Request::post('sort', 0),
                'status'     => (int) Request::post('status', 1),
                'start_time' => Request::post('start_time', '') ? strtotime(Request::post('start_time')) : 0,
                'end_time'   => Request::post('end_time', '') ? strtotime(Request::post('end_time')) : 0,
            ];

            if (empty($data['title'])) {
                return $this->error('请输入标题');
            }
            if (empty($data['image'])) {
                return $this->error('请上传图片');
            }

            $bannerService = new BannerService();
            if ($bannerService->create($data)) {
                $this->recordLog('create', '添加轮播图：' . $data['title']);
                return $this->success('添加成功', ['redirect' => '/admin/banner/index']);
            }

            return $this->error('添加失败');
        }

        $this->app->view->assign('info', null);
        return $this->app->view->fetch('/banner_edit');
    }

    /**
     * 编辑轮播图
     */
    public function edit(int $id)
    {
        $bannerService = new BannerService();
        $info = $bannerService->getById($id);

        if (empty($info)) {
            return $this->error('轮播图不存在');
        }

        if (Request::isPost()) {
            $data = [
                'title'      => Request::post('title', ''),
                'image'      => Request::post('image', ''),
                'link'       => Request::post('link', ''),
                'target'     => Request::post('target', '_self'),
                'sort'       => (int) Request::post('sort', 0),
                'status'     => (int) Request::post('status', 1),
                'start_time' => Request::post('start_time', '') ? strtotime(Request::post('start_time')) : 0,
                'end_time'   => Request::post('end_time', '') ? strtotime(Request::post('end_time')) : 0,
            ];

            if (empty($data['title'])) {
                return $this->error('请输入标题');
            }
            if (empty($data['image'])) {
                return $this->error('请上传图片');
            }

            if ($bannerService->update($id, $data)) {
                $this->recordLog('update', '编辑轮播图：' . $data['title']);
                return $this->success('保存成功', ['redirect' => '/admin/banner/index']);
            }

            return $this->error('保存失败');
        }

        $this->app->view->assign('info', $info);
        return $this->app->view->fetch('/banner_edit');
    }

    /**
     * 删除轮播图
     */
    public function delete(int $id)
    {
        $bannerService = new BannerService();
        $info = $bannerService->getById($id);

        if (empty($info)) {
            return $this->error('轮播图不存在');
        }

        if ($bannerService->delete($id)) {
            $this->recordLog('delete', '删除轮播图：' . $info['title']);
            return $this->success('删除成功');
        }

        return $this->error('删除失败');
    }
}
