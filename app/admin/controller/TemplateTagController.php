<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateTagService;

class TemplateTagController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new TemplateTagService();
        $grouped = $service->getGroupedTags();
        $this->assign([
            'grouped' => $grouped,
            'menuActive' => 'template_tag',
        ]);
        return $this->view('/template_tag/index');
    }

    public function create()
    {
        $name = $this->request->post('name', '');
        $type = $this->request->post('type', 'custom');
        $color = $this->request->post('color', '#1890ff');
        $sort = (int)$this->request->post('sort', 99);
        if (empty($name)) {
            return $this->error('标签名称不能为空');
        }
        $service = new TemplateTagService();
        $tag = $service->create($name, $type, $color, $sort);
        return $this->success('创建成功', $tag);
    }

    public function edit(int $id)
    {
        $data = [
            'name' => $this->request->post('name', ''),
            'type' => $this->request->post('type', 'custom'),
            'color' => $this->request->post('color', '#1890ff'),
            'sort' => (int)$this->request->post('sort', 99),
            'status' => (int)$this->request->post('status', 1),
        ];
        $service = new TemplateTagService();
        $result = $service->update($id, $data);
        return $result ? $this->success('更新成功') : $this->error('更新失败');
    }

    public function delete(int $id)
    {
        $service = new TemplateTagService();
        $result = $service->delete($id);
        return $result ? $this->success('删除成功') : $this->error('删除失败');
    }

    public function attach()
    {
        $templateId = (int)$this->request->post('template_id', 0);
        $tagId = (int)$this->request->post('tag_id', 0);
        if ($templateId <= 0 || $tagId <= 0) {
            return $this->error('参数不完整');
        }
        $service = new TemplateTagService();
        $service->attachTag($templateId, $tagId);
        return $this->success('关联成功');
    }

    public function batchAssign()
    {
        $templateIds = $this->request->post('template_ids', []);
        $tagIds = $this->request->post('tag_ids', []);
        if (empty($templateIds) || empty($tagIds)) {
            return $this->error('参数不完整');
        }
        $service = new TemplateTagService();
        $service->batchAssignTags($templateIds, $tagIds);
        return $this->success('批量分配成功');
    }
}
