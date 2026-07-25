<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\content\ContentRelationService;
use think\App;

/**
 * 内容关系管理 — V2.9.36 CM-3
 */
class ContentRelationController extends AdminBaseController
{
    protected ContentRelationService $relationService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->relationService = new ContentRelationService();
    }

    public function index(int $id)
    {
        $relations = $this->relationService->getRelations($id);
        $types = \app\common\model\ContentRelation::getTypeList();
        return $this->view('content_model/relation', [
            'model_id' => $id,
            'relations' => $relations,
            'types' => $types,
        ]);
    }

    public function save()
    {
        $data = $this->request->post();
        $result = $this->relationService->createRelation($data);
        return json($result);
    }

    public function delete(int $id)
    {
        $result = $this->relationService->deleteRelation($id);
        return json($result);
    }

    public function addRelation()
    {
        $relationName = $this->request->post('relation_name', '');
        $sourceContentId = (int) $this->request->post('source_content_id', 0);
        $targetContentId = (int) $this->request->post('target_content_id', 0);
        $result = $this->relationService->addRelation($relationName, $sourceContentId, $targetContentId);
        return json($result);
    }

    public function removeRelation()
    {
        $relationName = $this->request->post('relation_name', '');
        $sourceContentId = (int) $this->request->post('source_content_id', 0);
        $targetContentId = (int) $this->request->post('target_content_id', 0);
        $result = $this->relationService->removeRelation($relationName, $sourceContentId, $targetContentId);
        return json($result);
    }

    public function batchAdd()
    {
        $relationName = $this->request->post('relation_name', '');
        $sourceContentId = (int) $this->request->post('source_content_id', 0);
        $targetIds = $this->request->post('target_ids', []);
        if (is_string($targetIds)) {
            $targetIds = json_decode($targetIds, true) ?: [];
        }
        $result = $this->relationService->batchAddRelations($relationName, $sourceContentId, $targetIds);
        return json($result);
    }

    public function search()
    {
        $keyword = $this->request->param('keyword', '');
        $modelId = (int) $this->request->param('model_id', 0);
        $result = $this->relationService->searchContentsForRelation($keyword, $modelId);
        return json(['code' => 0, 'data' => $result]);
    }

    public function related(int $id)
    {
        $relationName = $this->request->param('relation_name', '');
        $limit = (int) $this->request->param('limit', 10);
        $result = $this->relationService->getRelatedContents($id, $relationName, $limit);
        return json(['code' => 0, 'data' => $result]);
    }
}
