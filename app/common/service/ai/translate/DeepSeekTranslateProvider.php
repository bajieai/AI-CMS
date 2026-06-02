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
 * V2.9.16: DeepSeek 翻译Provider（增强版）
 *
 * 基于DeepSeek Chat API实现多语言翻译。
 * 增强内容：
 *   - 支持16种语言（含中文、欧洲语系、东南亚语系）
 *   - 自动重试机制（指数退避）
 *   - 长文本自动分段翻译
 *   - 更完善的错误处理
 *
 * 密钥配置: .env → DEEPSEEK_API_KEY=sk-xxx
 *           config/ai.php → translate.api_key 读取 env('DEEPSEEK_API_KEY')
 */
class DeepSeekTranslateProvider implements TranslateProviderInterface
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
        // 优先读取 translate 专属配置，回退到通用 deepseek 配置
        $config = Config::get('ai.translate', []);
        if (empty($config)) {
            $config = Config::get('ai.deepseek', []);
        }

        $this->apiKey  = $config['api_key'] ?? '';
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://api.deepseek.com', '/');
        $this->model   = $config['model'] ?? 'deepseek-chat';
        $this->timeout = (int) ($config['timeout'] ?? 60);

        // V2.9.16: 重试配置
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
                'message'  => 'DeepSeek API密钥未配置',
            ];
        }

        $langName     = $this->getLangName($targetLang);
        $preserveHtml = $options['preserveHtml'] ?? true;
        $context      = $options['context'] ?? '';
        $segmentThreshold = $options['segment_threshold']
            ?? Config::get('ai.translate.segment_threshold', 1500);

        // V2.9.16: 长文本自动分段翻译
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
                Log::info("[DeepSeekTranslate] 第{$attempt}次重试，延迟{$delay}ms");
            }

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
                        'success'  => false,
                        'text'     => '',
                        'provider' => $this->getProviderName(),
                        'message'  => '翻译结果为空',
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
            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->getResponse()?->getStatusCode();
                // 429/500/502/503/504 可重试
                if ($statusCode && in_array($statusCode, [429, 500, 502, 503, 504], true)) {
                    Log::warning("[DeepSeekTranslate] HTTP {$statusCode}，准备重试: " . $e->getMessage());
                    continue;
                }
                break;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::error('[DeepSeekTranslate] ' . $e->getMessage());
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
     * V2.9.16: 长文本分段翻译
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
            Log::warning('[DeepSeekTranslate] 分段翻译部分失败: ' . implode('; ', $errors));
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
        return 'deepseek';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getSupportedLanguages(): array
    {
        // V2.9.16: 从统一配置类读取，消除硬编码
        return TranslateLanguageConfig::getSupportedCodes();
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
