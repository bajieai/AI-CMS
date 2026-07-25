<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PLUG-2: 插件钩子服务
 * 桥接现有HookRegistry(V2.9.25)
 */
class PluginHookService
{
    /**
     * 执行Action钩子（无返回值）
     */
    public function doAction(string $hookName, array $data = []): void
    {
        $hooks = $this->getEnabledHooks($hookName, 'action');

        foreach ($hooks as $hook) {
            $startTime = microtime(true);

            try {
                $callback = $this->resolveCallback($hook['callback']);
                if ($callback) {
                    call_user_func($callback, $data);
                }
            } catch (\Throwable $e) {
                // 异常隔离：单插件异常不影响其他
                \think\facade\Log::error("Plugin hook {$hookName} error: " . $e->getMessage());
            }

            // 记录执行统计
            $execTime = (int)((microtime(true) - $startTime) * 1000000);
            Db::name('plugin_hook')->where('id', $hook['id'])->inc('exec_count')->inc('exec_time', $execTime)->update();
        }
    }

    /**
     * 执行Filter钩子（过滤数据）
     */
    public function applyFilters(string $hookName, mixed $data): mixed
    {
        $hooks = $this->getEnabledHooks($hookName, 'filter');

        foreach ($hooks as $hook) {
            $startTime = microtime(true);

            try {
                $callback = $this->resolveCallback($hook['callback']);
                if ($callback) {
                    $data = call_user_func($callback, $data);
                }
            } catch (\Throwable $e) {
                \think\facade\Log::error("Plugin filter {$hookName} error: " . $e->getMessage());
            }

            $execTime = (int)((microtime(true) - $startTime) * 1000000);
            Db::name('plugin_hook')->where('id', $hook['id'])->inc('exec_count')->inc('exec_time', $execTime)->update();
        }

        return $data;
    }

    /**
     * 获取已启用的钩子
     */
    protected function getEnabledHooks(string $hookName, string $type): array
    {
        return Db::name('plugin_hook')
            ->where('hook_name', $hookName)
            ->where('hook_type', $type)
            ->where('enabled', 1)
            ->order('priority', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 解析回调
     */
    protected function resolveCallback(string $callback): ?callable
    {
        if (str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);
            if (class_exists($class)) {
                return [new $class(), $method];
            }
        } elseif (function_exists($callback)) {
            return $callback;
        }

        return null;
    }

    /**
     * 获取钩子列表（分页）
     */
    public function getHooks(int $page = 1, int $pageSize = 20, int $pluginId = 0): array
    {
        $query = Db::name('plugin_hook')
            ->alias('h')
            ->join('plugin p', 'h.plugin_id = p.id', 'LEFT')
            ->field('h.*, p.name as plugin_name, p.identifier as plugin_identifier');

        if ($pluginId > 0) {
            $query->where('h.plugin_id', $pluginId);
        }

        $total = $query->count();
        $list = $query->order('h.hook_name, h.priority')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取插件列表
     */
    public function getPluginList(): array
    {
        return Db::name('plugin')
            ->where('status', 1)
            ->field('id, name, identifier')
            ->select()
            ->toArray();
    }

    /**
     * 获取钩子详情
     */
    public function getHookById(int $id): ?array
    {
        $hook = Db::name('plugin_hook')
            ->alias('h')
            ->join('plugin p', 'h.plugin_id = p.id', 'LEFT')
            ->field('h.*, p.name as plugin_name, p.identifier as plugin_identifier')
            ->where('h.id', $id)
            ->find();
        return $hook ?: null;
    }

    /**
     * 注册钩子
     */
    public function registerHook(array $data): array
    {
        try {
            $id = Db::name('plugin_hook')->insertGetId([
                'hook_name'  => $data['hook_name'],
                'plugin_id'  => $data['plugin_id'],
                'hook_type'  => $data['hook_type'] ?? 'action',
                'callback'   => $data['callback'],
                'priority'   => $data['priority'] ?? 10,
                'enabled'    => $data['enabled'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return ['code' => 0, 'msg' => '注册成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '注册失败: ' . $e->getMessage()];
        }
    }

    /**
     * 取消注册钩子
     */
    public function unregisterHook(int $id): array
    {
        try {
            Db::name('plugin_hook')->where('id', $id)->delete();
            return ['code' => 0, 'msg' => '取消注册成功'];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '取消注册失败: ' . $e->getMessage()];
        }
    }

    /**
     * 更新优先级
     */
    public function updatePriority(int $id, int $priority): array
    {
        try {
            Db::name('plugin_hook')->where('id', $id)->update(['priority' => $priority]);
            return ['code' => 0, 'msg' => '更新成功'];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取性能统计
     */
    public function getPerformanceStats(int $days = 7): array
    {
        return $this->getHookStats();
    }

    /**
     * 获取系统预置钩子
     */
    public function getSystemHooks(): array
    {
        return [
            ['name' => 'content.before_save', 'desc' => '内容保存前', 'type' => 'filter'],
            ['name' => 'content.after_save', 'desc' => '内容保存后', 'type' => 'action'],
            ['name' => 'content.before_delete', 'desc' => '内容删除前', 'type' => 'filter'],
            ['name' => 'content.after_delete', 'desc' => '内容删除后', 'type' => 'action'],
            ['name' => 'user.before_register', 'desc' => '用户注册前', 'type' => 'filter'],
            ['name' => 'user.after_register', 'desc' => '用户注册后', 'type' => 'action'],
            ['name' => 'user.before_login', 'desc' => '用户登录前', 'type' => 'filter'],
            ['name' => 'user.after_login', 'desc' => '用户登录后', 'type' => 'action'],
            ['name' => 'template.before_install', 'desc' => '模板安装前', 'type' => 'filter'],
            ['name' => 'template.after_install', 'desc' => '模板安装后', 'type' => 'action'],
            ['name' => 'system.before_config_save', 'desc' => '配置保存前', 'type' => 'filter'],
            ['name' => 'system.after_config_save', 'desc' => '配置保存后', 'type' => 'action'],
            ['name' => 'plugin.before_install', 'desc' => '插件安装前', 'type' => 'filter'],
            ['name' => 'plugin.after_install', 'desc' => '插件安装后', 'type' => 'action'],
            ['name' => 'plugin.before_enable', 'desc' => '插件启用前', 'type' => 'filter'],
            ['name' => 'plugin.after_enable', 'desc' => '插件启用后', 'type' => 'action'],
        ];
    }

    /**
     * 获取钩子列表
     */
    public function getHookList(): array
    {
        return Db::name('plugin_hook')
            ->alias('h')
            ->join('plugin p', 'h.plugin_id = p.id')
            ->field('h.*, p.name as plugin_name, p.identifier as plugin_identifier')
            ->order('h.hook_name, h.priority')
            ->select()
            ->toArray();
    }

    /**
     * 获取钩子性能统计
     */
    public function getHookStats(): array
    {
        return Db::name('plugin_hook')
            ->alias('h')
            ->join('plugin p', 'h.plugin_id = p.id')
            ->field('h.hook_name, p.name as plugin_name, h.exec_count, h.exec_time, h.priority')
            ->where('h.exec_count', '>', 0)
            ->order('h.exec_time', 'desc')
            ->limit(20)
            ->select()
            ->toArray();
    }
}
