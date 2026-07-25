<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI标签Controller
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\BaseController;
use app\common\service\ai\AiTagService;

/**
 * AI标签Controller - V2.9.32 FIX-4
 */
class AiTagController extends BaseController
{
    public function recommend(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentId = (int) $this->request->post('content_id', 0);
        $count = (int) $this->request->post('count', 5);
        $service = new AiTagService();
        $result = $service->recommend($contentId, $count);
        return $result['success'] ? $this->success($result['message'], $result) : $this->error($result['message']);
    }

    public function batchRecommend(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentIds = $this->request->post('content_ids', []);
        $count = (int) $this->request->post('count', 5);
        $service = new AiTagService();
        $result = $service->batchRecommend($contentIds, $count);
        return $this->success("批量标签推荐完成（成功{$result['success_count']}篇）", $result);
    }

    public function tagHotness(): \think\Response
    {
        $limit = (int) $this->request->get('limit', 20);
        $service = new AiTagService();
        $result = $service->tagHotness($limit);
        return $this->success('获取成功', $result);
    }
}
