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

use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.15: 翻译Provider路由器
 *
 * 根据配置路由到对应翻译Provider，支持故障降级。
 * 首发仅注册 en/ja/ko 三种语言。
 */
class TranslateProviderRouter
{
    /** @var array Provider实例缓存 */
    protected static array $instances = [];

    /** @var array 已注册语言代码 */
    protected static array $registeredLangs = ['en', 'ja', 'ko'];

    /**
     * 获取指定Provider实例
     */
    public static function getProvider(string $name = ''): TranslateProviderInterface
    {
        if (empty($name)) {
            $name = Config::get('ai.translate.provider', 'deepseek');
        }

        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $instance = match ($name) {
            'deepseek' => new DeepSeekTranslateProvider(),
            'openai'   => new OpenAITranslateProvider(),
            default    => throw new \RuntimeException('不支持的翻译Provider: ' . $name),
        };

        self::$instances[$name] = $instance;
        return $instance;
    }

    /**
     * 执行翻译（自动选择Provider）
     *
     * @param string $text       待翻译文本
     * @param string $targetLang 目标语言代码
     * @param array  $options    可选参数
     * @return array ['success'=>bool, 'text'=>string, 'provider'=>string, 'message'=>string]
     */
    public static function translate(string $text, string $targetLang, array $options = []): array
    {
        if (!in_array($targetLang, self::$registeredLangs, true)) {
            return [
                'success' => false,
                'text'    => '',
                'provider' => '',
                'message' => "不支持的目标语言: {$targetLang}。当前仅支持: " . implode(', ', self::$registeredLangs),
            ];
        }

        $providerName = Config::get('ai.translate.provider', 'deepseek');
        $fallbackName = Config::get('ai.translate.fallback_provider', '');

        try {
            $provider = self::getProvider($providerName);
            $result = $provider->translate($text, $targetLang, $options);

            if (!$result['success'] && !empty($fallbackName) && $fallbackName !== $providerName) {
                $fallbackResult = self::tryFallback($text, $targetLang, $options, $fallbackName);
                if ($fallbackResult !== null) {
                    return $fallbackResult;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error("[TranslateProviderRouter] translate failed: " . $e->getMessage());

            // 尝试fallback
            if (!empty($fallbackName) && $fallbackName !== $providerName) {
                $fallbackResult = self::tryFallback($text, $targetLang, $options, $fallbackName);
                if ($fallbackResult !== null) {
                    return $fallbackResult;
                }
            }

            return [
                'success' => false,
                'text'    => '',
                'provider' => $providerName,
                'message' => '翻译路由失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 尝试fallback Provider
     */
    protected static function tryFallback(string $text, string $targetLang, array $options, string $fallbackName): ?array
    {
        try {
            $fallback = self::getProvider($fallbackName);
            $result = $fallback->translate($text, $targetLang, $options);
            if ($result['success']) {
                $result['fallback'] = true;
                $result['fallback_provider'] = $fallbackName;
            }
            return $result;
        } catch (\Throwable $e) {
            Log::error("[TranslateProviderRouter] fallback {$fallbackName} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取已注册的语言列表
     * @return array ['en'=>'英语', 'ja'=>'日语', 'ko'=>'韩语']
     */
    public static function getRegisteredLanguages(): array
    {
        return [
            'en' => '英语',
            'ja' => '日语',
            'ko' => '韩语',
        ];
    }

    /**
     * 检查语言是否已注册
     */
    public static function isLanguageRegistered(string $lang): bool
    {
        return in_array($lang, self::$registeredLangs, true);
    }

    /**
     * 清除Provider实例缓存
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
