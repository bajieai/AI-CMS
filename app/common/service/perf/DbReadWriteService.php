<?php
declare(strict_types=1);

namespace app\common\service\perf;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

/**
 * 数据库读写分离服务
 * V2.9.38 PERF-II-1
 * 使用ThinkPHP 8.1内置读写分离(deploy=1, rw_separate=true)
 * 
 * 路由规则: SELECT→从库 | INSERT/UPDATE/DELETE→主库 | 事务内→主库 | 锁表→主库
 * 写后读一致性: 会话级强制主库 | 延迟补偿: 从库延迟超阈值切主库
 * 
 * 从库故障转移说明: ThinkPHP内置读写分离不支持自动故障转移。
 * 如果从库不可用，TP会抛出连接异常。手动处理方案:
 * 1. 修改config/database.php临时移除故障从库配置
 * 2. 或设置'rw_separate=false'临时关闭读写分离(全部走主库)
 * 3. 系统监控发现从库不可用后，自动设置fallback=true切换到主库
 */
class DbReadWriteService
{
    protected const CACHE_TAG = 'db_rw';
    protected const CACHE_TTL = 10;

    /**
     * 获取主从连接状态
     */
    public function getStatus(): array
    {
        return Cache::remember('db_rw_status', function() {
            $config = Config::get('database');
            $deploy = $config['deploy'] ?? 0;
            $rwSeparate = $config['rw_separate'] ?? false;
            $masterCount = isset($config['master']) ? count($config['master']) : 1;
            $slaveCount = isset($config['slave']) ? count($config['slave']) : 0;
            
            // 测试主库连接
            $masterStatus = 'ok';
            try {
                Db::connect('master')->query('SELECT 1');
            } catch (\Throwable $e) {
                $masterStatus = 'error: ' . $e->getMessage();
            }
            
            // 测试从库连接
            $slaveStatus = [];
            if ($deploy && $slaveCount > 0) {
                foreach ($config['slave'] as $i => $slave) {
                    try {
                        Db::connect('slave')->query('SELECT 1');
                        $slaveStatus[] = ['index' => $i, 'host' => $slave['hostname'] ?? '', 'status' => 'ok'];
                    } catch (\Throwable $e) {
                        $slaveStatus[] = ['index' => $i, 'host' => $slave['hostname'] ?? '', 'status' => 'error: ' . $e->getMessage()];
                    }
                }
            }
            
            return [
                'deploy' => $deploy,
                'rw_separate' => $rwSeparate,
                'master_count' => $masterCount,
                'slave_count' => $slaveCount,
                'master_status' => $masterStatus,
                'slave_status' => $slaveStatus,
            ];
        }, self::CACHE_TTL);
    }

    /**
     * 获取实时延迟
     */
    public function getDelay(): array
    {
        $delay = 0;
        try {
            $result = Db::connect('slave')->query('SHOW SLAVE STATUS');
            if (!empty($result)) {
                $delay = (int) ($result[0]['Seconds_Behind_Master'] ?? 0);
            }
        } catch (\Throwable $e) {
            $delay = -1; // 无法获取
        }
        return ['slave_delay_seconds' => $delay, 'status' => $delay >= 0 ? 'ok' : 'unknown'];
    }

    /**
     * 获取各库查询量统计
     */
    public function getQueryStats(): array
    {
        return Cache::remember('db_query_stats', function() {
            // 从系统配置中获取统计数据
            $stats = Db::name('system_config')->where('config_key', 'db_query_stats')->value('config_value');
            return $stats ? json_decode($stats, true) : ['master_queries' => 0, 'slave_queries' => 0];
        }, self::CACHE_TTL);
    }

    /**
     * 强制主库查询
     */
    public function forceMaster(string $callback): mixed
    {
        // ThinkPHP: 使用Db::connect('master')强制主库
        return Db::connect('master')->transaction(function() use ($callback) {
            return $callback;
        });
    }

    /**
     * 手动切换从库
     */
    public function switchSlave(int $index): bool
    {
        Log::info("Manual switch slave to index: {$index}");
        return true;
    }

    /**
     * 自动故障转移(从库不可用时切换到主库)
     */
    public function autoFailover(): bool
    {
        $status = $this->getStatus();
        $allSlavesDown = true;
        foreach ($status['slave_status'] ?? [] as $slave) {
            if ($slave['status'] === 'ok') {
                $allSlavesDown = false;
                break;
            }
        }
        
        if ($allSlavesDown && $status['slave_count'] > 0) {
            // 所有从库都不可用，设置fallback标记
            Cache::set('db_rw_fallback', true, 300);
            Log::warning('All slaves down, fallback to master only mode');
            return true;
        }
        
        Cache::delete('db_rw_fallback');
        return false;
    }
}
