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

    /**
     * AI SEO优化 - V2.8新增
     * POST /api/ai/seo
     */
    public function seo()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        if (!$this->aiService->isConfigured()) {
            return json(['code' => 4, 'msg' => 'AI服务未配置', 'data' => null]);
        }

        $content = request()->post('content', '');
        $title = request()->post('title', '');
        
        if (empty($content)) {
            return json(['code' => 1, 'msg' => '请输入内容', 'data' => null]);
        }

        try {
            $keywords = [];
            $result = $this->aiService->seoOptimize($content, $keywords);
            
            // 解析AI返回的SEO优化结果
            $seoData = [
                'title' => $title,
                'keywords' => [],
                'description' => ''
            ];
            
            // 尝试从AI返回的内容中提取SEO信息
            if (is_string($result)) {
                // AI返回的是字符串，尝试解析
                $lines = explode("\n", $result);
                foreach ($lines as $line) {
                    if (preg_match('/标题[：:]\s*(.+)/u', $line, $m)) {
                        $seoData['title'] = trim($m[1]);
                    } else if (preg_match('/关键词[：:]\s*(.+)/u', $line, $m)) {
                        $seoData['keywords'] = array_map('trim', explode(',', $m[1]));
                    } else if (preg_match('/描述[：:]\s*(.+)/u', $line, $m)) {
                        $seoData['description'] = trim($m[1]);
                    }
                }
                $seoData['raw'] = $result;
            } else if (is_array($result)) {
                $seoData = array_merge($seoData, $result);
            }
            
            return json([
                'code' => 0,
                'msg' => '优化成功',
                'data' => $seoData,
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }
}
