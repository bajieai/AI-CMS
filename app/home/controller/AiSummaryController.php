<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI摘要Controller
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\BaseController;
use app\common\service\ai\AiSummaryService;

/**
 * AI摘要Controller - V2.9.32 FIX-4
 */
class AiSummaryController extends BaseController
{
    public function generate(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentId = (int) $this->request->post('content_id', 0);
        $type = $this->request->post('type', AiSummaryService::TYPE_MEDIUM);
        $service = new AiSummaryService();
        $result = $service->generate($contentId, $type);
        return $result['success'] ? $this->success($result['message'], $result) : $this->error($result['message']);
    }

    public function batchGenerate(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentIds = $this->request->post('content_ids', []);
        $type = $this->request->post('type', AiSummaryService::TYPE_MEDIUM);
        $service = new AiSummaryService();
        $result = $service->batchGenerate($contentIds, $type);
        return $this->success("批量摘要完成（成功{$result['success_count']}篇）", $result);
    }

    public function preview(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentId = (int) $this->request->post('content_id', 0);
        $type = $this->request->post('type', AiSummaryService::TYPE_MEDIUM);
        $service = new AiSummaryService();
        $result = $service->preview($contentId, $type);
        return $result['success'] ? $this->success('预览成功', $result) : $this->error($result['message']);
    }
}
