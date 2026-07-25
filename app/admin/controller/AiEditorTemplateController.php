<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiEditorTemplateService;

/**
 * AI编辑器模板库控制器 — V2.9.28 A-5
 */
class AiEditorTemplateController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 模板列表
     */
    public function index()
    {
        $params = $this->request->get();
        $page = (int)($params['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $limit = 20;
        $service = new AiEditorTemplateService();
        $data = $service->getList($params, $page, $limit);
        $categories = $service->getCategories();

        $this->assign([
            'list' => $data['list'],
            'total' => $data['total'],
            'page' => $data['page'],
            'total_page' => (int)ceil($data['total'] / $data['limit']),
            'limit' => $data['limit'],
            'params' => $params,
            'categories' => $categories,
            'menuActive' => 'ai_editor_template',
        ]);

        return $this->view('/content/ai_editor_templates');
    }

    /**
     * 添加/编辑模板
     */
    public function edit(int $id = 0)
    {
        $service = new AiEditorTemplateService();

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $userId = $this->adminInfo['id'] ?? 0;
            $result = $service->save($data, $id, $userId);
            if ($result['success']) {
                $this->recordLog($id > 0 ? '编辑AI模板' : '添加AI模板', $data['name'] ?? '');
                return $this->success($result['message'], ['redirect' => '/admin/ai_editor_template/index']);
            }
            return $this->error($result['message']);
        }

        $template = $id > 0 ? $service->getDetail($id) : null;
        $this->assign([
            'template' => $template,
            'id' => $id,
            'menuActive' => 'ai_editor_template',
        ]);

        return $this->view('/content/ai_editor_template_edit');
    }

    /**
     * 删除模板
     */
    public function delete(int $id)
    {
        $service = new AiEditorTemplateService();
        $result = $service->delete($id, $this->adminInfo['id'] ?? 0);
        if ($result['success']) {
            $this->recordLog('删除AI模板', "ID:{$id}");
            return $this->success($result['message']);
        }
        return $this->error($result['message']);
    }

    /**
     * 使用模板（AJAX，填充变量后返回Prompt）
     */
    public function useTemplate(int $id)
    {
        $variables = $this->request->post('variables', []);
        $service = new AiEditorTemplateService();
        $result = $service->useTemplate($id, $variables);
        return json(['code' => $result['success'] ? 0 : -1, 'msg' => $result['message'] ?? '成功', 'data' => $result]);
    }
}
