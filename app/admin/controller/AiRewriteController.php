<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiRewriteService;

/**
 * AI批量改写控制器 — V2.9.30 AI2-1
 */
class AiRewriteController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function batchRewrite()
    {
        $contentIds = $this->request->post('content_ids', []);
        $mode = $this->request->post('mode', 'title');
        $intensity = $this->request->post('intensity', 'moderate');
        $style = $this->request->post('style', '');

        if (empty($contentIds)) {
            return $this->error('请选择要改写的内容');
        }
        if (count($contentIds) > 20) {
            return $this->error('单次最多改写20篇内容');
        }

        $userId = $this->adminInfo['id'] ?? 0;
        $service = new AiRewriteService();
        $results = $service->batchRewrite($userId, $contentIds, $mode, $intensity, $style);

        $success = count(array_filter($results, fn($r) => $r['success'] ?? false));
        return $this->success("改写完成: 成功{$success}篇", $results);
    }

    public function confirm(int $id)
    {
        $service = new AiRewriteService();
        $result = $service->confirm($id);
        return $result ? $this->success('确认成功') : $this->error('确认失败');
    }

    public function discard(int $id)
    {
        $service = new AiRewriteService();
        $result = $service->discard($id);
        return $result ? $this->success('已放弃') : $this->error('操作失败');
    }

    public function rollback(int $contentId)
    {
        $logId = (int)$this->request->post('log_id', 0);
        $service = new AiRewriteService();
        $result = $service->rollback($contentId, $logId);
        return $result ? $this->success('回滚成功') : $this->error('回滚失败');
    }

    public function history(int $contentId)
    {
        $service = new AiRewriteService();
        $history = $service->getHistory($contentId);
        return $this->success('获取成功', $history);
    }
}
