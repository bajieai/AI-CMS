<?php

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;

/**
 * V2.9.35 PERF-5: 性能监控中间件
 * 记录请求加载时间/DB查询/内存/慢请求
 */
class PerformanceMonitorMiddleware
{
    /**
     * DB查询计数
     */
    protected int $dbQueryCount = 0;
    protected float $dbQueryTime = 0;

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // 注册DB查询监听
        Db::listen(function ($sql, $runtime, $master) {
            $this->dbQueryCount++;
            $this->dbQueryTime += (float) $runtime;
        });

        $response = $next($request);

        // 注册shutdown函数记录性能数据
        register_shutdown_function(function () use ($request, $startTime, $startMemory, $response) {
            $endTime = microtime(true);
            $responseTime = (int)(($endTime - $startTime) * 1000); // 毫秒
            $memoryUsage = memory_get_usage() - $startMemory;
            $memoryPeak = memory_get_peak_usage();

            $isSlow = $responseTime > 2000 ? 1 : 0;

            // 采样率控制：非慢请求10%采样
            if (!$isSlow && random_int(1, 100) > 10) {
                return;
            }

            // 异步写入性能日志
            try {
                Db::name('performance_log')->insert([
                    'url'             => mb_substr($request->url(true), 0, 512),
                    'method'          => strtoupper($request->method()),
                    'response_time'   => $responseTime,
                    'db_query_count'  => $this->dbQueryCount,
                    'db_query_time'   => (int)($this->dbQueryTime * 1000),
                    'memory_usage'    => $memoryUsage,
                    'memory_peak'     => $memoryPeak,
                    'status_code'     => $response->getCode(),
                    'is_slow'         => $isSlow,
                    'user_id'         => (int) session('user_id'),
                    'ip'              => $request->ip(),
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable) {
                // 忽略写入失败
            }
        });

        return $response;
    }
}
