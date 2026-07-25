<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiAgentMarketService;
use think\facade\Json;

/**
 * AI智能体市场控制器
 * V2.9.38 AI-PLUS-5
 */
class AiAgentMarketController extends AdminBaseController
{
    protected AiAgentMarketService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new AiAgentMarketService();
    }

    public function market()
    {
        $result = $this->service->getMarketList($this->request->param());
        return $this->view('ai_agent/market', $result);
    }

    public function detail()
    {
        $id = (int) $this->request->param('id', 0);
        $item = $this->service->getMarketDetail($id);
        return $this->view('ai_agent/market_detail', ['item' => $item]);
    }

    public function install()
    {
        $templateId = (int) $this->request->param('template_id', 0);
        $id = $this->service->installAgent($templateId, $this->adminId ?? 0);
        return Json::success('安装成功', ['id' => $id]);
    }

    public function uninstall()
    {
        $agentId = (int) $this->request->param('id', 0);
        $this->service->uninstallAgent($agentId);
        return Json::success('卸载成功');
    }

    public function submit()
    {
        $workflowId = (int) $this->request->param('workflow_id', 0);
        $description = $this->request->param('description', '');
        $this->service->submitTemplate($workflowId, $description);
        return Json::success('已提交审核');
    }

    public function audit()
    {
        $templateId = (int) $this->request->param('template_id', 0);
        $approved = (bool) $this->request->param('approved', false);
        $reason = $this->request->param('reason', '');
        $this->service->auditTemplate($templateId, $approved, $reason);
        return Json::success($approved ? '审核通过' : '已拒绝');
    }

    public function rate()
    {
        $templateId = (int) $this->request->param('template_id', 0);
        $rating = (float) $this->request->param('rating', 5);
        $this->service->rateTemplate($templateId, $rating);
        return Json::success('评分成功');
    }
}
