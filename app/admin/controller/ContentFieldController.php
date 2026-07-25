<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\content\ContentFieldService;
use think\App;

/**
 * 内容字段管理 — V2.9.36 CM-1
 */
class ContentFieldController extends AdminBaseController
{
    protected ContentFieldService $fieldService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->fieldService = new ContentFieldService();
    }

    public function index(int $id)
    {
        $fields = $this->fieldService->getFields($id);
        $types = $this->fieldService->getFieldTypes();
        return $this->view('content_model/field', [
            'model_id' => $id,
            'fields' => $fields,
            'types' => $types,
        ]);
    }

    public function save()
    {
        $data = $this->request->post();
        $result = $this->fieldService->createField($data);
        return json($result);
    }

    public function update(int $id)
    {
        $data = $this->request->post();
        $result = $this->fieldService->updateField($id, $data);
        return json($result);
    }

    public function delete(int $id)
    {
        $result = $this->fieldService->deleteField($id);
        return json($result);
    }

    public function sort()
    {
        $sortData = $this->request->post('sort_data', '');
        if (is_string($sortData)) {
            $sortData = json_decode($sortData, true) ?: [];
        }
        $result = $this->fieldService->sortFields($sortData);
        return json($result);
    }

    public function copy(int $id)
    {
        $result = $this->fieldService->copyField($id);
        return json($result);
    }

    public function types()
    {
        $types = $this->fieldService->getFieldTypes();
        return json(['code' => 0, 'data' => $types]);
    }

    public function detail(int $id)
    {
        $field = $this->fieldService->getFieldById($id);
        return json(['code' => 0, 'data' => $field]);
    }
}
