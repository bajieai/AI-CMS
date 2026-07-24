<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Cache;

/**
 * V2.9.35 SEC-1: 安全配置服务
 * 三级安全策略(宽松/标准/严格)管理
 * 配置存储在i8j_system_config表的security_config(JSON)字段
 */
class SecurityConfigService
{
    protected const CACHE_KEY = 'security_config_v2935';
    protected const CACHE_TTL = 3600;

    /**
     * 获取安全配置
     */
    public function getConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, function () {
            // 文件配置作为基础
            $fileConfig = Config::get('security', []);

            // 合并数据库配置（覆盖文件配置）
            try {
                $dbConfig = $this->getDbConfig();
                if (!empty($dbConfig)) {
                    $fileConfig = $this->mergeConfig($fileConfig, $dbConfig);
                }
            } catch (\Throwable) {
                // 数据库配置读取失败，使用文件配置
            }

            return $fileConfig;
        }, self::CACHE_TTL);
    }

    /**
     * 获取安全级别
     */
    public function getLevel(): string
    {
        $config = $this->getConfig();
        return $config['level'] ?? 'standard';
    }

    /**
     * 设置安全级别
     */
    public function setLevel(string $level): bool
    {
        if (!in_array($level, ['relaxed', 'standard', 'strict'], true)) {
            return false;
        }

        $config = $this->getDbConfig();
        $config['level'] = $level;

        // 根据级别自动调整子配置
        $config = $this->applyLevelDefaults($config, $level);

        $this->saveDbConfig($config);
        $this->clearCache();

        return true;
    }

    /**
     * 保存安全配置
     */
    public function saveConfig(array $config): bool
    {
        // 过滤敏感字段
        unset($config['encryption']['master_key']);

        $this->saveDbConfig($config);
        $this->clearCache();

        return true;
    }

    /**
     * 从数据库读取配置
     */
    protected function getDbConfig(): array
    {
        $row = \think\facade\Db::name('system_config')
            ->where('name', 'security_config')
            ->find();

        if (!$row || empty($row['value'])) {
            return [];
        }

        $value = $row['value'];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * 保存配置到数据库
     */
    protected function saveDbConfig(array $config): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        $exists = \think\facade\Db::name('system_config')
            ->where('name', 'security_config')
            ->find();

        if ($exists) {
            \think\facade\Db::name('system_config')
                ->where('name', 'security_config')
                ->update(['value' => $json]);
        } else {
            \think\facade\Db::name('system_config')
                ->insert([
                    'name'  => 'security_config',
                    'value' => $json,
                ]);
        }
    }

    /**
     * 根据级别应用默认配置
     */
    protected function applyLevelDefaults(array $config, string $level): array
    {
        switch ($level) {
            case 'strict':
                $config['xss_input']['enabled'] = true;
                $config['sql_injection']['enabled'] = true;
                $config['sql_injection']['mode'] = 'block';
                $config['csrf']['bind_ip'] = true;
                $config['csrf']['token_ttl'] = 1800;
                $config['password']['min_length'] = 12;
                $config['password']['require_special'] = true;
                $config['password']['max_login_attempts'] = 3;
                break;
            case 'standard':
                $config['xss_input']['enabled'] = true;
                $config['sql_injection']['enabled'] = true;
                $config['sql_injection']['mode'] = 'block';
                $config['csrf']['bind_ip'] = false;
                $config['csrf']['token_ttl'] = 1800;
                $config['password']['min_length'] = 8;
                $config['password']['require_special'] = false;
                $config['password']['max_login_attempts'] = 5;
                break;
            case 'relaxed':
                $config['xss_input']['enabled'] = true;
                $config['sql_injection']['enabled'] = true;
                $config['sql_injection']['mode'] = 'log';
                $config['csrf']['bind_ip'] = false;
                $config['csrf']['token_ttl'] = 3600;
                $config['password']['min_length'] = 6;
                $config['password']['require_special'] = false;
                $config['password']['max_login_attempts'] = 10;
                break;
        }

        return $config;
    }

    /**
     * 合并配置（数据库配置覆盖文件配置）
     */
    protected function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY);
    }
}
