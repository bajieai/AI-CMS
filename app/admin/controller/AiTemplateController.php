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
use app\common\service\AiTemplateService;
use app\common\service\CacheService;
use app\common\model\AiTemplate as AiTemplateModel;

/**
 * AI内容模板控制器 - V2.6新增
 * 模板CRUD + 使用模板生成 + 进度查看
 */
class AiTemplateController extends AdminBaseController
{
    /**
     * 模板列表页
     */
    public function index(): string
    {
        $page = (int) $this->request->param('page', 1);
        $keyword = $this->request->param('keyword', '');
        $status = $this->request->param('status', '');

        $filter = [];
        if ($keyword !== '') $filter['keyword'] = $keyword;
        if ($status !== '') $filter['status'] = $status;

        $result = AiTemplateService::getList($page, 12, $filter);

        $this->app->view->assign([
            'list'  => $result['data']['list'],
            'total' => $result['data']['total'],
            'page'  => $result['data']['page'],
            'limit' => $result['data']['limit'],
            'keyword' => $keyword,
            'status' => $status,
        ]);

        return $this->app->view->fetch('/ai_template_index');
    }

    /**
     * 编辑/新建模板页
     */
    public function edit(): string
    {
        $id = (int) $this->request->param('id', 0);
        $template = $id > 0 ? AiTemplateModel::find($id) : null;

        $this->app->view->assign([
            'template'   => $template,
            'id'         => $id,
            'cate_list'  => AiTemplateService::getCateList(),
            'model_list' => AiTemplateService::getModelList(),
            'style_list' => AiTemplateService::getStyleList(),
        ]);

        return $this->app->view->fetch('/ai_template_edit');
    }

    /**
     * 保存模板（新建+编辑共用）
     */
    public function save(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $data = $this->request->post();
        $id = (int) ($data['id'] ?? 0);

        // V2.7 修复：前端提交 fields_json 需转为 fields_config
        if (!empty($data['fields_json'])) {
            $decoded = json_decode($data['fields_json'], true);
            $data['fields_config'] = is_array($decoded) ? $decoded : [];
            unset($data['fields_json']);
        }

        // V2.9 新增：处理字段映射JSON
        if (!empty($data['field_mapping_json'])) {
            $decoded = json_decode($data['field_mapping_json'], true);
            $data['field_mapping'] = is_array($decoded) ? $decoded : [];
            unset($data['field_mapping_json']);
        }

        // V2.9 新增：处理质量检测配置JSON
        if (!empty($data['quality_config_json'])) {
            $decoded = json_decode($data['quality_config_json'], true);
            $data['quality_config'] = is_array($decoded) ? $decoded : [];
            unset($data['quality_config_json']);
        }

        if ($id > 0) {
            unset($data['id']);
            $result = AiTemplateService::update($id, $data);
        } else {
            $result = AiTemplateService::create($data);
        }

        if ($result['success']) {
            CacheService::clearByTag(CacheService::TAG_CONTENT);
            return $this->success($result['msg']);
        }
        return $this->error($result['msg']);
    }

    /**
     * 删除模板
     */
    public function delete(): \think\Response
    {
        $id = (int) $this->request->param('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $result = AiTemplateService::delete($id);
        if ($result['success']) {
            return $this->success($result['msg']);
        }
        return $this->error($result['msg']);
    }

    /**
     * 使用模板 — 参数填写页
     */
    public function use(): string
    {
        $id = (int) $this->request->param('id', 0);
        if ($id <= 0) {
            abort(404, '参数错误');
        }

        $detailResult = AiTemplateService::getDetail($id);
        if (!$detailResult['success']) {
            abort(404, $detailResult['msg']);
        }

        $this->app->view->assign([
            'template'   => $detailResult['data'],
            'cate_list'  => AiTemplateService::getCateList(),
            'model_list' => AiTemplateService::getModelList(),
        ]);

        return $this->app->view->fetch('/ai_template_use');
    }

    /**
     * 预览生成效果（V2.9新增）
     */
    public function preview(): \think\Response
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $templateId = (int) $this->request->param('template_id', 0);
        if ($templateId <= 0) {
            return json(['code' => 1, 'msg' => '请选择模板']);
        }

        $keyword = trim($this->request->param('keywords', ''));
        if (empty($keyword)) {
            $keyword = '测试关键词';
        }

        $params = $this->request->param();
        // 提取变量
        $variables = $this->request->param('variables/a', []);

        $result = AiTemplateService::preview($templateId, $keyword, $params);

        if ($result['success']) {
            return json(['code' => 0, 'data' => $result['data']]);
        }

        return json(['code' => 1, 'msg' => $result['msg']]);
    }

    /**
     * 提交批量生成任务
     */
    public function submitBatch(): \think\Response
    {
        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $data = $this->request->post();
        $templateId = (int) ($data['template_id'] ?? 0);

        if ($templateId <= 0) {
            return $this->error('请选择一个模板');
        }

        $result = AiTemplateService::batchGenerate($templateId, $data);
        if ($result['success']) {
            $this->recordLog('ai_template_batch', 'AI模板批量生成 #' . $templateId);
            return $this->success($result['msg'], $result['data']);
        }
        return $this->error($result['msg']);
    }

    /**
     * 生成进度页
     */
    public function progress(): string
    {
        $taskId = (int) $this->request->param('task_id', 0);
        
        if ($taskId > 0) {
            $task = \app\common\model\AiBatchTask::find($taskId);
            $template = null;
            if ($task && $task->template_id > 0) {
                $template = AiTemplateModel::find($task->template_id);
            }
            
            $this->app->view->assign([
                'task'     => $task,
                'template' => $template,
                'task_id'  => $taskId,
            ]);
        }

        return $this->app->view->fetch('/ai_template_progress');
    }

    /**
     * AJAX获取任务进度（轮询接口）
     */
    public function ajaxProgress(): \think\Response
    {
        $taskId = (int) $this->request->param('task_id', 0);
        if ($taskId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $task = \app\common\model\AiBatchTask::find($taskId);
        if (!$task) {
            return json(['code' => 1, 'msg' => '任务不存在']);
        }

        $percent = $task->total > 0 ? round(($task->completed / $task->total) * 100) : 0;

        return json([
            'code' => 0,
            'data' => [
                'status'    => $task->status,
                'completed' => $task->completed,
                'total'     => $task->total,
                'percent'   => $percent,
                'finished'  => $task->status === 2 || $task->status === 3,
            ],
        ]);
    }

    /**
     * V2.9.9: AI根据自然语言生成字段配置
     */
    public function generateFields(): \think\Response
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $nlDescription = trim($this->request->post('nl_description', ''));
        if (empty($nlDescription)) {
            return json(['code' => 1, 'msg' => '请输入自然语言描述']);
        }

        $result = AiTemplateService::generateFieldsFromNL($nlDescription);

        if ($result['success']) {
            $this->recordLog('ai_template_generate_fields', 'AI生成字段: ' . mb_substr($nlDescription, 0, 50));
            return json(['code' => 0, 'msg' => $result['msg'], 'data' => $result['data']]);
        }
        return json(['code' => 1, 'msg' => $result['msg']]);
    }
}
