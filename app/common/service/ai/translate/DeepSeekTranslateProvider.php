<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai\translate;

use GuzzleHttp\Client;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.15: DeepSeek 翻译Provider
 *
 * 基于DeepSeek Chat API实现多语言翻译。
 * 密钥配置: .env → DEEPSEEK_API_KEY=sk-xxx
 *          config/ai.php → translate.api_key 读取 env('DEEPSEEK_API_KEY')
 */
class DeepSeekTranslateProvider implements TranslateProviderInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        // 优先读取 translate 专属配置，回退到通用 deepseek 配置
        $config = Config::get('ai.translate', []);
        if (empty($config)) {
            $config = Config::get('ai.deepseek', []);
        }

        $this->apiKey  = $config['api_key'] ?? '';
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://api.deepseek.com', '/');
        $this->model   = $config['model'] ?? 'deepseek-chat';
        $this->timeout = (int) ($config['timeout'] ?? 60);

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $this->timeout,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function translate(string $text, string $targetLang, array $options = []): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'text'    => '',
                'provider' => $this->getProviderName(),
                'message' => 'DeepSeek API密钥未配置',
            ];
        }

        $langName = $this->getLangName($targetLang);
        $preserveHtml = $options['preserveHtml'] ?? true;
        $context = $options['context'] ?? '';

        // 构建翻译Prompt
        $systemPrompt = $this->buildSystemPrompt($langName, $preserveHtml, $context);

        try {
            $response = $this->client->post('/chat/completions', [
                'json' => [
                    'model'       => $this->model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $text],
                    ],
                    'temperature' => 0.3, // 低温度确保翻译一致性
                    'max_tokens'  => 4096,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $translated = $body['choices'][0]['message']['content'] ?? '';

            if (empty($translated)) {
                return [
                    'success' => false,
                    'text'    => '',
                    'provider' => $this->getProviderName(),
                    'message' => '翻译结果为空',
                ];
            }

            // 清理可能的代码块标记
            $translated = $this->stripCodeBlock($translated);

            return [
                'success'  => true,
                'text'     => $translated,
                'provider' => $this->getProviderName(),
                'message'  => '翻译成功',
            ];
        } catch (\Exception $e) {
            Log::error('[DeepSeekTranslate] ' . $e->getMessage());
            return [
                'success' => false,
                'text'    => '',
                'provider' => $this->getProviderName(),
                'message' => '翻译失败: ' . $e->getMessage(),
            ];
        }
    }

    public function getProviderName(): string
    {
        return 'deepseek';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getSupportedLanguages(): array
    {
        // V2.9.15 首发仅支持 en/ja/ko
        return ['en', 'ja', 'ko'];
    }

    /**
     * 构建翻译系统提示词
     */
    protected function buildSystemPrompt(string $langName, bool $preserveHtml, string $context = ''): string
    {
        $prompt = "你是一位专业翻译。请将用户提供的文本翻译成{$langName}。";

        if ($preserveHtml) {
            $prompt .= "\n重要规则：\n"
                . "1. 保留所有HTML标签不变（如<p>、<div>、<h2>等），只翻译标签内的文本内容。\n"
                . "2. 不要修改任何属性值（如class、id、src等）。\n"
                . "3. 保持原文的段落结构和格式。\n";
        }

        if (!empty($context)) {
            $prompt .= "\n上下文：{$context}\n";
        }

        $prompt .= "\n请直接输出翻译结果，不要添加任何解释、前缀或代码块标记。";

        return $prompt;
    }

    /**
     * 获取语言显示名称
     */
    protected function getLangName(string $lang): string
    {
        return match ($lang) {
            'en' => '英语',
            'ja' => '日语',
            'ko' => '韩语',
            default => $lang,
        };
    }

    /**
     * 去除可能的Markdown代码块标记
     */
    protected function stripCodeBlock(string $text): string
    {
        $text = preg_replace('/^```[\w]*\n?/', '', $text);
        $text = preg_replace('/\n?```$/', '', $text);
        return trim($text);
    }
}
