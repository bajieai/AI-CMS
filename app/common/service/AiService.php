<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiLog;
use app\common\service\ai\AiProviderFactory;
use app\common\service\ai\AiProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use think\facade\Config;
use think\facade\Log;

/**
 * AI服务门面类
 * V2.4重构：委托给AiProviderFactory + 具体Provider实现
 * 保留原有generate()接口以兼容现有调用方
 */
class AiService
{
    protected ?AiProviderInterface $provider = null;
    protected Client $client;
    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('ai.deepseek');
    }

    /**
     * 获取当前Provider（懒加载）
     */
    protected function getProvider(): AiProviderInterface
    {
        if ($this->provider === null) {
            $this->provider = AiProviderFactory::getDefault();
        }
        return $this->provider;
    }

    /**
     * 设置指定Provider
     */
    public function setProvider(AiProviderInterface $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * 生成内容（兼容原有接口）
     * V3.1: 支持写作风格(style)参数
     */
    public function generate(string $prompt, string $template = 'continue', array $options = []): array
    {
        $templates = Config::get('ai.templates', []);
        $systemPrompt = $templates[$template]['system_prompt'] ?? '';

        // V3.1: 如果指定了写作风格，使用风格对应的system_prompt
        $style = $options['style'] ?? '';
        if (!empty($style)) {
            $styles = Config::get('ai.writing_styles', []);
            if (isset($styles[$style]['system_prompt'])) {
                $systemPrompt = $styles[$style]['system_prompt'];
            }
        }

        if (!empty($systemPrompt)) {
            $options['system_prompt'] = $systemPrompt;
        }

        $provider = $this->getProvider();
        $modelInfo = $provider->getModelInfo();
        $startTime = microtime(true);

        try {
            $content = $provider->write($prompt, $options);
            $duration = intval((microtime(true) - $startTime) * 1000);

            // 记录成功日志
            $this->logCall($modelInfo, 'write', $prompt, $content, $duration, 1);

            return [
                'content' => $content,
                'usage' => null,
                'model' => $modelInfo['model_id'] ?? $this->config['default_model'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            $duration = intval((microtime(true) - $startTime) * 1000);

            // 记录失败日志
            $this->logCall($modelInfo, 'write', $prompt, '', $duration, 2, $e->getMessage());

            // 故障降级：尝试备用Provider
            try {
                $fallback = AiProviderFactory::getFallbackProvider($modelInfo['provider'] ?? null);
                $fallbackInfo = $fallback->getModelInfo();
                $content = $fallback->write($prompt, $options);
                $fallbackDuration = intval((microtime(true) - $startTime) * 1000);

                // 记录降级日志
                $this->logCall($fallbackInfo, 'write', $prompt, $content, $fallbackDuration, 3, '降级: ' . $e->getMessage());

                return [
                    'content' => $content,
                    'usage' => null,
                    'model' => $fallbackInfo['model_id'] ?? 'fallback',
                ];
            } catch (\Exception $fallbackError) {
                throw new \Exception('AI调用失败，已尝试所有可用模型: ' . $e->getMessage());
            }
        }
    }

    /**
     * SEO优化
     */
    public function seoOptimize(string $content, array $keywords = []): array
    {
        return $this->callWithFallback('seoOptimize', $content, $keywords);
    }

    /**
     * 翻译
     */
    public function translate(string $text, string $from = 'zh', string $to = 'en'): string
    {
        return $this->callWithFallback('translate', $text, $from, $to);
    }

    /**
     * 批量翻译 - V2.9新增
     *
     * @param array  $texts  待翻译文本数组 ['key1' => 'text1', 'key2' => 'text2']
     * @param string $from   源语言
     * @param string $to     目标语言
     * @return array ['key1' => 'translated1', 'key2' => 'translated2']
     */
    public function translateBatch(array $texts, string $from = 'zh', string $to = 'en'): array
    {
        if (empty($texts)) {
            return [];
        }

        // 构建批量翻译 prompt
        $prompt = "请将以下JSON中的文本从" . $this->getLangName($from) . "翻译成" . $this->getLangName($to) . "。\n\n";
        $prompt .= "待翻译文本（JSON格式）：\n";
        $prompt .= json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "请严格按原JSON结构返回翻译结果，保持key不变，仅翻译value。\n";
        $prompt .= "只返回JSON，不要包含其他文字说明。";

        try {
            $response = $this->callWithFallback('write', $prompt, [
                'max_tokens' => 4096,
                'temperature' => 0.3,
            ]);

            // 尝试提取 JSON
            preg_match('/\{.*\}/s', $response, $matches);
            if (!empty($matches[0])) {
                $result = json_decode($matches[0], true);
                if (is_array($result)) {
                    return $result;
                }
            }

            // JSON 解析失败，尝试逐条翻译
            $result = [];
            foreach ($texts as $key => $text) {
                try {
                    $result[$key] = $this->translate($text, $from, $to);
                } catch (\Exception) {
                    $result[$key] = $text; // 翻译失败保留原文
                }
            }
            return $result;
        } catch (\Exception $e) {
            // 批量翻译失败，逐条翻译降级
            $result = [];
            foreach ($texts as $key => $text) {
                try {
                    $result[$key] = $this->translate($text, $from, $to);
                } catch (\Exception) {
                    $result[$key] = $text;
                }
            }
            return $result;
        }
    }

    /**
     * 获取语言名称
     */
    protected function getLangName(string $code): string
    {
        $map = [
            'zh'    => '中文',
            'zh-cn' => '简体中文',
            'zh-tw' => '繁体中文',
            'en'    => '英语',
            'ja'    => '日语',
            'ko'    => '韩语',
            'fr'    => '法语',
            'de'    => '德语',
            'es'    => '西班牙语',
            'auto'  => '自动检测',
        ];
        return $map[$code] ?? $code;
    }

    /**
     * 摘要生成
     */
    public function summarize(string $text, int $maxLength = 200): string
    {
        return $this->callWithFallback('summarize', $text, $maxLength);
    }

    /**
     * 通用调用方法（带故障降级）
     */
    protected function callWithFallback(string $method, ...$args): mixed
    {
        $provider = $this->getProvider();
        $modelInfo = $provider->getModelInfo();
        $startTime = microtime(true);

        try {
            $result = $provider->$method(...$args);
            $duration = intval((microtime(true) - $startTime) * 1000);
            $this->logCall($modelInfo, $method, is_string($args[0] ?? '') ? $args[0] : '', is_string($result) ? $result : json_encode($result), $duration, 1);
            return $result;
        } catch (\Exception $e) {
            $duration = intval((microtime(true) - $startTime) * 1000);
            $this->logCall($modelInfo, $method, is_string($args[0] ?? '') ? $args[0] : '', '', $duration, 2, $e->getMessage());

            // 降级
            try {
                $fallback = AiProviderFactory::getFallbackProvider($modelInfo['provider'] ?? null);
                $result = $fallback->$method(...$args);
                $fallbackInfo = $fallback->getModelInfo();
                $fallbackDuration = intval((microtime(true) - $startTime) * 1000);
                $this->logCall($fallbackInfo, $method, is_string($args[0] ?? '') ? $args[0] : '', is_string($result) ? $result : json_encode($result), $fallbackDuration, 3, '降级: ' . $e->getMessage());
                return $result;
            } catch (\Exception $fallbackError) {
                throw new \Exception('AI调用失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 记录AI调用日志
     */
    protected function logCall(array $modelInfo, string $taskType, string $prompt, string $response, int $durationMs, int $status, string $errorMsg = ''): void
    {
        try {
            AiLog::create([
                'model_id'        => $modelInfo['model_id'] ?? 0,
                'task_type'       => $taskType,
                'prompt_length'   => mb_strlen($prompt),
                'response_length' => mb_strlen($response),
                'duration_ms'     => $durationMs,
                'status'          => $status,
                'error_msg'       => $errorMsg,
            ]);
        } catch (\Throwable) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 检查API是否已配置
     */
    public function isConfigured(): bool
    {
        try {
            $provider = $this->getProvider();
            return true;
        } catch (\Throwable) {
            return !empty($this->config['api_key']);
        }
    }

    /**
     * AI图片生成 - V2.8新增
     * 使用门面方法，不修改ImageProviderInterface
     *
     * @param string $prompt 图片描述
     * @param array $options 选项 ['style', 'size', 'count', 'regenerate']
     * @return array 图片信息数组
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        if (empty(trim($prompt))) {
            throw new \Exception('图片描述不能为空');
        }

        // V3.1: 配额检查
        $this->checkImageQuota();

        $factory = new \app\common\service\ai\ImageProviderFactory();
        $provider = $factory->getDefault();

        try {
            $result = $provider->generateImage($prompt, $options);
            // 记录配额使用
            $this->recordImageQuota();
            return $result;
        } catch (\Exception $e) {
            // 尝试降级到备用Provider
            $fallback = \app\common\service\ai\ImageProviderFactory::getFallbackProvider(
                $provider->getImageInfo()['provider'] ?? null
            );

            if ($fallback !== null) {
                $result = $fallback->generateImage($prompt, $options);
                $this->recordImageQuota();
                return $result;
            }

            throw new \Exception('AI配图失败: ' . $e->getMessage());
        }
    }

    /**
     * V3.1: 检查今日配图配额
     * @throws \Exception
     */
    protected function checkImageQuota(): void
    {
        $limit = Config::get('ai.image.daily_limit', 50);
        if ($limit <= 0) return;

        $key = 'ai_image_quota_' . date('Ymd') . '_' . (session('user_id') ?? 'guest');
        $used = cache($key) ?: 0;
        if ($used >= $limit) {
            throw new \Exception('今日AI配图额度已用完（' . $limit . '次/天），请明天再试');
        }
    }

    /**
     * V3.1: 记录配图配额使用
     */
    protected function recordImageQuota(): void
    {
        $key = 'ai_image_quota_' . date('Ymd') . '_' . (session('user_id') ?? 'guest');
        $used = cache($key) ?: 0;
        cache($key, $used + 1, 86400);
    }

    /**
     * V3.1: 获取今日配图配额使用情况
     */
    public function getImageQuota(): array
    {
        $limit = Config::get('ai.image.daily_limit', 50);
        $key = 'ai_image_quota_' . date('Ymd') . '_' . (session('user_id') ?? 'guest');
        $used = cache($key) ?: 0;
        return ['limit' => $limit, 'used' => $used, 'remaining' => max(0, $limit - $used)];
    }

    /**
     * V3.1: 从文章内容提取关键词构建图片Prompt
     *
     * @param string $title 文章标题
     * @param string $content 文章内容（纯文本）
     * @param int $maxKeywords 最大关键词数量
     * @return string 构建好的Prompt
     */
    public function buildImagePrompt(string $title, string $content, int $maxKeywords = 5): string
    {
        $title = trim($title);
        $content = trim(strip_tags($content));

        // 1. 标题优先
        if (!empty($title)) {
            $basePrompt = $title;
        } else {
            $basePrompt = mb_substr($content, 0, 100);
        }

        // 2. 从内容提取高频关键词（简单TF-IDF近似）
        $keywords = $this->extractKeywords($content, $maxKeywords);
        $keywordStr = implode(', ', $keywords);

        // 3. 构建Prompt
        $prompt = "Create an illustration for a blog post";
        if ($title) {
            $prompt .= " titled \"" . $title . "\"";
        }
        if ($keywordStr) {
            $prompt .= ". Keywords: " . $keywordStr;
        }
        $prompt .= ". High quality, detailed, professional.";

        return $prompt;
    }

    /**
     * V3.1: 提取文章关键词（简单词频统计）
     */
    protected function extractKeywords(string $text, int $maxCount = 5): array
    {
        if (empty($text)) return [];

        // 去除HTML标签和标点
        $text = strip_tags($text);
        $text = preg_replace('/[\p{P}\s\d]+/u', ' ', $text);

        // 分词（简单空格分隔，中文需要额外处理）
        $words = [];
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $text)) {
            // 中文内容：提取2-4字词组
            preg_match_all('/[\x{4e00}-\x{9fff}]{2,4}/u', $text, $matches);
            $words = $matches[0] ?? [];
        } else {
            // 英文内容
            $words = array_filter(explode(' ', strtolower($text)), fn($w) => mb_strlen($w) > 3);
        }

        // 停用词过滤
        $stopWords = ['的', '了', '和', '是', '在', '有', '我', '他', '她', '它', '们', '这', '那',
            '一个', '可以', '我们', '你们', '他们', '这个', '那个', '什么', '怎么', '为什么',
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was',
            'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now',
            'old', 'see', 'two', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too',
            'use', 'way', 'will', 'with', 'have', 'from', 'they', 'know', 'want', 'been', 'good',
            'much', 'some', 'time', 'very', 'when', 'come', 'here', 'just', 'like', 'long',
            'make', 'many', 'over', 'such', 'take', 'than', 'them', 'well', 'were'];

        $filtered = array_filter($words, fn($w) => !in_array(strtolower($w), $stopWords, true) && mb_strlen($w) >= 2);

        // 统计词频
        $freq = array_count_values($filtered);
        arsort($freq);

        return array_slice(array_keys($freq), 0, $maxCount);
    }

    /**
     * V3.1: SEO评分纯算法（零AI成本，毫秒响应）
     *
     * @param string $title 文章标题
     * @param string $content 文章内容
     * @param string $seoTitle SEO标题
     * @param string $seoDesc SEO描述
     * @param string $seoKeywords SEO关键词
     * @return array ['score'=>int, 'breakdown'=>[], 'suggestions'=>[]]
     */
    public function calculateSeoScore(string $title, string $content, string $seoTitle = '', string $seoDesc = '', string $seoKeywords = ''): array
    {
        $contentText = strip_tags($content);
        $contentLength = mb_strlen($contentText);
        $suggestions = [];

        // 1. 标题长度评分 (30分) - 理想20-60字符
        $t = trim($seoTitle ?: $title);
        $titleLen = mb_strlen($t);
        if ($titleLen >= 20 && $titleLen <= 60) {
            $titleScore = 30;
        } elseif ($titleLen >= 10 && $titleLen < 20) {
            $titleScore = 20;
            $suggestions[] = '标题建议扩展到20-60字，当前' . $titleLen . '字';
        } elseif ($titleLen > 60) {
            $titleScore = 20;
            $suggestions[] = '标题过长（' . $titleLen . '字），建议精简到60字以内';
        } else {
            $titleScore = 5;
            $suggestions[] = '标题过短，建议设置20-60字的描述性标题';
        }

        // 2. 关键词密度评分 (20分) - 理想1%-3%
        $keywords = array_filter(array_map('trim', explode(',', $seoKeywords)));
        $keywordScore = 0;
        if (count($keywords) >= 3 && count($keywords) <= 10) {
            $keywordScore = 10;
        } elseif (count($keywords) > 0) {
            $keywordScore = 5;
            $suggestions[] = '建议设置3-10个关键词，当前' . count($keywords) . '个';
        } else {
            $suggestions[] = '未设置关键词，建议添加3-10个相关关键词';
        }

        // 检查关键词是否在标题和内容中出现
        $keywordInTitle = 0;
        $keywordInContent = 0;
        foreach ($keywords as $kw) {
            if (mb_stripos($t, $kw) !== false) $keywordInTitle++;
            if (mb_stripos($contentText, $kw) !== false) $keywordInContent++;
        }
        if (count($keywords) > 0) {
            $titleRatio = $keywordInTitle / count($keywords);
            $contentRatio = $keywordInContent / count($keywords);
            if ($titleRatio >= 0.5 && $contentRatio >= 0.5) {
                $keywordScore += 10;
            } elseif ($contentRatio >= 0.3) {
                $keywordScore += 5;
                $suggestions[] = '关键词在标题或内容中出现频率偏低，建议合理布局';
            } else {
                $suggestions[] = '关键词未在内容中有效出现，建议在标题和正文中自然融入关键词';
            }
        }

        // 3. 描述完整性评分 (20分) - 理想50-160字符
        $desc = trim($seoDesc);
        $descLen = mb_strlen($desc);
        if ($descLen >= 50 && $descLen <= 160) {
            $descScore = 20;
        } elseif ($descLen >= 20 && $descLen < 50) {
            $descScore = 12;
            $suggestions[] = 'SEO描述建议扩展到50-160字，当前' . $descLen . '字';
        } elseif ($descLen > 160) {
            $descScore = 12;
            $suggestions[] = 'SEO描述过长（' . $descLen . '字），搜索引擎可能截断显示';
        } else {
            $descScore = 0;
            $suggestions[] = '未设置SEO描述，建议添加50-160字的摘要描述';
        }

        // 4. 内容长度评分 (15分) - 理想>800字
        if ($contentLength >= 1500) {
            $lengthScore = 15;
        } elseif ($contentLength >= 800) {
            $lengthScore = 12;
        } elseif ($contentLength >= 300) {
            $lengthScore = 8;
            $suggestions[] = '内容长度建议达到800字以上，当前' . $contentLength . '字';
        } else {
            $lengthScore = 3;
            $suggestions[] = '内容过短（' . $contentLength . '字），建议扩展内容以提升SEO效果';
        }

        // 5. 图片ALT评分 (15分) - 检查内容中是否有无ALT的图片
        preg_match_all('/<img[^>]*>/i', $content, $imgMatches);
        $totalImages = count($imgMatches[0] ?? []);
        $imagesWithAlt = 0;
        foreach ($imgMatches[0] ?? [] as $imgTag) {
            if (preg_match('/alt=["\'][^"\']+["\']/i', $imgTag)) {
                $imagesWithAlt++;
            }
        }
        if ($totalImages === 0) {
            $altScore = 10;
            $suggestions[] = '建议添加至少1张配图并设置ALT属性，提升内容丰富度';
        } elseif ($imagesWithAlt >= $totalImages) {
            $altScore = 15;
        } elseif ($imagesWithAlt >= $totalImages * 0.5) {
            $altScore = 10;
            $suggestions[] = '部分图片缺少ALT属性，建议为所有图片添加描述性ALT文本';
        } else {
            $altScore = 5;
            $suggestions[] = '大部分图片缺少ALT属性，ALT文本有助于搜索引擎理解图片内容';
        }

        $totalScore = min(100, $titleScore + $keywordScore + $descScore + $lengthScore + $altScore);

        return [
            'score' => $totalScore,
            'breakdown' => [
                'title_length' => ['score' => $titleScore, 'max' => 30, 'label' => '标题长度'],
                'keyword_density' => ['score' => $keywordScore, 'max' => 20, 'label' => '关键词密度'],
                'description' => ['score' => $descScore, 'max' => 20, 'label' => '描述完整性'],
                'content_length' => ['score' => $lengthScore, 'max' => 15, 'label' => '内容长度'],
                'image_alt' => ['score' => $altScore, 'max' => 15, 'label' => '图片ALT'],
            ],
            'suggestions' => $suggestions,
        ];
    }

    /**
     * AI内容质量检测 - V2.8新增
     * 使用门面方法，不修改AiProviderInterface
     * 
     * @param string $content 待检测内容
     * @param array $dimensions 检测维度 ['readability','seo','originality','structure','engagement']
     * @return array ['overall_score'=>int, 'dimensions'=>[], 'suggestions'=>[], 'total_words'=>int]
     */
    public function evaluateContentQuality(string $content, array $dimensions = []): array
    {
        if (empty($content)) {
            return ['overall_score' => 0, 'dimensions' => [], 'suggestions' => [], 'total_words' => 0];
        }

        $prompt = $this->buildQualityPrompt($content, $dimensions);
        
        try {
            $response = $this->callWithFallback('write', $prompt, [
                'max_tokens' => 1024,
                'temperature' => 0.3,
                'system_prompt' => '你是专业的内容质量评估专家。请严格按JSON格式返回评估结果，不要包含任何其他文字。'
            ]);
            
            // 尝试解析JSON响应
            $result = json_decode($response, true);
            
            if (is_array($result)) {
                return [
                    'overall_score' => intval($result['overall_score'] ?? 0),
                    'dimensions' => $result['dimensions'] ?? [],
                    'suggestions' => $result['suggestions'] ?? [],
                    'total_words' => intval($result['total_words'] ?? mb_strlen($content)),
                ];
            }
            
            // JSON解析失败，返回默认结果
            return [
                'overall_score' => 0,
                'dimensions' => [],
                'suggestions' => ['AI返回格式异常，请稍后重试'],
                'total_words' => mb_strlen($content),
            ];
            
        } catch (\Exception $e) {
            // AI调用失败，返回降级结果
            return [
                'overall_score' => 0,
                'dimensions' => [],
                'suggestions' => ['AI质量检测服务暂不可用：' . $e->getMessage()],
                'total_words' => mb_strlen($content),
            ];
        }
    }

    /**
     * V2.9.1 M16b: AI配色推荐
     *
     * @param string $industry  行业类型（如科技/电商/教育/医疗）
     * @param string $style     风格偏好（如现代/简约/活泼/商务）
     * @param string $baseColor 基础色（可选，如#3b82f6）
     * @return array ['primary'=>'#xxx', 'secondary'=>'#xxx', 'accent'=>'#xxx', 'bg'=>'#xxx', 'text'=>'#xxx', 'reason'=>'推荐原因']
     */
    public function colorSuggest(string $industry = '', string $style = '', string $baseColor = ''): array
    {
        $prompt = "你是一位专业的UI/UX设计师，请为以下场景推荐一套网站配色方案。\n\n";
        if ($industry) $prompt .= "行业: {$industry}\n";
        if ($style) $prompt .= "风格偏好: {$style}\n";
        if ($baseColor) $prompt .= "基础色: {$baseColor}\n";
        $prompt .= "\n请返回JSON格式（仅JSON，不要其他文字）:\n";
        $prompt .= "{\n";
        $prompt .= '  "primary": "#主色",' . "\n";
        $prompt .= '  "secondary": "#辅色",' . "\n";
        $prompt .= '  "accent": "#强调色",' . "\n";
        $prompt .= '  "bg": "#背景色",' . "\n";
        $prompt .= '  "bg_secondary": "#次背景色",' . "\n";
        $prompt .= '  "text": "#主文字色",' . "\n";
        $prompt .= '  "text_secondary": "#次文字色",' . "\n";
        $prompt .= '  "border": "#边框色",' . "\n";
        $prompt .= '  "reason": "推荐原因（50字以内）"' . "\n";
        $prompt .= "}\n";

        try {
            $response = $this->callWithFallback('write', $prompt, [
                'max_tokens'  => 512,
                'temperature' => 0.5,
            ]);

            preg_match('/\{.*\}/s', $response, $matches);
            if (!empty($matches[0])) {
                $result = json_decode($matches[0], true);
                if (is_array($result) && !empty($result['primary'])) {
                    return $result;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[AiService] AI配色推荐失败: ' . $e->getMessage());
        }

        // HSL降级：根据行业返回预设配色
        return self::getPresetColors($industry, $style, $baseColor);
    }

    /**
     * V2.9.1 M16b: HSL降级配色方案
     */
    public static function getPresetColors(string $industry = '', string $style = '', string $baseColor = ''): array
    {
        // 行业预设
        $industryMap = [
            '科技' => ['primary' => '#3b82f6', 'secondary' => '#64748b', 'accent' => '#06b6d4'],
            '电商' => ['primary' => '#f97316', 'secondary' => '#64748b', 'accent' => '#ef4444'],
            '教育' => ['primary' => '#22c55e', 'secondary' => '#64748b', 'accent' => '#eab308'],
            '医疗' => ['primary' => '#06b6d4', 'secondary' => '#64748b', 'accent' => '#10b981'],
            '金融' => ['primary' => '#1e40af', 'secondary' => '#64748b', 'accent' => '#f59e0b'],
            '政务' => ['primary' => '#dc2626', 'secondary' => '#64748b', 'accent' => '#f59e0b'],
        ];

        $colors = $industryMap[$industry] ?? ['primary' => '#3b82f6', 'secondary' => '#64748b', 'accent' => '#f59e0b'];

        // 如果提供了基础色，以其为primary
        if ($baseColor && preg_match('/^#[0-9a-fA-F]{6}$/', $baseColor)) {
            $colors['primary'] = $baseColor;
        }

        return array_merge($colors, [
            'bg'             => '#ffffff',
            'bg_secondary'   => '#f8fafc',
            'text'           => '#1e293b',
            'text_secondary' => '#64748b',
            'border'         => '#e2e8f0',
            'reason'         => '基于行业特征和色彩理论的HSL降级推荐方案',
        ]);
    }

    /**
     * 构建质量检测Prompt
     */
    protected function buildQualityPrompt(string $content, array $dimensions = []): string
    {
        $defaultDimensions = ['readability', 'seo', 'originality', 'structure', 'engagement'];
        $checkDimensions = empty($dimensions) ? $defaultDimensions : $dimensions;
        
        $dimensionNames = [
            'readability' => '可读性',
            'seo' => 'SEO优化',
            'originality' => '原创性',
            'structure' => '结构完整性',
            'engagement' => '吸引力',
        ];
        
        $dimensionList = [];
        foreach ($checkDimensions as $dim) {
            $dimensionList[] = ($dimensionNames[$dim] ?? $dim) . '(0-100分)';
        }
        
        $prompt = "请对以下内容进行质量评估，返回JSON格式：\n\n";
        $prompt .= "评估维度：\n";
        foreach ($dimensionList as $i => $dim) {
            $prompt .= ($i + 1) . ". {$dim}\n";
        }
        
        $prompt .= "\n返回格式示例：\n";
        $prompt .= "{\n";
        $prompt .= '  "overall_score": 85,' . "\n";
        $prompt .= '  "dimensions": {' . "\n";
        $prompt .= '    "readability": 90,' . "\n";
        $prompt .= '    "seo": 80,' . "\n";
        $prompt .= '    "originality": 85,' . "\n";
        $prompt .= '    "structure": 90,' . "\n";
        $prompt .= '    "engagement": 80' . "\n";
        $prompt .= '  },' . "\n";
        $prompt .= '  "suggestions": ["建议1", "建议2"],' . "\n";
        $prompt .= '  "total_words": 1500' . "\n";
        $prompt .= "}\n\n";
        
        $prompt .= "待评估内容：\n" . mb_substr($content, 0, 3000) . (mb_strlen($content) > 3000 ? "\n...(内容已截断)" : "");
        
        return $prompt;
    }
}
