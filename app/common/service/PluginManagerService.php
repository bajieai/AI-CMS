<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PLUG-1: 插件管理服务
 * 安装/卸载/启用/禁用/列表
 */
class PluginManagerService
{
    protected string $pluginRoot = '';

    public function __construct()
    {
        $this->pluginRoot = root_path() . 'plugin' . DIRECTORY_SEPARATOR;
    }

    public function getList(): array
    {
        return Db::name('plugin')->order('id', 'desc')->select()->toArray();
    }

    public function getDetail(int $id): array
    {
        return Db::name('plugin')->where('id', $id)->find() ?? [];
    }

    public function install(string $identifier): array
    {
        $pluginPath = $this->pluginRoot . $identifier . DIRECTORY_SEPARATOR;
        $pluginFile = $pluginPath . 'plugin.php';

        if (!file_exists($pluginFile)) {
            return ['success' => false, 'message' => 'plugin.php 文件不存在'];
        }

        $config = include $pluginFile;
        if (!is_array($config) || empty($config['identifier'])) {
            return ['success' => false, 'message' => 'plugin.php 格式错误'];
        }

        $existing = Db::name('plugin')->where('identifier', $config['identifier'])->find();
        if ($existing) {
            return ['success' => false, 'message' => '插件已安装'];
        }

        // 执行安装SQL
        $installSql = $pluginPath . 'install.sql';
        if (file_exists($installSql)) {
            $sql = file_get_contents($installSql);
            if ($sql) {
                try { Db::execute($sql); } catch (\Throwable) {}
            }
        }

        $pluginId = Db::name('plugin')->insertGetId([
            'identifier'   => $config['identifier'],
            'name'         => $config['name'] ?? $config['identifier'],
            'description'  => $config['description'] ?? '',
            'version'      => $config['version'] ?? '1.0.0',
            'author'       => $config['author'] ?? '',
            'homepage'     => $config['homepage'] ?? '',
            'hooks'        => json_encode($config['hooks'] ?? [], JSON_UNESCAPED_UNICODE),
            'config'       => json_encode($config['config'] ?? [], JSON_UNESCAPED_UNICODE),
            'permissions'  => json_encode($config['permissions'] ?? [], JSON_UNESCAPED_UNICODE),
            'menu'         => json_encode($config['menu'] ?? [], JSON_UNESCAPED_UNICODE),
            'status'       => 1,
            'install_path' => 'plugin/' . $identifier,
            'installed_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        if (!empty($config['hooks'])) {
            $this->registerHooks($pluginId, $config['hooks']);
        }

        return ['success' => true, 'message' => '插件安装成功', 'plugin_id' => $pluginId];
    }

    public function uninstall(int $pluginId): array
    {
        $plugin = Db::name('plugin')->where('id', $pluginId)->find();
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }

        $pluginPath = $this->pluginRoot . $plugin['identifier'] . DIRECTORY_SEPARATOR;
        $uninstallSql = $pluginPath . 'uninstall.sql';
        if (file_exists($uninstallSql)) {
            $sql = file_get_contents($uninstallSql);
            if ($sql) {
                try { Db::execute($sql); } catch (\Throwable) {}
            }
        }

        Db::name('plugin_hook')->where('plugin_id', $pluginId)->delete();
        Db::name('plugin')->where('id', $pluginId)->delete();

        return ['success' => true, 'message' => '插件已卸载'];
    }

    public function toggleStatus(int $pluginId, int $status): array
    {
        $plugin = Db::name('plugin')->where('id', $pluginId)->find();
        if (!$plugin) {
            return ['success' => false, 'message' => '插件不存在'];
        }

        Db::name('plugin')->where('id', $pluginId)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Db::name('plugin_hook')->where('plugin_id', $pluginId)->update(['enabled' => $status === 1 ? 1 : 0]);

        return ['success' => true, 'message' => $status === 1 ? '插件已启用' : '插件已禁用'];
    }

    protected function registerHooks(int $pluginId, array $hooks): void
    {
        foreach ($hooks as $hook) {
            Db::name('plugin_hook')->insert([
                'plugin_id'  => $pluginId,
                'hook_name'  => $hook['name'] ?? '',
                'hook_type'  => $hook['type'] ?? 'action',
                'callback'   => $hook['callback'] ?? '',
                'priority'   => $hook['priority'] ?? 100,
                'enabled'    => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function scanAvailable(): array
    {
        $plugins = [];
        if (!is_dir($this->pluginRoot)) {
            return $plugins;
        }

        $dirs = glob($this->pluginRoot . '*', GLOB_ONLY_DIR);
        foreach ($dirs as $dir) {
            $identifier = basename($dir);
            $pluginFile = $dir . DIRECTORY_SEPARATOR . 'plugin.php';
            if (file_exists($pluginFile)) {
                $config = include $pluginFile;
                if (is_array($config) && !empty($config['identifier'])) {
                    $plugins[] = [
                        'identifier'  => $config['identifier'],
                        'name'        => $config['name'] ?? $config['identifier'],
                        'description' => $config['description'] ?? '',
                        'version'     => $config['version'] ?? '1.0.0',
                        'author'      => $config['author'] ?? '',
                    ];
                }
            }
        }

        return $plugins;
    }
}
