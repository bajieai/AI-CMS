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
use app\common\model\MailLog;

/**
 * 邮件发送日志控制器 - V2.9.18 D-3
 */
class MailLogController extends AdminBaseController
{
    public function index()
    {
        return $this->view('/mail_log');
    }

    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);
        $status   = $this->request->get('status', '');
        $dateFrom = $this->request->get('date_from', '');
        $dateTo   = $this->request->get('date_to', '');

        $query = MailLog::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $total = $query->count();
        $data  = $query->page($page, $pageSize)->select();

        return $this->success('ok', ['data' => $data, 'total' => $total]);
    }

    /**
     * V2.9.19 S-1d: 邮件日志统计卡片
     */
    public function overview()
    {
        $today = date('Y-m-d');
        $cacheKey = 'mail_log_overview_' . $today;

        $stats = \think\facade\Cache::remember($cacheKey, function () use ($today) {
            $todayStart = $today . ' 00:00:00';
            $todayEnd   = $today . ' 23:59:59';

            $total  = MailLog::whereBetween('created_at', [$todayStart, $todayEnd])->count();
            $sent   = MailLog::whereBetween('created_at', [$todayStart, $todayEnd])->where('status', MailLog::STATUS_SENT)->count();
            $failed = MailLog::whereBetween('created_at', [$todayStart, $todayEnd])->where('status', MailLog::STATUS_FAILED)->count();
            $rate   = $total > 0 ? round($sent / $total * 100, 1) : 0;

            return compact('total', 'sent', 'failed', 'rate');
        }, 60);

        return $this->success('ok', $stats);
    }

    /**
     * V2.9.19 S-1d: 近30天发送趋势
     * V2.9.21 BUG-3 优化：改用 GROUP BY 单次查询替代 60 次循环查询
     */
    public function stats()
    {
        $days = [];
        $success = [];
        $fail = [];

        // 预生成日期数组
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = $date;
            $success[$date] = 0;
            $fail[$date] = 0;
        }

        $startDate = $days[0] . ' 00:00:00';
        $endDate   = $days[count($days) - 1] . ' 23:59:59';

        // 单次 GROUP BY 查询（V2.9.21 优化）
        $rows = MailLog::field([
                "DATE(created_at) as date",
                "status",
                "COUNT(*) as count"
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->group("DATE(created_at), status")
            ->select();

        foreach ($rows as $row) {
            $d = $row['date'];
            if ($row['status'] == MailLog::STATUS_SENT) {
                $success[$d] = (int) $row['count'];
            } elseif ($row['status'] == MailLog::STATUS_FAILED) {
                $fail[$d] = (int) $row['count'];
            }
        }

        // 按日期顺序重组数组
        $successOrdered = [];
        $failOrdered = [];
        foreach ($days as $d) {
            $successOrdered[] = $success[$d];
            $failOrdered[] = $fail[$d];
        }

        return $this->success('ok', [
            'days'    => $days,
            'success' => $successOrdered,
            'fail'    => $failOrdered,
        ]);
    }

    /**
     * V2.9.21 D-2: 邮件统计看板页面
     */
    public function statistics()
    {
        return $this->view('/mail_log_statistics');
    }

    /**
     * V2.9.21 D-2: 邮件统计数据接口（供 Chart.js 使用）
     */
    public function statisticsData()
    {
        $type = $this->request->get('type', 'trend'); // trend | status | template | hourly

        switch ($type) {
            case 'trend':
                return $this->getTrendData();
            case 'status':
                return $this->getStatusDistribution();
            case 'template':
                return $this->getTemplateDistribution();
            case 'hourly':
                return $this->getHourlyDistribution();
            default:
                return $this->success('ok', []);
        }
    }

    /**
     * 获取趋势数据（近30天）
     */
    protected function getTrendData()
    {
        $days = [];
        $sent = [];
        $failed = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = $date;
            $sent[$date] = 0;
            $failed[$date] = 0;
        }

        $startDate = $days[0] . ' 00:00:00';
        $endDate   = end($days) . ' 23:59:59';

        $rows = MailLog::field([
                "DATE(created_at) as date",
                "status",
                "COUNT(*) as count"
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->group("DATE(created_at), status")
            ->select();

        foreach ($rows as $row) {
            $d = $row['date'];
            if ($row['status'] == MailLog::STATUS_SENT) {
                $sent[$d] = (int) $row['count'];
            } elseif ($row['status'] == MailLog::STATUS_FAILED) {
                $failed[$d] = (int) $row['count'];
            }
        }

        $sentOrdered = [];
        $failedOrdered = [];
        foreach ($days as $d) {
            $sentOrdered[] = $sent[$d];
            $failedOrdered[] = $failed[$d];
        }

        return $this->success('ok', [
            'labels' => $days,
            'datasets' => [
                ['label' => '发送成功', 'data' => $sentOrdered, 'color' => '#28a745'],
                ['label' => '发送失败', 'data' => $failedOrdered, 'color' => '#dc3545'],
            ],
        ]);
    }

    /**
     * 获取状态分布数据
     */
    protected function getStatusDistribution()
    {
        $rows = MailLog::field(['status', 'COUNT(*) as count'])
            ->group('status')
            ->select();

        $labels = [];
        $data = [];
        $colors = ['#28a745', '#dc3545', '#ffc107'];
        $statusMap = [
            MailLog::STATUS_SENT   => '发送成功',
            MailLog::STATUS_FAILED => '发送失败',
            MailLog::STATUS_PENDING=> '待发送',
        ];

        foreach ($rows as $row) {
            $labels[] = $statusMap[$row['status']] ?? '未知';
            $data[] = (int) $row['count'];
        }

        return $this->success('ok', [
            'labels' => $labels,
            'data'   => $data,
            'colors' => array_slice($colors, 0, count($data)),
        ]);
    }

    /**
     * 获取模板分布数据（Top 10）
     */
    protected function getTemplateDistribution()
    {
        // V2.9.27 修复: i8j_mail_log表无template列,改用subject
        $rows = MailLog::field(['subject', 'COUNT(*) as count'])
            ->where('status', MailLog::STATUS_SENT)
            ->group('subject')
            ->order('count', 'desc')
            ->limit(10)
            ->select();

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row['subject'] ?: '默认模板';
            $data[] = (int) $row['count'];
        }

        return $this->success('ok', [
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    /**
     * 获取时段分布数据（24小时）
     */
    protected function getHourlyDistribution()
    {
        $today = date('Y-m-d');
        $start = $today . ' 00:00:00';
        $end   = $today . ' 23:59:59';

        $rows = MailLog::field([
                "HOUR(created_at) as hour",
                "COUNT(*) as count"
            ])
            ->whereBetween('created_at', [$start, $end])
            ->group("HOUR(created_at)")
            ->select();

        $hourly = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            $hourly[(int) $row['hour']] = (int) $row['count'];
        }

        $labels = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('%02d:00', $h);
        }

        return $this->success('ok', [
            'labels' => $labels,
            'data'   => $hourly,
        ]);
    }
}
