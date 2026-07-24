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
use app\common\service\data\SmartReportService;
use app\common\service\data\ReportAiAnalysisService;
use app\common\service\data\TrendPredictionService;
use app\admin\model\DataReport;

/**
 * 智能报表控制器 - V2.9.39 DATA-DEEP-2
 */
class SmartReportController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 报表列表页
     */
    public function index()
    {
        $service = new SmartReportService();
        $page = (int) $this->request->param('page', 1);
        $pageSize = (int) $this->request->param('page_size', 20);
        $reportType = $this->request->param('report_type', '');
        $status = $this->request->param('status', '');

        $result = $service->listReports($page, $pageSize, [
            'report_type' => $reportType,
            'status'      => $status,
        ]);

        $this->assign('list', $result['list']);
        $this->assign('total', $result['total']);
        $this->assign('page', $result['page']);
        $this->assign('typeMap', DataReport::getTypeMap());
        return $this->view('/smart_report/index');
    }

    /**
     * API: 报表列表
     */
    public function list()
    {
        $service = new SmartReportService();
        $page = (int) $this->request->param('page', 1);
        $pageSize = (int) $this->request->param('page_size', 20);

        $result = $service->listReports($page, $pageSize, [
            'report_type' => $this->request->param('report_type', ''),
            'status'      => $this->request->param('status', ''),
        ]);

        return $this->success('ok', $result);
    }

    /**
     * API: 获取报表详情
     */
    public function detail()
    {
        $id = (int) $this->request->param('id', 0);
        $service = new SmartReportService();
        $report = $service->getReport($id);
        if (!$report) {
            return $this->error('报表不存在');
        }
        return $this->success('ok', $report);
    }

    /**
     * API: 创建报表
     */
    public function create()
    {
        $data = $this->request->param();
        $service = new SmartReportService();
        $result = $service->createReport($data);
        if ($result['success']) {
            return $this->success('创建成功', $result);
        }
        return $this->error($result['msg'] ?? '创建失败');
    }

    /**
     * API: 更新报表
     */
    public function update()
    {
        $id = (int) $this->request->param('id', 0);
        $data = $this->request->except(['id'], 'param');
        $service = new SmartReportService();
        $result = $service->updateReport($id, $data);
        if ($result['success']) {
            return $this->success('更新成功');
        }
        return $this->error($result['msg'] ?? '更新失败');
    }

    /**
     * API: 删除报表
     */
    public function delete()
    {
        $id = (int) $this->request->param('id', 0);
        $service = new SmartReportService();
        $result = $service->deleteReport($id);
        if ($result['success']) {
            return $this->success('删除成功');
        }
        return $this->error($result['msg'] ?? '删除失败');
    }

    /**
     * API: 生成报表数据
     */
    public function generate()
    {
        $id = (int) $this->request->param('id', 0);
        $params = $this->request->except(['id'], 'param');
        $service = new SmartReportService();
        $result = $service->generateReport($id, $params);
        if ($result['success']) {
            return $this->success('生成成功', $result);
        }
        return $this->error($result['msg'] ?? '生成失败');
    }

    /**
     * API: AI分析报表
     */
    public function aiAnalysis()
    {
        $id = (int) $this->request->param('id', 0);
        $service = new SmartReportService();
        $result = $service->analyzeWithAi($id);
        if ($result['success']) {
            return $this->success('AI分析完成', $result['analysis']);
        }
        return $this->error($result['msg'] ?? 'AI分析失败');
    }

    /**
     * API: 趋势预测
     */
    public function predict()
    {
        $id = (int) $this->request->param('id', 0);
        $forecastDays = (int) $this->request->param('forecast_days', 7);
        $method = $this->request->param('method', TrendPredictionService::METHOD_LINEAR_REGRESSION);

        // 获取报表数据
        $service = new SmartReportService();
        $reportData = $service->generateReport($id);
        if (!$reportData['success']) {
            return $this->error($reportData['msg'] ?? '报表数据生成失败');
        }

        // 转换为时序数据
        $historicalData = [];
        $labels = $reportData['chart']['labels'] ?? [];
        $values = $reportData['chart']['datasets'][0]['data'] ?? [];
        foreach ($labels as $i => $label) {
            $historicalData[] = [
                'date'  => $label,
                'value' => (float) ($values[$i] ?? 0),
            ];
        }

        $predictionService = new TrendPredictionService();
        $result = $predictionService->predict($historicalData, $forecastDays, $method);

        return $this->success('预测完成', $result);
    }

    /**
     * API: 手动发送报表
     */
    public function send()
    {
        $id = (int) $this->request->param('id', 0);
        $recipients = $this->request->param('recipients', []);
        $service = new SmartReportService();
        $result = $service->sendReportManually($id, $recipients);
        if ($result['success']) {
            return $this->success('发送成功', ['sent' => $result['sent'] ?? 0]);
        }
        return $this->error($result['msg'] ?? '发送失败');
    }

    /**
     * API: 获取报表类型和状态映射
     */
    public function options()
    {
        return $this->success('ok', [
            'report_types' => DataReport::getTypeMap(),
            'statuses'     => DataReport::getStatusMap(),
            'prediction_methods' => [
                TrendPredictionService::METHOD_LINEAR_REGRESSION => '线性回归',
                TrendPredictionService::METHOD_MOVING_AVERAGE     => '移动平均',
                TrendPredictionService::METHOD_SEASONAL          => '季节性预测',
                TrendPredictionService::METHOD_AI                => 'AI预测',
            ],
        ]);
    }

    /**
     * 报表预览页
     */
    public function preview()
    {
        $id = (int) $this->request->param('id', 0);
        $service = new SmartReportService();
        $report = $service->getReport($id);
        if (!$report) {
            return $this->error('报表不存在');
        }

        $reportData = $service->generateReport($id);
        $this->assign('report', $report);
        $this->assign('reportData', $reportData);
        return $this->view('/smart_report/preview');
    }
}
