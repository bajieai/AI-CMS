<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\TemplateDevUpload;
use app\common\service\template\TemplateDeveloperService;
use think\Response;

/**
 * 模板开发者管理控制器（管理员角色） - V2.9.12
 *
 * 审核开发者上传的模板
 */
class TemplateDeveloperAdminController extends AdminBaseController
{
    /**
     * 待审核列表
     */
    public function index(): string
    {
        $status = $this->request->get('status', '');
        $query = TemplateDevUpload::with('member')
            ->order('create_time', 'desc');

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $list = $query->paginate(20);

        $this->assign([
            'list'   => $list,
            'status' => $status,
        ]);

        return $this->view('/template_developer/index');
    }

    /**
     * 审核详情页
     */
    public function detail(int $id): string
    {
        $upload = TemplateDevUpload::with('member')->find($id);
        if (empty($upload)) {
            return $this->error('记录不存在');
        }

        $manifest = $upload->getManifest();

        $this->assign([
            'upload'   => $upload,
            'manifest' => $manifest,
        ]);

        return $this->view('/template_developer/review');
    }

    /**
     * 审核通过（AJAX）
     */
    public function approve(int $id): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $auditorId = (int) session('user_id');
        $remark = $this->request->post('remark', '');

        $service = new TemplateDeveloperService();
        $result = $service->approve($id, $auditorId, $remark);

        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 审核拒绝（AJAX）
     */
    public function reject(int $id): Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $auditorId = (int) session('user_id');
        $remark = $this->request->post('remark', '');

        if (empty($remark)) {
            return $this->error('请填写拒绝原因');
        }

        $service = new TemplateDeveloperService();
        $result = $service->reject($id, $auditorId, $remark);

        if ($result['success']) {
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 删除记录（AJAX）
     */
    public function delete(int $id): Response
    {
        $upload = TemplateDevUpload::find($id);
        if (empty($upload)) {
            return $this->error('记录不存在');
        }

        // 删除文件
        if (!empty($upload->file_path) && file_exists($upload->file_path)) {
            unlink($upload->file_path);
        }

        $upload->delete();
        return $this->success('删除成功');
    }
}
