<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\AiReport;
use app\common\service\AiReportService;
use think\facade\Log;

/**
 * AI数据分析报告管理 - V2.9.1 M9
 */
class ReportController extends AdminBaseController
{
    /**
     * 报告列表
     */
    public function index()
    {
        $type = $this->request->get('type', '');
        $page = (int) $this->request->get('page', 1);
        $limit = 20;

        $query = AiReport::order('id', 'desc');
        if (!empty($type)) {
            $query->where('type', $type);
        }

        $list = $query->paginate(['list_rows' => $limit, 'page' => $page]);

        $this->app->view->assign('list', $list);
        $this->app->view->assign('type', $type);
        $this->app->view->assign('types', ['daily' => '日报', 'weekly' => '周报', 'monthly' => '月报', 'manual' => '手动']);

        return $this->app->view->fetch('/report_index');
    }

    /**
     * 报告详情
     */
    public function detail(int $id = 0)
    {
        $report = AiReport::find($id);
        if (!$report) {
            $this->error('报告不存在');
        }

        $this->app->view->assign('report', $report);
        return $this->app->view->fetch('/report_detail');
    }

    /**
     * 生成报告（手动触发）
     */
    public function generate()
    {
        $type = $this->request->post('type', 'daily');
        $start = $this->request->post('start_time', '');
        $end   = $this->request->post('end_time', '');

        $endTime = $end ? strtotime($end . ' 23:59:59') : strtotime('today 23:59:59');
        $startTime = $start ? strtotime($start . ' 00:00:00') : match ($type) {
            'daily'   => strtotime('-1 day 00:00:00'),
            'weekly'  => strtotime('-7 days 00:00:00'),
            'monthly' => strtotime('-30 days 00:00:00'),
            default   => strtotime('-1 day 00:00:00'),
        };

        try {
            $reportId = AiReportService::generate($type, $startTime, $endTime);
            return json(['code' => 0, 'msg' => '报告生成任务已启动', 'data' => ['id' => $reportId]]);
        } catch (\Exception $e) {
            Log::error('[ReportController] 生成报告失败: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '生成失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除报告
     */
    public function delete(int $id = 0)
    {
        $report = AiReport::find($id);
        if (!$report) {
            $this->error('报告不存在');
        }

        $report->delete();
        $this->success('删除成功');
    }

    /**
     * 发布报告
     */
    public function publish(int $id = 0)
    {
        $report = AiReport::find($id);
        if (!$report) {
            $this->error('报告不存在');
        }

        $report->status = AiReport::STATUS_PUBLISHED;
        $report->save();
        $this->success('发布成功');
    }
}
