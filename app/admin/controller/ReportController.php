<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\report\ReportEngineService;

/**
 * 自定义报表控制器 — V2.9.34 DR-1
 */
class ReportController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new ReportEngineService();
        $list = $service->getList();
        $types = [
            'content'    => '内容报表',
            'user'       => '用户报表',
            'template'   => '模板报表',
            'pay'        => '付费报表',
            'distribute' => '分发报表',
        ];
        $this->assign('list', $list);
        $this->assign('types', $types);
        $this->assign('menuActive', 'report');
        return $this->view('/report_index');
    }

    public function save()
    {
        $data = $this->request->post();
        $id = (int)($data['id'] ?? 0);
        $service = new ReportEngineService();
        $result = $service->saveReport($data, $id);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function generate()
    {
        $reportId = (int)$this->request->param('id', 0);
        $params = $this->request->param();
        $service = new ReportEngineService();
        $result = $service->generate($reportId, $params);
        if ($result['success'] ?? false) {
            return $this->success('生成成功', $result);
        }
        return $this->error($result['message'] ?? '生成失败');
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $service = new ReportEngineService();
        $report = $service->getReport($id);
        if (!$report) {
            return $this->error('报表不存在');
        }
        $types = [
            'content'    => '内容报表',
            'user'       => '用户报表',
            'template'   => '模板报表',
            'pay'        => '付费报表',
            'distribute' => '分发报表',
        ];

        // 将JSON字段转为标签数组，方便模板展示为badge
        $metricsTags = $this->jsonToTags($report['metrics'] ?? null);
        $dimensionsTags = $this->jsonToTags($report['dimensions'] ?? null);
        $filtersTags = $this->jsonToTags($report['filters'] ?? null);

        $this->assign('report', $report);
        $this->assign('types', $types);
        $this->assign('metricsTags', $metricsTags);
        $this->assign('dimensionsTags', $dimensionsTags);
        $this->assign('filtersTags', $filtersTags);
        $this->assign('menuActive', 'report');
        return $this->view('/report_detail');
    }

    /**
     * 将JSON字段数据转为简单数组，用于模板badge展示
     */
    private function jsonToTags($data): array
    {
        if (empty($data)) return [];
        if (is_array($data)) return $data;
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function publish()
    {
        $id = (int)$this->request->param('id', 0);
        $service = new ReportEngineService();
        $result = $service->publishReport($id);
        if ($result['success'] ?? false) {
            return json(['code' => 0, 'msg' => '发布成功']);
        }
        return json(['code' => 1, 'msg' => $result['message'] ?? '发布失败']);
    }

    public function delete()
    {
        $id = (int)$this->request->param('id', 0);
        $service = new ReportEngineService();
        $result = $service->deleteReport($id);
        if ($result['success'] ?? false) {
            return json(['code' => 0, 'msg' => '删除成功']);
        }
        return json(['code' => 1, 'msg' => $result['message'] ?? '删除失败']);
    }
}
