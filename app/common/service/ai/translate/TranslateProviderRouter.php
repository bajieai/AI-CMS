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

use think\facade\Cache;
use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.16: 翻译Provider路由器（增强版）
 *
 * 功能增强：
 *   - 插件化自动注册 Provider（支持第三方插件注入）
 *   - 内置速率限制检查（RPM/RPH）
 *   - 动态语言列表（从 TranslateLanguageConfig 读取）
 *   - 多 Provider 遍历降级（fallback 链）
 */
class TranslateProviderRouter
{
    /** @var array Provider实例缓存 */
    protected static array $instances = [];

    /** @var array 内置Provider类名映射 */
    protected static array $builtInProviders = [
        'deepseek' => DeepSeekTranslateProvider::class,
        'openai'   => OpenAITranslateProvider::class,
    ];

    /** @var array 插件注册的自定义Provider */
    protected static array $customProviders = [];

    /**
     * V2.9.16: 注册自定义翻译Provider（供插件调用）
     *
     * @param string $name  Provider标识名，如 'google'
     * @param string $class 完整类名，必须实现 TranslateProviderInterface
     */
    public static function registerProvider(string $name, string $class): void
    {
        if (!is_subclass_of($class, TranslateProviderInterface::class)) {
            throw new \InvalidArgumentException("Provider类 {$class} 必须实现 TranslateProviderInterface");
        }
        self::$customProviders[$name] = $class;
        Log::info("[TranslateProviderRouter] 注册翻译Provider: {$name} => {$class}");
    }

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

        // V2.9.16: 先查自定义Provider，再查内置
        $class = self::$customProviders[$name] ?? self::$builtInProviders[$name] ?? null;

        if ($class === null || !class_exists($class)) {
            throw new \RuntimeException('不支持的翻译Provider: ' . $name);
        }

        $instance = new $class();
        self::$instances[$name] = $instance;
        return $instance;
    }

    /**
     * 获取所有已注册的Provider名称列表
     */
    public static function getRegisteredProviders(): array
    {
        return array_merge(
            array_keys(self::$builtInProviders),
            array_keys(self::$customProviders)
        );
    }

    /**
     * 执行翻译（自动选择Provider）
     *
     * V2.9.16 增强：
     *   - 翻译前检查速率限制
     *   - fallback 支持多 Provider 链式遍历
     */
    public static function translate(string $text, string $targetLang, array $options = []): array
    {
        if (!TranslateLanguageConfig::isSupported($targetLang)) {
            return [
                'success'  => false,
                'text'     => '',
                'provider' => '',
                'message'  => "不支持的目标语言: {$targetLang}。当前支持: " . implode(', ', TranslateLanguageConfig::getSupportedCodes()),
            ];
        }

        $providerName = Config::get('ai.translate.provider', 'deepseek');
        $fallbackName = Config::get('ai.translate.fallback_provider', '');

        // V2.9.16: 速率限制检查
        if (!self::checkRateLimit($providerName)) {
            Log::warning("[TranslateProviderRouter] Provider {$providerName} 触发速率限制，尝试fallback");
            // 主Provider被限速时，直接尝试fallback链
            return self::tryFallbackChain($text, $targetLang, $options, $providerName);
        }

        try {
            $provider = self::getProvider($providerName);
            $result = $provider->translate($text, $targetLang, $options);

            if (!$result['success'] && !empty($fallbackName)) {
                $fallbackResult = self::tryFallbackChain($text, $targetLang, $options, $providerName);
                if ($fallbackResult !== null && $fallbackResult['success']) {
                    return $fallbackResult;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error("[TranslateProviderRouter] translate failed: " . $e->getMessage());

            if (!empty($fallbackName)) {
                $fallbackResult = self::tryFallbackChain($text, $targetLang, $options, $providerName);
                if ($fallbackResult !== null && $fallbackResult['success']) {
                    return $fallbackResult;
                }
            }

            return [
                'success'  => false,
                'text'     => '',
                'provider' => $providerName,
                'message'  => '翻译路由失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * V2.9.16: 多Provider链式降级
     *
     * 遍历除主Provider外的所有可用Provider，返回第一个成功的结果
     */
    protected static function tryFallbackChain(string $text, string $targetLang, array $options, string $excludeProvider): ?array
    {
        $allProviders = self::getRegisteredProviders();
        $fallbackOrder = Config::get('ai.translate.fallback_chain', []);

        // 如果配置了fallback_chain，按配置顺序；否则遍历所有Provider
        $candidates = !empty($fallbackOrder)
            ? $fallbackOrder
            : $allProviders;

        foreach ($candidates as $candidate) {
            if ($candidate === $excludeProvider) {
                continue;
            }

            try {
                // 检查速率限制
                if (!self::checkRateLimit($candidate)) {
                    Log::info("[TranslateProviderRouter] Fallback {$candidate} 触发速率限制，跳过");
                    continue;
                }

                $provider = self::getProvider($candidate);
                if (!$provider->isAvailable()) {
                    continue;
                }

                $result = $provider->translate($text, $targetLang, $options);
                if ($result['success']) {
                    $result['fallback'] = true;
                    $result['fallback_provider'] = $candidate;
                    Log::info("[TranslateProviderRouter] Fallback 成功: {$candidate}");
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::error("[TranslateProviderRouter] fallback {$candidate} failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * V2.9.16: 基于Cache的速率限制检查
     *
     * @param string $providerName Provider标识名
     * @return bool true=允许翻译, false=已超限
     */
    protected static function checkRateLimit(string $providerName): bool
    {
        $config = Config::get('ai.translate.rate_limit', []);
        $rpm = (int) ($config['rpm'] ?? 0);
        $rph = (int) ($config['rph'] ?? 0);

        if ($rpm <= 0 && $rph <= 0) {
            return true; // 未配置限速则放行
        }

        $now = time();
        $minuteKey = "translate_rate_rpm_{$providerName}_" . intval($now / 60);
        $hourKey   = "translate_rate_rph_{$providerName}_" . intval($now / 3600);

        if ($rpm > 0) {
            $rpmCount = (int) Cache::get($minuteKey, 0);
            if ($rpmCount >= $rpm) {
                return false;
            }
            Cache::set($minuteKey, $rpmCount + 1, 120);
        }

        if ($rph > 0) {
            $rphCount = (int) Cache::get($hourKey, 0);
            if ($rphCount >= $rph) {
                return false;
            }
            Cache::set($hourKey, $rphCount + 1, 7200);
        }

        return true;
    }

    /**
     * 获取已注册的语言列表
     * @return array ['zh' => '中文', 'en' => '英语', ...]
     */
    public static function getRegisteredLanguages(): array
    {
        return TranslateLanguageConfig::getDropdownOptions();
    }

    /**
     * 检查语言是否已注册
     */
    public static function isLanguageRegistered(string $lang): bool
    {
        return TranslateLanguageConfig::isSupported($lang);
    }

    /**
     * 清除Provider实例缓存
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
