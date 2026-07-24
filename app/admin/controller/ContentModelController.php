<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\content\ContentModelService;
use app\common\service\content\FormDesignerService;
use app\common\service\content\ContentImportExportService;
use think\App;

/**
 * 内容模型管理 — V2.9.36 CM-2/CM-5/CM-6
 */
class ContentModelController extends AdminBaseController
{
    protected ContentModelService $modelService;
    protected FormDesignerService $designerService;
    protected ContentImportExportService $importExportService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->modelService = new ContentModelService();
        $this->designerService = new FormDesignerService();
        $this->importExportService = new ContentImportExportService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $keyword = $this->request->param('keyword', '');
        $groupId = (int) $this->request->param('group_id', 0);
        $filter = [];
        if ($keyword) {
            $filter[] = ['model_name|model_identifier', 'like', '%' . $keyword . '%'];
        }
        if ($groupId > 0) {
            $filter[] = ['group_id', '=', $groupId];
        }
        $result = $this->modelService->getModelList($page, 20, $filter);
        return $this->view('content_model/index', $result);
    }

    public function create()
    {
        $groups = \think\facade\Db::name('model_group')->where('status', 1)->select()->toArray();
        return $this->view('content_model/form', ['model' => null, 'groups' => $groups]);
    }

    public function save()
    {
        $data = $this->request->post();
        $result = $this->modelService->createModel($data);
        return json($result);
    }

    public function edit(int $id)
    {
        $model = $this->modelService->getModelById($id);
        if (!$model) {
            return $this->error('模型不存在');
        }
        $groups = \think\facade\Db::name('model_group')->where('status', 1)->select()->toArray();
        return $this->view('content_model/form', ['model' => $model, 'groups' => $groups]);
    }

    public function update(int $id)
    {
        $data = $this->request->post();
        $result = $this->modelService->updateModel($id, $data);
        return json($result);
    }

    public function delete(int $id)
    {
        $result = $this->modelService->deleteModel($id);
        return json($result);
    }

    public function toggle(int $id)
    {
        $result = $this->modelService->toggleModel($id);
        return json($result);
    }

    public function designer(int $id)
    {
        $model = $this->modelService->getModelById($id);
        if (!$model) {
            return $this->error('模型不存在');
        }
        $layout = $this->designerService->getLayout($id);
        $containers = $this->designerService->getLayoutContainers();
        $fieldService = app(\app\common\service\content\ContentFieldService::class);
        $fields = $fieldService->getFields($id);
        return $this->view('content_model/designer', [
            'model' => $model,
            'layout' => $layout,
            'containers' => $containers,
            'fields' => $fields,
        ]);
    }

    public function saveDesign()
    {
        $modelId = (int) $this->request->post('model_id', 0);
        $layout = $this->request->post('layout', '');
        if (is_string($layout)) {
            $layout = json_decode($layout, true) ?: [];
        }
        $result = $this->designerService->saveLayout($modelId, $layout);
        return json($result);
    }

    public function importExport()
    {
        $models = $this->modelService->getEnabledModels();
        return $this->view('content_model/import_export', ['models' => $models]);
    }

    public function import()
    {
        $modelId = (int) $this->request->post('model_id', 0);
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择文件']);
        }
        $duplicateAction = $this->request->post('duplicate_action', 'skip');
        $result = $this->importExportService->importContents($modelId, $file->getRealPath(), [], $duplicateAction);
        return json($result);
    }

    public function export()
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $format = $this->request->param('format', 'csv');
        $filter = [];
        $startTime = $this->request->param('start_time', '');
        $endTime = $this->request->param('end_time', '');
        if ($startTime) {
            $filter[] = ['publish_time', '>=', $startTime];
        }
        if ($endTime) {
            $filter[] = ['publish_time', '<=', $endTime];
        }
        $result = $this->importExportService->exportContents($modelId, $filter, [], $format);
        if ($result['code'] === 0) {
            return download($result['data']['file_path'], $result['data']['file_name']);
        }
        return json($result);
    }

    public function downloadTemplate()
    {
        $modelId = (int) $this->request->param('model_id', 0);
        $result = $this->importExportService->getImportTemplate($modelId);
        if ($result['code'] === 0) {
            return download($result['data']['file_path'], $result['data']['file_name']);
        }
        return json($result);
    }
}
