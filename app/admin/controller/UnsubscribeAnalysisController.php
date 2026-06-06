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
use app\common\model\Subscriber;

/**
 * V2.9.19 S-1b: 退订分析面板控制器
 */
class UnsubscribeAnalysisController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        return $this->view('/unsubscribe_analysis');
    }

    /**
     * 退订分析概览 — 8 个指标卡片
     */
    public function overview()
    {
        $total        = Subscriber::count();
        $confirmed    = Subscriber::where('status', Subscriber::STATUS_CONFIRMED)->count();
        $pending      = Subscriber::where('status', Subscriber::STATUS_PENDING)->count();
        $unsubscribed = Subscriber::where('status', Subscriber::STATUS_UNSUBSCRIBED)->count();

        $monthStart = date('Y-m-01 00:00:00');
        $monthNew   = Subscriber::where('status', Subscriber::STATUS_CONFIRMED)
            ->where('confirmed_at', '>=', $monthStart)->count();
        $monthUnsub = Subscriber::where('status', Subscriber::STATUS_UNSUBSCRIBED)
            ->where('unsubscribed_at', '>=', $monthStart)->count();

        $confirmRate = $total > 0 ? round($confirmed / $total * 100, 1) : 0;
        $unsubRate   = $total > 0 ? round($unsubscribed / max($total, 1) * 100, 1) : 0;

        return $this->success('ok', compact(
            'total', 'confirmed', 'pending', 'unsubscribed',
            'monthNew', 'monthUnsub', 'confirmRate', 'unsubRate'
        ));
    }

    /**
     * 近30天订阅/退订趋势
     */
    public function trend()
    {
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $days[] = date('Y-m-d', strtotime("-{$i} days"));
        }

        $subscribed   = [];
        $unsubscribed = [];
        foreach ($days as $date) {
            $subscribed[]   = Subscriber::whereDate('confirmed_at', $date)->count();
            $unsubscribed[] = Subscriber::whereDate('unsubscribed_at', $date)->count();
        }

        return $this->success('ok', [
            'days'         => $days,
            'subscribed'   => $subscribed,
            'unsubscribed' => $unsubscribed,
        ]);
    }
}
