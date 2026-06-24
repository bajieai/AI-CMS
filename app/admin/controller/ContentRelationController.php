<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\content\ContentRelationService;

/**
 * 内容关联管理后台控制器 - V2.9.29 Sprint I-1
 */
class ContentRelationController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $contentId = (int) $this->request->get('content_id', 0);
        $service = new ContentRelationService();
        $list = $service->getRelations($contentId);
        $this->assign('list', $list);
        $this->assign('content_id', $contentId);
        return $this->view('/content_relation_list');
    }

    public function add()
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $sourceId = (int) $this->request->post('source_content_id', 0);
        $targetId = (int) $this->request->post('target_content_id', 0);
        $type = $this->request->post('relation_type', 'related');
        $weight = (float) $this->request->post('relation_weight', 1.0);
        $service = new ContentRelationService();
        return $service->addRelation($sourceId, $targetId, $type, $weight, true)
            ? $this->success('关联成功') : $this->error('关联失败');
    }

    public function delete(int $id = 0)
    {
        $service = new ContentRelationService();
        return $service->deleteRelation($id) ? $this->success('已删除') : $this->error('删除失败');
    }

    public function network(int $id = 0)
    {
        $service = new ContentRelationService();
        $graph = $service->getNetworkGraph($id);
        return $this->success('获取成功', $graph);
    }
}
