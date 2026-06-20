<?php

declare(strict_types=1);

namespace app\common\hook;

/**
 * Hook 便捷门面类 — V2.9.25 K-3/M-1
 *
 * 提供静态方法快速调用 HookRegistry，无需手动实例化。
 * 同时提供向后兼容的桥接方法，将旧版 PluginService::fire / PluginMarketService::on
 * 代理到新的 HookRegistry。
 */
class Hook
{
    /**
     * 注册事件监听器（静态代理到 HookRegistry::on）
     */
    public static function on(string $event, callable $listener, int $priority = 10): void
    {
        HookRegistry::on($event, $listener, $priority);
    }

    /**
     * 触发事件（静态代理到 HookRegistry::fire）
     */
    public static function fire(string $event, array $data = [], array $context = []): HookResult
    {
        return HookRegistry::fire($event, $data, $context);
    }

    /**
     * 移除事件监听器（静态代理到 HookRegistry::off）
     */
    public static function off(string $event, ?callable $listener = null): void
    {
        HookRegistry::off($event, $listener);
    }

    /**
     * 获取已注册事件列表
     */
    public static function getRegisteredEvents(): array
    {
        return HookRegistry::getRegisteredEvents();
    }

    /**
     * 获取调试日志
     */
    public static function getDebugLogs(int $limit = 100): array
    {
        return HookRegistry::getDebugLogs($limit);
    }

    /**
     * 开启/关闭调试模式
     */
    public static function setDebugMode(bool $enabled): void
    {
        HookRegistry::setDebugMode($enabled);
    }

    /**
     * 兼容旧版 PluginMarketService::on()
     */
    public static function legacyOn(string $event, callable $listener, int $priority = 10): void
    {
        self::on($event, $listener, $priority);
    }

    /**
     * 兼容旧版 PluginMarketService::fire()
     */
    public static function legacyFire(string $event, array $data = [], array $context = []): HookResult
    {
        return self::fire($event, $data, $context);
    }
}
