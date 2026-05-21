<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller;

use app\common\service\AiService;
use think\App;
use think\facade\Config;

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
        $style = request()->post('style', '');

        if (empty($prompt)) {
            return json(['code' => 1, 'msg' => '请输入内容', 'data' => null]);
        }

        try {
            $result = $this->aiService->generate($prompt, $template, ['style' => $style]);
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
     * AI图片生成 - V2.8新增 / V2.9.9-R4增强: 支持size参数
     * POST /api/ai/image
     */
    public function image()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        $prompt = request()->post('prompt', '');
        $style = request()->post('style', 'realistic');
        $size = request()->post('size', '1024x1024');
        
        if (empty($prompt)) {
            return json(['code' => 1, 'msg' => '请输入图片描述', 'data' => null]);
        }

        // 尺寸白名单校验
        $validSizes = ['1024x1024', '1024x576', '1024x768', '768x1024', '1792x1024', '1024x1792'];
        if (!in_array($size, $validSizes, true)) {
            $size = '1024x1024';
        }

        try {
            $result = $this->aiService->generateImage($prompt, [
                'style' => $style,
                'count' => 1,
                'size' => $size,
            ]);
            
            return json([
                'code' => 0,
                'msg' => '生成成功',
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

    /**
     * V3.1: AI批量配图（单图生成+自动Prompt构建）
     * POST /api/ai/batch_image
     *
     * 前端串行调用此接口实现批量配图+进度条，无需后端异步队列
     */
    public function batchImage()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        $title = request()->post('title', '');
        $content = request()->post('content', '');
        $style = request()->post('style', 'realistic');
        $paragraphIndex = (int) request()->post('paragraph_index', 0);

        // 检查配额
        $quota = $this->aiService->getImageQuota();
        if ($quota['remaining'] <= 0) {
            return json(['code' => 4, 'msg' => '今日AI配图额度已用完（' . $quota['limit'] . '次/天）', 'data' => $quota]);
        }

        try {
            // 从内容自动构建Prompt
            $prompt = $this->aiService->buildImagePrompt($title, $content);

            $result = $this->aiService->generateImage($prompt, [
                'style' => $style,
                'size' => '1024x1024',
            ]);

            return json([
                'code' => 0,
                'msg' => '生成成功',
                'data' => array_merge($result, [
                    'quota' => $this->aiService->getImageQuota(),
                    'paragraph_index' => $paragraphIndex,
                ]),
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * V3.1: SEO评分纯算法（零AI成本）
     * POST /api/ai/seo_score
     */
    public function seoScore()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        $title = request()->post('title', '');
        $content = request()->post('content', '');
        $seoTitle = request()->post('seo_title', '');
        $seoDesc = request()->post('seo_description', '');
        $seoKeywords = request()->post('seo_keywords', '');

        try {
            $result = $this->aiService->calculateSeoScore($title, $content, $seoTitle, $seoDesc, $seoKeywords);
            return json([
                'code' => 0,
                'msg' => '评分完成',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return json(['code' => 4, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * V3.1: 获取写作风格列表
     * GET /api/ai/styles
     */
    public function styles()
    {
        $styles = Config::get('ai.writing_styles', []);
        $list = [];
        foreach ($styles as $key => $item) {
            $list[] = [
                'key' => $key,
                'name' => $item['name'] ?? $key,
            ];
        }
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => ['list' => $list],
        ]);
    }

    /**
     * V3.1: 社交分享链接生成
     * POST /api/ai/share
     */
    public function share()
    {
        if (empty(session('user_id'))) {
            return json(['code' => 2, 'msg' => '请先登录', 'data' => null]);
        }

        $title = request()->post('title', '');
        $desc = request()->post('description', '');
        $url = request()->post('url', '');
        $cover = request()->post('cover', '');

        if (empty($url)) {
            return json(['code' => 1, 'msg' => '请提供分享链接', 'data' => null]);
        }

        // 生成各平台分享链接（纯前端可用）
        $encodedUrl = urlencode($url);
        $encodedTitle = urlencode($title);
        $encodedDesc = urlencode($desc);

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'url' => $url,
                'weibo' => 'https://service.weibo.com/share/share.php?url=' . $encodedUrl . '&title=' . $encodedTitle,
                'qq' => 'https://connect.qq.com/widget/shareqq/index.html?url=' . $encodedUrl . '&title=' . $encodedTitle . '&desc=' . $encodedDesc,
                'twitter' => 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle,
                'copy' => $url,
                'card' => [
                    'title' => $title,
                    'description' => $desc,
                    'image' => $cover,
                ],
            ],
        ]);
    }
}
