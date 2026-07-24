<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\plugin\PluginRatingService;

/**
 * 插件评价控制器 — V2.9.36 Sprint PLUG-SHOP
 */
class PluginRatingController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 提交评价
     */
    public function submit()
    {
        $pluginId = (int) $this->request->post('plugin_id', 0);
        $rating   = (int) $this->request->post('rating', 0);
        $title    = $this->request->post('title', '');
        $content  = $this->request->post('content', '');
        $memberId = (int) session('user_id', 0);

        if ($pluginId <= 0 || $memberId <= 0) {
            return $this->error('参数错误');
        }

        $service = new PluginRatingService();
        $result = $service->submitRating($pluginId, $memberId, $rating, $title, $content);

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 评价列表
     */
    public function list(int $pluginId)
    {
        $page = (int) $this->request->get('page', 1);
        $sort = $this->request->get('sort', 'latest');

        $service = new PluginRatingService();
        $result = $service->getRatingList($pluginId, $page, $sort);
        $stats = $service->getRatingStats($pluginId);

        if ($this->isRealAjax()) {
            return $this->success('ok', [
                'list'  => $result['list'],
                'total' => $result['total'],
                'stats' => $stats,
            ]);
        }

        $this->assign([
            'list'       => $result['list'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'stats'      => $stats,
            'plugin_id'  => $pluginId,
            'menuActive' => 'plugin_store',
        ]);

        return $this->view('/plugin_store/rating');
    }

    /**
     * 开发者回复
     */
    public function reply(int $id)
    {
        $reply = $this->request->post('reply', '');

        if (empty($reply)) {
            return $this->error('回复内容不能为空');
        }

        $service = new PluginRatingService();
        $result = $service->replyRating($id, $reply);

        return $result['code'] === 0
            ? $this->success($result['msg'])
            : $this->error($result['msg']);
    }

    /**
     * 点赞
     */
    public function like(int $id)
    {
        $service = new PluginRatingService();
        $result = $service->likeRating($id);

        return $result['code'] === 0
            ? $this->success($result['msg'], $result['data'] ?? [])
            : $this->error($result['msg']);
    }
}
