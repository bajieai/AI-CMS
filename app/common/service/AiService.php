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
}
