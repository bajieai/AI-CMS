<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\report\ExportCenterService;

/**
 * 数据导出中心控制器 — V2.9.34 DR-3
 */
class ExportCenterController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new ExportCenterService();
        $history = $service->getExportHistory();
        $this->assign('history', $history);
        $this->assign('menuActive', 'export_center');
        return $this->view('/export_center/index');
    }

    public function export()
    {
        $params = $this->request->post();
        $service = new ExportCenterService();
        $result = $service->export($params);
        if ($result['success'] ?? false) {
            return $this->success('导出成功', $result);
        }
        return $this->error($result['message'] ?? '导出失败');
    }

    public function download()
    {
        $exportId = (int)$this->request->param('id', 0);
        $service = new ExportCenterService();
        $result = $service->downloadFile($exportId);
        return json($result);
    }

    public function createScheduledExport()
    {
        $config = $this->request->post();
        $service = new ExportCenterService();
        $result = $service->createScheduledExport($config);
        if ($result['success'] ?? false) {
            return $this->success('创建成功', $result);
        }
        return $this->error($result['message'] ?? '创建失败');
    }
}
