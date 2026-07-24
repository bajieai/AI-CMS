<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 DEV-ECO-1: 开发者社区管理后台
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\dev\DeveloperCommunityService;
use think\App;

/**
 * 开发者社区管理后台 - V2.9.39 DEV-ECO-1
 */
class DeveloperCommunityController extends AdminBaseController
{
    protected DeveloperCommunityService $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new DeveloperCommunityService();
    }

    /**
     * 社区首页
     */
    public function index()
    {
        $stats = $this->service->getStats();
        $leaderboard = $this->service->getLeaderboard(10);

        return $this->view('/developer_community/index', [
            'stats' => $stats,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * 内容列表
     */
    public function posts()
    {
        $params = $this->request->get();
        $result = $this->service->getAdminList($params);

        return $this->view('/developer_community/posts', $result);
    }

    /**
     * 审核内容
     */
    public function review()
    {
        $id = (int) $this->request->post('id', 0);
        $status = (int) $this->request->post('status', 1);
        $note = $this->request->post('note', '');

        $result = $this->service->reviewPost($id, $status, $note);

        if ($result['success']) {
            $this->recordLog('dev_community_review', "审核社区内容 #{$id}");
            return $this->success('审核完成');
        }
        return $this->error('审核失败');
    }

    /**
     * 删除内容
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        $result = $this->service->deletePost($id);

        if ($result['success']) {
            $this->recordLog('dev_community_delete', "删除社区内容 #{$id}");
            return $this->success('已删除');
        }
        return $this->error('删除失败');
    }

    /**
     * 开发者排行榜
     */
    public function leaderboard()
    {
        $leaderboard = $this->service->getLeaderboard(100);

        return $this->view('/developer_community/leaderboard', ['list' => $leaderboard]);
    }
}
