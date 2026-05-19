<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\MediaService;
use app\common\service\UploadService;
use think\facade\Request;

/**
 * 媒体资源库控制器
 */
class MediaController extends AdminBaseController
{
    /**
     * 媒体列表
     */
    public function index()
    {
        $params = [
            'filetype' => Request::get('filetype', ''),
            'keyword'  => Request::get('keyword', ''),
            'cate_id'  => Request::get('cate_id', ''),
        ];

        $mediaService = new MediaService();
        $list = $mediaService->getList($params, 24);

        // 视图切换参数
        $viewType = Request::get('view', 'grid');

        // 构建筛选参数字符串（用于视图切换链接）
        $paramsStr = '';
        if (!empty($params['filetype'])) $paramsStr .= '&filetype=' . urlencode($params['filetype']);
        if (!empty($params['keyword'])) $paramsStr .= '&keyword=' . urlencode($params['keyword']);
        if (!empty($params['cate_id'])) $paramsStr .= '&cate_id=' . urlencode($params['cate_id']);

        $this->app->view->assign('list', $list);
        $this->app->view->assign('params', $params);
        $this->app->view->assign('viewType', $viewType);
        $this->app->view->assign('paramsStr', $paramsStr);

        return $this->app->view->fetch('/media_list');
    }

    /**
     * 上传媒体文件
     */
    public function upload()
    {
        $file = Request::file('file');
        if (empty($file)) {
            return $this->error('请选择要上传的文件');
        }

        $filetype = Request::post('filetype', 'image');
        $allowedTypes = ['image', 'video', 'file'];
        if (!in_array($filetype, $allowedTypes)) {
            $filetype = 'image';
        }

        try {
            $uploadService = new UploadService();
            $result = $uploadService->uploadMedia($file, $filetype);

            // 记录到媒体库
            $mediaService = new MediaService();
            $mediaService->create([
                'filename' => $result['filename'],
                'filepath' => $result['url'],
                'filetype' => $filetype,
                'mimetype' => $result['mimetype'],
                'filesize' => $result['filesize'],
                'alt_text' => Request::post('alt_text', ''),
            ]);

            $this->recordLog('upload', '上传媒体文件：' . $result['filename']);

            return $this->success('上传成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 编辑媒体信息
     */
    public function edit(int $id)
    {
        $mediaService = new MediaService();
        $media = $mediaService->getById($id);

        if (empty($media)) {
            return $this->error('媒体文件不存在');
        }

        if (Request::isPost()) {
            $data = [
                'alt_text' => Request::post('alt_text', ''),
            ];

            if ($mediaService->update($id, $data)) {
                $this->recordLog('update', '编辑媒体信息：' . $media['filename']);
                return $this->success('保存成功', ['redirect' => '/admin/media/index']);
            }

            return $this->error('保存失败');
        }

        $this->app->view->assign('media', $media);
        return $this->app->view->fetch('/media_edit');
    }

    /**
     * 删除媒体文件
     */
    public function delete(int $id)
    {
        $mediaService = new MediaService();
        $media = $mediaService->getById($id);

        if (empty($media)) {
            return $this->error('媒体文件不存在');
        }

        if ($mediaService->delete($id)) {
            $this->recordLog('delete', '删除媒体文件：' . $media['filename']);
            return $this->success('删除成功');
        }

        return $this->error('删除失败');
    }

    /**
     * TinyMCE 媒体选择器弹窗
     */
    public function select()
    {
        $params = [
            'filetype' => Request::get('filetype', 'image'),
            'keyword'  => Request::get('keyword', ''),
        ];

        $mediaService = new MediaService();
        $list = $mediaService->getList($params, 24);

        $this->app->view->assign('list', $list);
        $this->app->view->assign('params', $params);

        return $this->app->view->fetch('/media_select');
    }
}
