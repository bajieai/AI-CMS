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
 * OpenAI兼容Provider - V2.5新增
 * 通用Provider，支持所有兼容OpenAI格式的API（如OpenAI、Groq、Mistral等）
 */
class OpenaiCompatibleProvider implements AiProviderInterface
{
    protected \app\common\model\AiModel $model;
    protected Client $client;
    protected int $maxRetries = 2;
    protected int $retryDelay = 1000;

    public function __construct(\app\common\model\AiModel $model)
    {
        $this->model = $model;
        $apiBase = $model->api_base ?: 'https://api.openai.com/v1';
        $apiKey = \app\common\service\AiModelService::getDecryptedApiKey($model);

        $this->client = new Client([
            'base_uri' => rtrim($apiBase, '/'),
            'timeout' => 60,
            'connect_timeout' => 10,
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
            'model' => $this->model->model_id ?? 'gpt-3.5-turbo',
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
        $prompt = "请对以下内容进行SEO优化，目标关键词：{$keywordStr}\n\n要求：1.优化标题 2.提取关键词 3.生成描述(150字内) 4.优化正文\n\nJSON格式：{\"seo_title\":\"...\",\"seo_keywords\":\"...\",\"seo_description\":\"...\",\"optimized_content\":\"...\"}\n\n原文：\n{$content}";
        $result = $this->write($prompt);
        $parsed = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['seo_title'])) return $parsed;
        return ['optimized_content' => $result, 'seo_title' => '', 'seo_keywords' => $keywordStr, 'seo_description' => mb_substr(strip_tags($result), 0, 150)];
    }

    public function translate(string $text, string $from = 'zh', string $to = 'en'): string
    {
        $langMap = ['zh' => '中文', 'en' => '英文', 'ja' => '日文', 'ko' => '韩文'];
        return $this->write("Please translate the following from {$langMap[$from]} to {$langMap[$to]}, return only the translation:\n\n" . $text);
    }

    public function summarize(string $text, int $maxLength = 200): string
    {
        return $this->write("Please summarize the following in {$maxLength} characters or less:\n\n" . $text);
    }

    public function getModelInfo(): array
    {
        return ['provider' => 'openai', 'model_id' => $this->model->model_id ?? 'gpt-3.5-turbo', 'capabilities' => explode(',', $this->model->capabilities ?? 'write,seo,translate,summarize')];
    }

    protected function sendWithRetry(string $uri, array $data, int $retryCount = 0): array
    {
        try {
            $response = $this->client->post($uri, ['json' => $data]);
            $result = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('响应JSON解析失败');
            return $result;
        } catch (ConnectException $e) {
            if ($retryCount < $this->maxRetries) {
                usleep($this->retryDelay * 1000);
                return $this->sendWithRetry($uri, $data, $retryCount + 1);
            }
            throw new \Exception('连接AI服务失败');
        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            if (in_array($statusCode, [429, 500, 502, 503, 504]) && $retryCount < $this->maxRetries) {
                usleep($this->retryDelay * 1000 * ($retryCount + 1));
                return $this->sendWithRetry($uri, $data, $retryCount + 1);
            }
            throw new \Exception('AI服务请求失败: ' . $e->getMessage());
        }
    }
}
