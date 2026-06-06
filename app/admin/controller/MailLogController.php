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
     */
    public function stats()
    {
        $days = [];
        $success = [];
        $fail = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $days[] = $date;

            $dayStart = $date . ' 00:00:00';
            $dayEnd   = $date . ' 23:59:59';

            $success[] = MailLog::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', MailLog::STATUS_SENT)->count();
            $fail[] = MailLog::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', MailLog::STATUS_FAILED)->count();
        }

        return $this->success('ok', compact('days', 'success', 'fail'));
    }
}
