<?php
declare(strict_types=1);

namespace app\api\controller;

use app\api\controller\BaseController;
use app\common\service\ai\AiQaService;

/**
 * 智能问答API
 * V2.9.37 AI-HELPER-2
 */
class QaController extends BaseController
{
    /**
     * 提问
     */
    public function ask()
    {
        $question = $this->request->post('question', '');
        $sessionId = $this->request->post('session_id', session_id());
        if (empty($question)) {
            return $this->success('请输入问题', [], 400);
        }
        $memberId = $this->getMemberId();
        $service = new AiQaService();
        $result = $service->ask($question, $sessionId, $memberId);
        return $this->success('ok', $result);
    }

    /**
     * 对话历史
     */
    public function history()
    {
        $sessionId = $this->request->get('session_id', session_id());
        $service = new AiQaService();
        $result = $service->getHistory($sessionId);
        return $this->success('ok', $result);
    }

    /**
     * 反馈
     */
    public function feedback()
    {
        $qaId = (int) $this->request->post('qa_id', 0);
        $helpful = (int) $this->request->post('helpful', 1);
        if ($qaId <= 0) {
            return $this->success('参数错误', [], 400);
        }
        $service = new AiQaService();
        $result = $service->feedback($qaId, $helpful);
        return $this->success('ok', ['result' => $result]);
    }
}
