<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\provider\AiProviderFactory;
use think\facade\Cache;

/**
 * AI编辑器选段翻译服务 — V2.9.28 A-4
 */
class AiEditorTranslateService
{
    private AiProviderFactory $factory;

    /**
     * 支持的10种语言
     */
    public static array $languages = [
        'en' => '英文',
        'ja' => '日文',
        'ko' => '韩文',
        'fr' => '法文',
        'de' => '德文',
        'es' => '西班牙文',
        'ru' => '俄文',
        'ar' => '阿拉伯文',
        'pt' => '葡萄牙文',
        'zh' => '中文',
    ];

    public function __construct()
    {
        $this->factory = new AiProviderFactory();
    }

    /**
     * 选段翻译
     *
     * @param string $text 原文
     * @param string $targetLang 目标语言代码
     * @param string $mode 模式: replace/insert/compare
     * @return array
     */
    public function translate(string $text, string $targetLang, string $mode = 'replace'): array
    {
        $langLabel = self::$languages[$targetLang] ?? '英文';
        $cacheKey = 'ai_translate_' . md5($text . $targetLang);

        // 翻译记忆：优先使用缓存
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $this->formatResult($text, $cached, $mode, true);
        }

        $systemPrompt = "你是一个专业翻译。请将以下文本翻译为{$langLabel}。"
            . "保持原文的格式和语气，只返回翻译结果，不要添加解释。";

        try {
            $provider = $this->factory->getDefault();
            $response = $provider->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text],
            ], [
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            $translated = $response['content'] ?? $text;

            // 缓存翻译结果（1小时）
            Cache::set($cacheKey, $translated, 3600);

            return $this->formatResult($text, $translated, $mode, false);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '翻译失败: ' . $e->getMessage()];
        }
    }

    /**
     * 格式化翻译结果
     */
    private function formatResult(string $original, string $translated, string $mode, bool $fromCache): array
    {
        $result = [
            'success' => true,
            'original' => $original,
            'translated' => $translated,
            'from_cache' => $fromCache,
            'mode' => $mode,
        ];

        switch ($mode) {
            case 'insert':
                $result['result'] = $original . "\n\n---\n\n" . $translated;
                break;
            case 'compare':
                $result['result'] = "【原文】\n" . $original . "\n\n【译文】\n" . $translated;
                break;
            case 'replace':
            default:
                $result['result'] = $translated;
                break;
        }

        return $result;
    }
}
