<?php
declare(strict_types=1);

namespace app\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use think\facade\Cache;
use think\facade\Config;
use Psr\Http\Message\StreamInterface;

/**
 * DeepSeek AI服务
 */
class DeepSeekAiService
{
    /**
     * HTTP客户端
     */
    protected Client $client;

    /**
     * API配置
     */
    protected array $config;

    /**
     * 请求配置
     */
    protected array $requestConfig;

    /**
     * 缓存配置
     */
    protected array $cacheConfig;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = Config::get('ai.deepseek');
        $this->requestConfig = Config::get('ai.request');
        $this->cacheConfig = Config::get('ai.cache');
        
        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout' => $this->requestConfig['timeout'],
            'connect_timeout' => $this->requestConfig['connect_timeout'],
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * 生成内容(同步)
     */
    public function generate(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->config['default_model'];
        $systemPrompt = $options['system_prompt'] ?? '';
        $temperature = $options['temperature'] ?? $this->config['temperature'];
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'];
        
        // 检查缓存
        if ($this->cacheConfig['enabled']) {
            $cacheKey = $this->getCacheKey($prompt, $model, $temperature, $maxTokens);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // 构建消息
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];
        
        // 添加历史消息
        if (!empty($options['messages'])) {
            $messages = array_merge($messages, $options['messages']);
        }
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) $temperature,
            'max_tokens' => (int) $maxTokens,
        ];
        
        // 可选参数
        if (isset($options['top_p'])) {
            $data['top_p'] = (float) $options['top_p'];
        }
        if (isset($options['frequency_penalty'])) {
            $data['frequency_penalty'] = (float) $options['frequency_penalty'];
        }
        if (isset($options['presence_penalty'])) {
            $data['presence_penalty'] = (float) $options['presence_penalty'];
        }
        if (isset($options['stop'])) {
            $data['stop'] = $options['stop'];
        }
        
        // 发送请求(带重试)
        $response = $this->sendWithRetry('POST', '/chat/completions', $data);
        
        // 解析响应
        $result = $this->parseResponse($response);
        
        // 缓存结果
        if ($this->cacheConfig['enabled'] && !empty($result['content'])) {
            Cache::set($cacheKey, $result, $this->cacheConfig['ttl']);
        }
        
        return $result;
    }

    /**
     * 生成内容(流式SSE)
     */
    public function generateStream(string $prompt, array $options = [], callable $callback = null): array
    {
        $model = $options['model'] ?? $this->config['default_model'];
        $systemPrompt = $options['system_prompt'] ?? '';
        $temperature = $options['temperature'] ?? $this->config['temperature'];
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'];
        
        // 构建消息
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) $temperature,
            'max_tokens' => (int) $maxTokens,
            'stream' => true,
        ];
        
        try {
            $response = $this->client->post('/chat/completions', [
                'json' => $data,
            ]);
            
            $body = $response->getBody();
            $fullContent = '';
            $usage = null;
            
            // 处理SSE流
            while (!$body->eof()) {
                $line = $body->read(8192);
                
                if (str_starts_with($line, 'data: ')) {
                    $json = trim(substr($line, 6));
                    
                    if ($json === '[DONE]') {
                        break;
                    }
                    
                    $chunk = json_decode($json, true);
                    if ($chunk && isset($chunk['choices'][0]['delta']['content'])) {
                        $content = $chunk['choices'][0]['delta']['content'];
                        $fullContent .= $content;
                        
                        // 回调
                        if ($callback) {
                            $callback($content, $chunk);
                        }
                    }
                    
                    // 记录usage
                    if (isset($chunk['usage'])) {
                        $usage = $chunk['usage'];
                    }
                }
            }
            
            return [
                'content' => $fullContent,
                'usage' => $usage,
                'model' => $model,
                'finish_reason' => 'stop',
            ];
            
        } catch (GuzzleException $e) {
            throw new \Exception('AI服务请求失败: ' . $e->getMessage());
        }
    }

    /**
     * 计算成本
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $models = Config::get('ai.models', []);
        
        if (isset($models[$model])) {
            $modelConfig = $models[$model];
            $inputCost = ($inputTokens / 1000) * $modelConfig['input_price'];
            $outputCost = ($outputTokens / 1000) * $modelConfig['output_price'];
            return $inputCost + $outputCost;
        }
        
        return 0.0;
    }

    /**
     * 获取可用模型列表
     */
    public function getAvailableModels(): array
    {
        return Config::get('ai.models', []);
    }

    /**
     * 获取模型信息
     */
    public function getModelInfo(string $model): ?array
    {
        $models = Config::get('ai.models', []);
        return $models[$model] ?? null;
    }

    /**
     * 带重试发送请求
     */
    protected function sendWithRetry(string $method, string $uri, array $data, int $retryCount = 0): array
    {
        $maxRetries = $this->requestConfig['retry_times'];
        $retryDelay = $this->requestConfig['retry_delay'];
        
        try {
            $response = $this->client->post($uri, [
                'json' => $data,
            ]);
            
            $body = (string) $response->getBody();
            $result = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('响应JSON解析失败');
            }
            
            return $result;
            
        } catch (ConnectException $e) {
            // 连接错误可重试
            if ($retryCount < $maxRetries) {
                usleep($retryDelay * 1000);
                return $this->sendWithRetry($method, $uri, $data, $retryCount + 1);
            }
            throw new \Exception('连接AI服务失败，请检查网络');
            
        } catch (RequestException $e) {
            // 请求错误
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            
            // 429和5xx错误可重试
            if (in_array($statusCode, [429, 500, 502, 503, 504]) && $retryCount < $maxRetries) {
                usleep($retryDelay * 1000 * ($retryCount + 1));
                return $this->sendWithRetry($method, $uri, $data, $retryCount + 1);
            }
            
            // 4xx错误不重试
            if ($statusCode >= 400 && $statusCode < 500) {
                $error = json_decode((string) $e->getResponse()->getBody(), true);
                $message = $error['error']['message'] ?? $e->getMessage();
                throw new \Exception("AI服务请求错误: {$message}");
            }
            
            throw new \Exception('AI服务请求失败: ' . $e->getMessage());
        }
    }

    /**
     * 解析响应
     */
    protected function parseResponse(array $response): array
    {
        $content = '';
        $usage = null;
        $finishReason = 'stop';
        
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }
        
        if (isset($response['choices'][0]['finish_reason'])) {
            $finishReason = $response['choices'][0]['finish_reason'];
        }
        
        if (isset($response['usage'])) {
            $usage = $response['usage'];
        }
        
        return [
            'content' => $content,
            'usage' => $usage,
            'model' => $response['model'] ?? $this->config['default_model'],
            'finish_reason' => $finishReason,
        ];
    }

    /**
     * 获取缓存键
     */
    protected function getCacheKey(string $prompt, string $model, float $temperature, int $maxTokens): string
    {
        return $this->cacheConfig['prefix'] . md5(json_encode([
            'prompt' => $prompt,
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]));
    }

    /**
     * 检查API配置
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    /**
     * 测试连接
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->generate('Hello', ['max_tokens' => 10]);
            return !empty($response['content']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
