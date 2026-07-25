<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\template\TemplateBatchService;
use app\common\model\TemplateBatchLog;
use app\common\model\TemplateStore;

class TemplateBatchController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $list = TemplateStore::order('id', 'desc')->paginate(20, false, ['page' => $this->request->get('page', 1)]);
        $this->assign([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
            'menuActive' => 'template_batch',
        ]);
        return $this->view('/template_batch/index');
    }

    public function preview()
    {
        $action = $this->request->post('action', '');
        $templateIds = $this->request->post('template_ids', []);
        $params = $this->request->post('params', []);
        if (empty($templateIds) || empty($action)) {
            return $this->error('参数不完整');
        }
        $service = new TemplateBatchService();
        $result = $service->previewAction($action, $templateIds, $params);
        return $this->success('预览完成', $result);
    }

    public function execute()
    {
        $action = $this->request->post('action', '');
        $templateIds = $this->request->post('template_ids', []);
        $params = $this->request->post('params', []);
        if (empty($templateIds) || empty($action)) {
            return $this->error('参数不完整');
        }
        $service = new TemplateBatchService();
        $adminId = $this->adminInfo['id'] ?? 0;
        $adminName = $this->adminInfo['username'] ?? 'admin';
        switch ($action) {
            case 'batch_publish':
                $result = $service->batchPublish($templateIds, $adminId, $adminName);
                break;
            case 'batch_unpublish':
                $result = $service->batchUnpublish($templateIds, $adminId, $adminName);
                break;
            case 'batch_set_price':
                $result = $service->batchSetPrice($templateIds, (float)($params['price'] ?? 0), $params['original_price'] ?? null, $adminId, $adminName);
                break;
            case 'batch_add_tags':
                $result = $service->batchAddTags($templateIds, $params['tag_ids'] ?? [], $adminId, $adminName);
                break;
            case 'batch_set_category':
                $result = $service->batchSetCategory($templateIds, (int)($params['category_id'] ?? 0), $adminId, $adminName);
                break;
            case 'batch_set_industry':
                $result = $service->batchSetIndustry($templateIds, $params['industry'] ?? '', $adminId, $adminName);
                break;
            default:
                return $this->error('未知操作类型: ' . $action);
        }
        return $this->success('批量操作完成', $result);
    }

    public function log()
    {
        $list = TemplateBatchLog::order('id', 'desc')->paginate(20, false, ['page' => $this->request->get('page', 1)]);
        $this->assign([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'limit' => $list->listRows(),
            'menuActive' => 'template_batch',
        ]);
        return $this->view('/template_batch/log');
    }
}
