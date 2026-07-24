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
use GuzzleHttp\Exception\RequestException;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.16: OpenAI 翻译Provider（完整实现）
 *
 * 基于 OpenAI Chat Completions API 实现多语言翻译。
 * 支持 GPT-4o / GPT-4o-mini / GPT-4-turbo 等模型。
 *
 * 配置读取优先级：
 *   1. config/ai.php → translate.providers.openai.*
 *   2. config/ai.php → translate.*（通用配置回退）
 *   3. .env → OPENAI_API_KEY / OPENAI_BASE_URL
 */
class OpenAITranslateProvider implements TranslateProviderInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected int $maxRetries;
    protected int $retryDelay;

    public function __construct()
    {
        $config = Config::get('ai.translate', []);

        // 优先读取 openai 专属配置
        $openaiConfig = $config['providers']['openai'] ?? [];

        $this->apiKey  = $openaiConfig['api_key'] ?? $config['api_key'] ?? env('OPENAI_API_KEY', '');
        $this->baseUrl = rtrim(
            $openaiConfig['base_url'] ?? $config['base_url'] ?? env('OPENAI_BASE_URL', 'https://api.openai.com'),
            '/'
        );
        $this->model   = $openaiConfig['model'] ?? $config['model'] ?? env('OPENAI_MODEL', 'gpt-4o-mini');
        $this->timeout = (int) ($openaiConfig['timeout'] ?? $config['timeout'] ?? 60);

        // 重试配置
        $this->maxRetries = (int) ($config['max_retries'] ?? 2);
        $this->retryDelay = (int) ($config['retry_delay'] ?? 1000);

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
                'success'  => false,
                'text'     => '',
                'provider' => $this->getProviderName(),
                'message'  => 'OpenAI API密钥未配置',
            ];
        }

        $langName     = $this->getLangName($targetLang);
        $preserveHtml = $options['preserveHtml'] ?? true;
        $context      = $options['context'] ?? '';
        $segmentThreshold = $options['segment_threshold']
            ?? Config::get('ai.translate.segment_threshold', 1500);

        // 长文本自动分段翻译
        if (mb_strlen($text) > $segmentThreshold) {
            return $this->translateSegmented($text, $targetLang, $langName, $preserveHtml, $context, $segmentThreshold);
        }

        return $this->doTranslate($text, $targetLang, $langName, $preserveHtml, $context);
    }

    /**
     * 执行单次翻译（带重试）
     */
    protected function doTranslate(string $text, string $targetLang, string $langName, bool $preserveHtml, string $context = ''): array
    {
        $systemPrompt = $this->buildSystemPrompt($langName, $preserveHtml, $context);

        $lastException = null;
        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            if ($attempt > 0) {
                $delay = $this->retryDelay * (2 ** ($attempt - 1)); // 指数退避
                usleep($delay * 1000);
                Log::info("[OpenAITranslate] 第{$attempt}次重试，延迟{$delay}ms");
            }

            try {
                $response = $this->client->post('/v1/chat/completions', [
                    'json' => [
                        'model'       => $this->model,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $text],
                        ],
                        'temperature' => 0.3,
                        'max_tokens'  => 4096,
                    ],
                ]);

                $body = json_decode($response->getBody()->getContents(), true);
                $translated = $body['choices'][0]['message']['content'] ?? '';

                if (empty($translated)) {
                    return [
                        'success'  => false,
                        'text'     => '',
                        'provider' => $this->getProviderName(),
                        'message'  => '翻译结果为空',
                    ];
                }

                $translated = $this->stripCodeBlock($translated);

                return [
                    'success'  => true,
                    'text'     => $translated,
                    'provider' => $this->getProviderName(),
                    'message'  => '翻译成功',
                ];
            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->getResponse()?->getStatusCode();
                // 429/500/502/503/504 可重试，4xx 客户端错误不重试
                if ($statusCode && in_array($statusCode, [429, 500, 502, 503, 504], true)) {
                    Log::warning("[OpenAITranslate] HTTP {$statusCode}，准备重试: " . $e->getMessage());
                    continue;
                }
                break;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::error('[OpenAITranslate] ' . $e->getMessage());
                break;
            }
        }

        return [
            'success'  => false,
            'text'     => '',
            'provider' => $this->getProviderName(),
            'message'  => '翻译失败: ' . ($lastException?->getMessage() ?? '未知错误'),
        ];
    }

    /**
     * 长文本分段翻译
     */
    protected function translateSegmented(string $text, string $targetLang, string $langName, bool $preserveHtml, string $context, int $threshold): array
    {
        $segments = $this->splitText($text, $threshold);
        $results  = [];
        $errors   = [];

        foreach ($segments as $index => $segment) {
            $result = $this->doTranslate($segment, $targetLang, $langName, $preserveHtml, $context);
            // $targetLang 在 doTranslate 的提示词构建中使用
            if ($result['success']) {
                $results[] = $result['text'];
            } else {
                $errors[] = "第" . ($index + 1) . "段失败: " . $result['message'];
                // 保留原文段落，确保完整性
                $results[] = $segment;
            }
        }

        if (!empty($errors)) {
            Log::warning('[OpenAITranslate] 分段翻译部分失败: ' . implode('; ', $errors));
        }

        return [
            'success'  => empty($errors) || !empty($results),
            'text'     => implode("\n\n", $results),
            'provider' => $this->getProviderName(),
            'message'  => empty($errors) ? '分段翻译成功' : '部分段落翻译失败: ' . implode('; ', $errors),
            'errors'   => $errors,
        ];
    }

    /**
     * 按段落分割文本
     */
    protected function splitText(string $text, int $threshold): array
    {
        $paragraphs = preg_split('/\n{2,}/', $text);
        $segments   = [];
        $current    = '';

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) continue;

            if (mb_strlen($current) + mb_strlen($para) > $threshold && !empty($current)) {
                $segments[] = $current;
                $current = $para;
            } else {
                $current .= ($current ? "\n\n" : '') . $para;
            }
        }

        if (!empty($current)) {
            $segments[] = $current;
        }

        // 兜底：如果某段仍然过长，强制按句子切割
        $finalSegments = [];
        foreach ($segments as $seg) {
            if (mb_strlen($seg) > $threshold * 1.5) {
                $sentences = preg_split('/(?<=[。！？.!?])\s*/u', $seg);
                $tmp = '';
                foreach ($sentences as $sent) {
                    if (mb_strlen($tmp) + mb_strlen($sent) > $threshold && !empty($tmp)) {
                        $finalSegments[] = $tmp;
                        $tmp = $sent;
                    } else {
                        $tmp .= $sent;
                    }
                }
                if (!empty($tmp)) $finalSegments[] = $tmp;
            } else {
                $finalSegments[] = $seg;
            }
        }

        return $finalSegments;
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getSupportedLanguages(): array
    {
        return TranslateLanguageConfig::getSupportedCodes();
    }

    /**
     * 构建翻译系统提示词
     */
    protected function buildSystemPrompt(string $langName, bool $preserveHtml, string $context = ''): string
    {
        $prompt = "You are a professional translator. Please translate the user's text into {$langName}.";

        if ($preserveHtml) {
            $prompt .= "\nImportant rules:\n"
                . "1. Keep all HTML tags unchanged (e.g., <p>, <div>, <h2>), only translate the text inside tags.\n"
                . "2. Do not modify any attribute values (e.g., class, id, src).\n"
                . "3. Maintain the original paragraph structure and formatting.\n";
        }

        if (!empty($context)) {
            $prompt .= "\nContext: {$context}\n";
        }

        $prompt .= "\nPlease output the translation directly without any explanations, prefixes, or code block markers.";

        return $prompt;
    }

    /**
     * 获取语言显示名称
     */
    protected function getLangName(string $lang): string
    {
        return TranslateLanguageConfig::getLangName($lang);
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
