<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

/**
 * 插件API类 - V2.5
 * 每个已启用插件在加载 bootstrap.php 时注入一个实例
 * 提供 Hook 注册和配置读取能力
 */
class PluginApi
{
    protected string $pluginCode;
    protected array $config;

    public function __construct(string $pluginCode, array $config = [])
    {
        $this->pluginCode = $pluginCode;
        $this->config = $config;
    }

    /**
     * 注册Hook
     * @param string $hook Hook名称
     * @param callable $callback 回调函数
     */
    public function register(string $hook, callable $callback): void
    {
        PluginService::registerHook($this->pluginCode, $hook, $callback);
    }

    /**
     * 获取插件配置项
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 获取全部配置
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }
}
