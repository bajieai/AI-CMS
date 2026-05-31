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
 * V2.9.15: AI配图Provider路由器
 *
 * 根据Provider名称路由到对应实现，统一封装查询任务状态等操作。
 * 支持故障降级到fallback Provider。
 *
 * @todo V2.9.16 实现链式降级: tongyi→flux→mock
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
     * @param string $taskId   任务ID
     * @param string $provider Provider名称
     * @return array ['success'=>bool, 'url'=>string, 'failed'=>bool, 'message'=>string]
     */
    public static function queryTaskStatus(string $taskId, string $provider): array
    {
        try {
            $instance = self::getProvider($provider);
            return $instance->queryTaskStatus($taskId);
        } catch (\Throwable $e) {
            Log::error("[ImageProviderRouter] queryTaskStatus failed: provider={$provider}, taskId={$taskId}, error=" . $e->getMessage());

            // 故障降级：尝试fallback Provider
            $fallbackResult = self::tryFallback($taskId, $provider);
            if ($fallbackResult !== null) {
                return $fallbackResult;
            }

            return [
                'success' => false,
                'url' => '',
                'failed' => true,
                'message' => 'Provider查询失败: ' . $e->getMessage(),
                'progress' => 0,
            ];
        }
    }

    /**
     * 尝试fallback Provider查询
     * @todo V2.9.16 支持链式降级: tongyi→flux→mock
     */
    protected static function tryFallback(string $taskId, string $currentProvider): ?array
    {
        $config = Config::get('ai.image', []);
        $fallbackName = $config['fallback_provider'] ?? '';

        if (empty($fallbackName) || $fallbackName === $currentProvider) {
            return null;
        }

        try {
            $fallback = self::getProvider($fallbackName);
            $result = $fallback->queryTaskStatus($taskId);
            if ($result['success']) {
                $result['fallback'] = true;
                $result['fallback_provider'] = $fallbackName;
            }
            return $result;
        } catch (\Throwable $e) {
            Log::error("[ImageProviderRouter] fallback {$fallbackName} also failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 清除Provider实例缓存（用于测试或配置热更新）
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
