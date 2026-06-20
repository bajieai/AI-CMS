<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiContentEnhanceService;

/**
 * AI内容编辑增强控制器 — V2.9.26 R-1
 *
 * 模式：continue(续写) / rewrite(改写) / expand(扩写) / summarize(摘要)
 * 风格：formal / casual / professional / academic / colloquial
 */
class AiContentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function continueWriting()
    {
        return $this->execute('continueWriting');
    }

    public function rewrite()
    {
        return $this->execute('rewrite');
    }

    public function expand()
    {
        return $this->execute('expand');
    }

    public function summarize()
    {
        return $this->execute('summarize');
    }

    protected function execute(string $method)
    {
        $text = $this->request->post('text', '');
        $style = $this->request->post('style', 'formal');
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }

        $service = new AiContentEnhanceService();
        $userId = $this->adminInfo['id'] ?? 0;
        $result = $service->$method($text, $style, $userId, $contentId);

        if ($result['success']) {
            return json(['code' => 0, 'msg' => '处理成功', 'data' => [
                'text' => $result['text'],
                'elapsed_ms' => $result['elapsed_ms'],
            ]]);
        }
        return json(['code' => -1, 'msg' => $result['message'] ?? '处理失败']);
    }
}
