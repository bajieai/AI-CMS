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
use app\common\service\SocialShareService;
use app\common\service\ShareTrackerService;

/**
 * 社交分享管理后台控制器 - V2.9.9 轻量版
 */
class SocialShareController extends AdminBaseController
{
    /**
     * 分享概览页（V2.9.9 分享追踪看板）
     */
    public function index()
    {
        $period = $this->request->get('period', 'month');
        $days = match($period) {
            'week'  => 7,
            'today' => 1,
            default => 30,
        };
        $startTime = strtotime("-{$days} days");
        $endTime = time();

        $overview = ShareTrackerService::getOverview($startTime, $endTime);
        $trend = ShareTrackerService::getTrend(min($days, 30));
        $topContent = ShareTrackerService::getTopContent(10, $startTime, $endTime);
        $config = SocialShareService::getConfig();

        $this->assign([
            'overview'    => $overview,
            'trend'       => $trend,
            'top_content' => $topContent,
            'config'      => $config,
            'period'      => $period,
        ]);
        return $this->view('/social_share_index');
    }

    /**
     * 分享统计数据API
     */
    public function stats()
    {
        $period = $this->request->get('period', 'month');
        $contentId = (int) $this->request->get('content_id', 0);
        $stats = SocialShareService::getStats($contentId ?: null, $period);
        return json(['code' => 0, 'data' => $stats]);
    }

    /**
     * 分享配置页
     */
    public function config()
    {
        $config = SocialShareService::getConfig();
        $this->assign('config', $config);
        return $this->view('/social_share_config');
    }

    /**
     * 保存分享配置
     */
    public function saveConfig()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $data = $this->request->post();
        try {
            SocialShareService::saveConfig($data);
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
