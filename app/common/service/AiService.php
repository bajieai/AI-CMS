<?php
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
     */
    public function generate(string $prompt, string $template = 'continue', array $options = []): array
    {
        $templates = Config::get('ai.templates', []);
        $systemPrompt = $templates[$template]['system_prompt'] ?? '';

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

        $factory = new \app\common\service\ai\ImageProviderFactory();
        $provider = $factory->getDefault();
        
        try {
            $result = $provider->generateImage($prompt, $options);
            return $result;
        } catch (\Exception $e) {
            // 尝试降级到备用Provider
            $fallback = \app\common\service\ai\ImageProviderFactory::getFallbackProvider(
                $provider->getImageInfo()['provider'] ?? null
            );
            
            if ($fallback !== null) {
                return $fallback->generateImage($prompt, $options);
            }
            
            throw new \Exception('AI配图失败: ' . $e->getMessage());
        }
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
