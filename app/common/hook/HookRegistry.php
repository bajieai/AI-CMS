<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\hook;

use think\facade\Log;

/**
 * Hook 事件注册表管理器 — V2.9.25 K-3/M-1
 *
 * 统一管理系统级 Hook 事件的注册和触发。
 * 替代 PluginService::fire 和 PluginMarketService::on/fire 双系统。
 *
 * 特性：
 * - 支持优先级（数字越大越先执行）
 * - 支持中断传播（监听器返回 code=-1 时停止）
 * - 支持数据过滤（监听器可修改并返回新数据）
 * - 向后兼容（旧调用通过桥接方法代理到本类）
 *
 * @see HookEvents 事件常量定义
 * @see HookContext 事件上下文
 * @see HookResult 触发结果
 */
class HookRegistry
{
    /** @var array<string, array> 事件监听器 [event => [[listener, priority], ...]] */
    protected static array $listeners = [];

    /** @var bool 调试模式（记录触发日志） */
    protected static bool $debugMode = false;

    /** @var array 调试日志（最近 1000 条） */
    protected static array $debugLogs = [];

    /** @var int 调试日志上限 */
    protected static int $debugLogLimit = 1000;

    /**
     * 注册事件监听器
     *
     * @param string $event 事件名称（建议使用 HookEvents 常量）
     * @param callable $listener 回调函数，接收 HookContext 参数，可返回数组 ['code'=>0/-1, 'message'=>'', 'data'=>mixed]
     * @param int $priority 优先级（数字越大越先执行，默认 10）
     */
    public static function on(string $event, callable $listener, int $priority = 10): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];
        // 按优先级降序排序
        usort(self::$listeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * 触发事件
     *
     * @param string $event 事件名称
     * @param array $data 事件数据
     * @param array $context 执行上下文（user_id, ip, module 等）
     * @return HookResult 触发结果
     */
    public static function fire(string $event, array $data = [], array $context = []): HookResult
    {
        $result = new HookResult();
        $startTime = microtime(true);

        $ctx = new HookContext($event, $data, $context);

        if (empty(self::$listeners[$event])) {
            $result->data = $data;
            $result->elapsed = (microtime(true) - $startTime) * 1000;
            self::logDebug($event, $ctx, $result, 'no-listener');
            return $result;
        }

        $currentData = $data;

        foreach (self::$listeners[$event] as $item) {
            $listener = $item['listener'];

            try {
                $response = call_user_func($listener, $ctx);

                // 处理监听器返回值
                if (is_array($response)) {
                    $result->responses[] = $response;

                    // 检查是否阻塞
                    if (($response['code'] ?? 0) === -1) {
                        $result->code = -1;
                        $result->message = $response['message'] ?? '事件被监听器阻塞';
                        $result->stopped = true;
                        $result->data = $currentData;
                        $result->elapsed = (microtime(true) - $startTime) * 1000;
                        self::logDebug($event, $ctx, $result, 'blocked');
                        return $result;
                    }

                    // 监听器可修改数据
                    if (isset($response['data'])) {
                        $currentData = $response['data'];
                        $ctx->data = $currentData;
                    }
                } elseif ($response !== null) {
                    $result->responses[] = $response;
                    $currentData = $response;
                    $ctx->data = $currentData;
                }
            } catch (\Throwable $e) {
                Log::warning('[HookRegistry] 事件 ' . $event . ' 监听器执行失败: ' . $e->getMessage());
                $result->responses[] = ['code' => -1, 'message' => $e->getMessage()];
            }
        }

        $result->data = $currentData;
        $result->elapsed = (microtime(true) - $startTime) * 1000;
        self::logDebug($event, $ctx, $result, 'completed');
        return $result;
    }

    /**
     * 移除事件监听器
     *
     * @param string $event 事件名称
     * @param callable|null $listener 指定回调（null=移除该事件所有监听器）
     */
    public static function off(string $event, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset(self::$listeners[$event]);
            return;
        }

        if (!isset(self::$listeners[$event])) {
            return;
        }

        self::$listeners[$event] = array_values(array_filter(
            self::$listeners[$event],
            fn($item) => $item['listener'] !== $listener
        ));

        if (empty(self::$listeners[$event])) {
            unset(self::$listeners[$event]);
        }
    }

    /**
     * 获取事件全部监听器
     *
     * @param string|null $event 事件名称（null=返回所有）
     * @return array
     */
    public static function getListeners(?string $event = null): array
    {
        if ($event !== null) {
            return self::$listeners[$event] ?? [];
        }
        return self::$listeners;
    }

    /**
     * 获取已注册事件列表（含监听器数量）
     *
     * @return array<string, int> [事件名 => 监听器数量]
     */
    public static function getRegisteredEvents(): array
    {
        $result = [];
        foreach (self::$listeners as $event => $listeners) {
            $result[$event] = count($listeners);
        }
        return $result;
    }

    /**
     * 清空所有监听器
     */
    public static function clear(): void
    {
        self::$listeners = [];
    }

    /**
     * 开启/关闭调试模式
     */
    public static function setDebugMode(bool $enabled): void
    {
        self::$debugMode = $enabled;
    }

    /**
     * 获取调试日志
     */
    public static function getDebugLogs(int $limit = 100): array
    {
        return array_slice(array_reverse(self::$debugLogs), 0, $limit);
    }

    /**
     * 清空调试日志
     */
    public static function clearDebugLogs(): void
    {
        self::$debugLogs = [];
    }

    /**
     * 记录调试日志
     */
    protected static function logDebug(string $event, HookContext $ctx, HookResult $result, string $status): void
    {
        if (!self::$debugMode) {
            return;
        }

        self::$debugLogs[] = [
            'event' => $event,
            'trace_id' => $ctx->traceId,
            'timestamp' => date('Y-m-d H:i:s', (int)($ctx->timestamp / 1000000)) . sprintf('.%06d', $ctx->timestamp % 1000000),
            'status' => $status,
            'code' => $result->code,
            'message' => $result->message,
            'elapsed_ms' => round($result->elapsed, 2),
            'listener_count' => count($result->responses),
            'data' => $ctx->sanitize(),
        ];

        // 超出上限时移除最早的记录
        if (count(self::$debugLogs) > self::$debugLogLimit) {
            array_shift(self::$debugLogs);
        }
    }
}
