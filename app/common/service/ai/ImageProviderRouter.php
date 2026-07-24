<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Config;
use think\facade\Log;

/**
 * V2.9.16: AI配图Provider路由器（增强版）
 *
 * 功能增强：
 *   - 链式降级（多Provider遍历，不再仅限于单fallback）
 *   - fallback_chain 配置支持
 *   - 更完善的错误处理
 */
class ImageProviderRouter
{
    /** @var array Provider实例缓存 */
    protected static array $instances = [];

    /**
     * 获取指定Provider实例
     */
    public static function getProvider(string $name): ImageProviderInterface
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $provider = ImageProviderFactory::createProvider($name);
        self::$instances[$name] = $provider;
        return $provider;
    }

    /**
     * 查询异步任务状态（委托给对应Provider）
     *
     * V2.9.16: 增强链式降级，遍历 fallback_chain
     */
    public static function queryTaskStatus(string $taskId, string $provider): array
    {
        try {
            $instance = self::getProvider($provider);
            return $instance->queryTaskStatus($taskId);
        } catch (\Throwable $e) {
            Log::error("[ImageProviderRouter] queryTaskStatus failed: provider={$provider}, taskId={$taskId}, error=" . $e->getMessage());

            // V2.9.16: 链式降级，遍历所有备用Provider
            $fallbackResult = self::tryFallbackChain($taskId, $provider);
            if ($fallbackResult !== null) {
                return $fallbackResult;
            }

            return [
                'success'  => false,
                'url'      => '',
                'failed'   => true,
                'message'  => 'Provider查询失败: ' . $e->getMessage(),
                'progress' => 0,
            ];
        }
    }

    /**
     * V2.9.16: 链式降级 — 遍历所有已启用的Provider，返回第一个成功的结果
     *
     * 降级顺序：tongyi → flux → dalle（按配置 fallback_chain 或默认顺序）
     */
    protected static function tryFallbackChain(string $taskId, string $excludeProvider): ?array
    {
        $config = Config::get('ai.image', []);
        $fallbackChain = $config['fallback_chain'] ?? ['tongyi_wanxiang', 'flux', 'dalle'];

        // 过滤掉当前失败的Provider和未启用的
        $providersConfig = $config['providers'] ?? [];
        $candidates = [];

        foreach ($fallbackChain as $candidate) {
            if ($candidate === $excludeProvider) {
                continue;
            }
            // 检查Provider是否已启用（有api_key即视为启用）
            $pc = $providersConfig[$candidate] ?? [];
            if (!empty($pc['api_key']) && ($pc['enabled'] ?? true)) {
                $candidates[] = $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            try {
                $fallback = self::getProvider($candidate);
                $result = $fallback->queryTaskStatus($taskId);
                if ($result['success']) {
                    $result['fallback'] = true;
                    $result['fallback_provider'] = $candidate;
                    Log::info("[ImageProviderRouter] 链式降级成功: {$candidate} → taskId={$taskId}");
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning("[ImageProviderRouter] fallback {$candidate} failed: " . $e->getMessage());
            }
        }

        Log::error("[ImageProviderRouter] 所有备用Provider均失败，taskId={$taskId}");
        return null;
    }

    /**
     * 清除Provider实例缓存（用于测试或配置热更新）
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
