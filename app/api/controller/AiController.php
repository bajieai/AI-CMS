<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\AiService;
use think\App;

/**
 * AI接口控制器
 */
class AiController
{
    protected AiService $aiService;

    public function __construct(App $app)
    {
        $this->aiService = new AiService();
    }

    /**
     * AI内容生成
     * POST /api/ai/generate
     */
    public function generate()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        if (!$this->aiService->isConfigured()) {
            return json(['code' => 4, 'msg' => 'AI服务未配置', 'data' => null]);
        }

        $prompt = request()->post('prompt', '');
        $template = request()->post('template', 'continue');

        if (empty($prompt)) {
            return json(['code' => 1, 'msg' => '请输入内容', 'data' => null]);
        }

        try {
            $result = $this->aiService->generate($prompt, $template);
            return json([
                'code' => 0,
                'msg' => '生成成功',
                'data' => ['content' => $result['content']],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * AI内容质量检测 - V2.8新增
     * POST /api/ai/quality
     */
    public function quality()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        if (!$this->aiService->isConfigured()) {
            return json(['code' => 4, 'msg' => 'AI服务未配置', 'data' => null]);
        }

        $content = request()->post('content', '');
        
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '请输入内容', 'data' => null]);
        }

        try {
            $result = $this->aiService->evaluateContentQuality($content);
            return json([
                'code' => 0,
                'msg' => '检测成功',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }
}
