<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiSeoSuggestionService;

/**
 * AI SEO建议控制器 — V2.9.26 R-5
 */
class AiSeoController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * SEO分析
     */
    public function analyze()
    {
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');
        $keyword = $this->request->post('keyword', '');

        if (empty($title) || empty($content)) {
            return json(['code' => -1, 'msg' => '标题和内容不能为空']);
        }

        $service = new AiSeoSuggestionService();
        $result = $service->analyze($title, $content, $keyword);

        return $result['success']
            ? json(['code' => 0, 'msg' => '分析完成', 'data' => $result['suggestions'], 'elapsed_ms' => $result['elapsed_ms']])
            : json(['code' => -1, 'msg' => $result['message']]);
    }

    /**
     * 关键词建议
     */
    public function suggestKeywords()
    {
        $content = $this->request->post('content', '');
        $limit = (int)$this->request->post('limit', 10);

        if (empty($content)) {
            return json(['code' => -1, 'msg' => '内容不能为空']);
        }

        $service = new AiSeoSuggestionService();
        $result = $service->suggestKeywords($content, $limit);

        return $result['success']
            ? json(['code' => 0, 'data' => $result['keywords']])
            : json(['code' => -1, 'msg' => $result['message']]);
    }

    /**
     * Meta标签优化
     */
    public function optimizeMeta()
    {
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');

        if (empty($title)) {
            return json(['code' => -1, 'msg' => '标题不能为空']);
        }

        $service = new AiSeoSuggestionService();
        $result = $service->optimizeMeta($title, $content);

        return $result['success']
            ? json(['code' => 0, 'data' => $result['meta']])
            : json(['code' => -1, 'msg' => $result['message']]);
    }

    /**
     * 可读性评分
     */
    public function readabilityScore()
    {
        $content = $this->request->post('content', '');

        if (empty($content)) {
            return json(['code' => -1, 'msg' => '内容不能为空']);
        }

        $service = new AiSeoSuggestionService();
        $score = $service->readabilityScore($content);

        return json(['code' => 0, 'data' => $score]);
    }
}
