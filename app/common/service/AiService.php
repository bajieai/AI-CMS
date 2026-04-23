<?php
declare(strict_types=1);

namespace app\common\service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use think\facade\Config;

/**
 * AI服务（DeepSeek API直连）
 * 参考V1.0 DeepSeekAiService重写，移除JWT/Redis队列依赖
 */
class AiService
{
    protected Client $client;
    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('ai.deepseek');
        
        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout' => Config::get('ai.request.timeout', 60),
            'connect_timeout' => Config::get('ai.request.connect_timeout', 10),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * 生成内容
     */
    public function generate(string $prompt, string $template = 'continue', array $options = []): array
    {
        $templates = Config::get('ai.templates', []);
        $systemPrompt = $templates[$template]['system_prompt'] ?? '';
        
        $model = $options['model'] ?? $this->config['default_model'];
        $temperature = $options['temperature'] ?? $this->config['temperature'];
        $maxTokens = $options['max_tokens'] ?? $this->config['max_tokens'];

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) $temperature,
            'max_tokens' => (int) $maxTokens,
        ];

        $response = $this->sendWithRetry('POST', '/chat/completions', $data);

        return $this->parseResponse($response);
    }

    /**
     * 带重试发送请求
     */
    protected function sendWithRetry(string $method, string $uri, array $data, int $retryCount = 0): array
    {
        $maxRetries = Config::get('ai.request.retry_times', 2);
        $retryDelay = Config::get('ai.request.retry_delay', 1000);

        try {
            $response = $this->client->post($uri, ['json' => $data]);
            $body = (string) $response->getBody();
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('响应JSON解析失败');
            }

            return $result;

        } catch (ConnectException $e) {
            if ($retryCount < $maxRetries) {
                usleep($retryDelay * 1000);
                return $this->sendWithRetry($method, $uri, $data, $retryCount + 1);
            }
            throw new \Exception('连接AI服务失败，请检查网络');

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

            if (in_array($statusCode, [429, 500, 502, 503, 504]) && $retryCount < $maxRetries) {
                usleep($retryDelay * 1000 * ($retryCount + 1));
                return $this->sendWithRetry($method, $uri, $data, $retryCount + 1);
            }

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
        $content = $response['choices'][0]['message']['content'] ?? '';
        $usage = $response['usage'] ?? null;

        return [
            'content' => $content,
            'usage' => $usage,
            'model' => $response['model'] ?? $this->config['default_model'],
        ];
    }

    /**
     * 检查API是否已配置
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }
}
