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

namespace app\common\service\ai;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * DeepSeek AI Provider
 * 基于原AiService重构，实现AiProviderInterface
 */
class DeepSeekProvider implements AiProviderInterface
{
    protected \app\common\model\AiModel $model;
    protected Client $client;
    protected int $maxRetries;
    protected int $retryDelay;

    public function __construct(\app\common\model\AiModel $model)
    {
        $this->model = $model;
        $this->maxRetries = (int) config('ai.request.retry_times', 2);
        $this->retryDelay = (int) config('ai.request.retry_delay', 1000);

        $apiBase = $model->api_base ?: env('ai.deepseek_base_url', 'https://api.deepseek.com');
        $apiKey = $model->api_key ?: env('ai.deepseek_api_key', '');

        $this->client = new Client([
            'base_uri' => rtrim($apiBase, '/') . '/v1',
            'timeout' => (int) config('ai.request.timeout', 60),
            'connect_timeout' => (int) config('ai.request.connect_timeout', 10),
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function write(string $prompt, array $options = []): string
    {
        $systemPrompt = $options['system_prompt'] ?? '';
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $data = [
            'model' => $this->model->model_id ?? 'deepseek-chat',
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? $this->model->temperature ?? 0.7),
            'max_tokens' => (int) ($options['max_tokens'] ?? $this->model->max_tokens ?? 2000),
        ];

        $response = $this->sendWithRetry('/chat/completions', $data);
        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function seoOptimize(string $content, array $keywords = []): array
    {
        $keywordStr = !empty($keywords) ? implode(',', $keywords) : '自动分析';
        $prompt = "请对以下内容进行SEO优化，目标关键词：{$keywordStr}\n\n要求：\n1. 优化标题（SEO Title）\n2. 提取关键词（逗号分隔）\n3. 生成描述（150字以内）\n4. 优化正文内容\n\n请按以下JSON格式返回：\n{\"seo_title\":\"...\",\"seo_keywords\":\"...\",\"seo_description\":\"...\",\"optimized_content\":\"...\"}\n\n原文内容：\n{$content}";

        $result = $this->write($prompt);

        // 尝试解析JSON
        $parsed = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['seo_title'])) {
            return $parsed;
        }

        return [
            'optimized_content' => $result,
            'seo_title' => '',
            'seo_keywords' => $keywordStr,
            'seo_description' => mb_substr(strip_tags($result), 0, 150),
        ];
    }

    public function translate(string $text, string $from = 'zh', string $to = 'en'): string
    {
        $langMap = ['zh' => '中文', 'en' => '英文', 'ja' => '日文', 'ko' => '韩文'];
        $fromLang = $langMap[$from] ?? $from;
        $toLang = $langMap[$to] ?? $to;
        return $this->write("请将以下内容从{$fromLang}翻译为{$toLang}，只返回翻译结果：\n\n" . $text);
    }

    public function summarize(string $text, int $maxLength = 200): string
    {
        return $this->write("请将以下内容总结为{$maxLength}字以内的摘要，只返回摘要内容：\n\n" . $text);
    }

    public function getModelInfo(): array
    {
        return [
            'provider' => 'deepseek',
            'model_id' => $this->model->model_id ?? 'deepseek-chat',
            'capabilities' => explode(',', $this->model->capabilities ?? 'write,seo,translate,summarize'),
        ];
    }

    /**
     * 带重试的HTTP请求
     */
    protected function sendWithRetry(string $uri, array $data, int $retryCount = 0): array
    {
        try {
            $response = $this->client->post($uri, ['json' => $data]);
            $body = (string) $response->getBody();
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('响应JSON解析失败');
            }

            return $result;

        } catch (ConnectException $e) {
            if ($retryCount < $this->maxRetries) {
                usleep($this->retryDelay * 1000);
                return $this->sendWithRetry($uri, $data, $retryCount + 1);
            }
            throw new \Exception('连接AI服务失败，请检查网络');

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

            if (in_array($statusCode, [429, 500, 502, 503, 504]) && $retryCount < $this->maxRetries) {
                usleep($this->retryDelay * 1000 * ($retryCount + 1));
                return $this->sendWithRetry($uri, $data, $retryCount + 1);
            }

            if ($statusCode >= 400 && $statusCode < 500) {
                $error = json_decode((string) $e->getResponse()->getBody(), true);
                $message = $error['error']['message'] ?? $e->getMessage();
                throw new \Exception("AI服务请求错误: {$message}");
            }

            throw new \Exception('AI服务请求失败: ' . $e->getMessage());
        }
    }
}
