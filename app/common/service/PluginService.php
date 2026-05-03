<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Plugin as PluginModel;
use think\facade\Cache;

/**
 * 插件服务 - V2.5完整实现
 * 提供Hook机制 + 插件扫描/安装/卸载/启用/禁用管理
 */
class PluginService
{
    /** @var array<string, array> 已注册的Hook回调 */
    protected static array $hooks = [];

    /** @var bool 是否已完成bootstrap */
    protected static bool $bootstrapped = false;

    protected static string $cacheTag = 'i8j_plugin';

    /**
     * 扫描 plugin/ 目录下的所有插件
     * @return array 插件列表（含 is_installed / is_enabled 状态）
     */
    public static function scanPlugins(): array
    {
        $pluginDir = root_path() . 'plugin';
        $plugins = [];

        if (!is_dir($pluginDir)) {
            return $plugins;
        }

        $dirs = glob($pluginDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $code = basename($dir);
            $jsonFile = $dir . '/plugin.json';
            if (!file_exists($jsonFile)) {
                continue;
            }

            $info = json_decode(file_get_contents($jsonFile), true);
            if (!$info || empty($info['code'])) {
                continue;
            }

            $record = PluginModel::where('code', $code)->find();
            $plugins[] = array_merge($info, [
                'is_installed' => $record ? 1 : 0,
                'is_enabled'   => $record ? (int) $record->is_enabled : 0,
                'config'       => $record ? $record->config : [],
            ]);
        }

        return $plugins;
    }

    /**
     * 安装插件
     * @throws \Exception
     */
    public static function install(string $code): void
    {
        $pluginDir = root_path() . 'plugin/' . $code;
        $jsonFile  = $pluginDir . '/plugin.json';

        if (!file_exists($jsonFile)) {
            throw new \Exception('插件不存在：' . $code);
        }

        $info = json_decode(file_get_contents($jsonFile), true);
        if (!$info || empty($info['code'])) {
            throw new \Exception('插件信息文件格式错误');
        }

        $exists = PluginModel::where('code', $code)->find();
        if ($exists) {
            throw new \Exception('插件已安装');
        }

        $model = new PluginModel();
        $model->code        = $code;
        $model->name        = $info['name']        ?? $code;
        $model->version     = $info['version']     ?? '1.0.0';
        $model->author      = $info['author']      ?? '';
        $model->description = $info['description'] ?? '';
        $model->hooks       = $info['hooks']       ?? [];
        $model->config      = self::getDefaultConfig($info['config'] ?? []);
        $model->is_enabled  = 0;
        $model->save();

        Cache::tag(self::$cacheTag)->clear();
    }

    /**
     * 卸载插件
     * @throws \Exception
     */
    public static function uninstall(string $code): void
    {
        $plugin = PluginModel::where('code', $code)->find();
        if (!$plugin) {
            throw new \Exception('插件未安装');
        }

        $plugin->delete();
        self::removePluginHooks($code);
        Cache::tag(self::$cacheTag)->clear();
    }

    /**
     * 启用插件
     * @throws \Exception
     */
    public static function enable(string $code): void
    {
        $plugin = PluginModel::where('code', $code)->find();
        if (!$plugin) {
            throw new \Exception('插件未安装');
        }

        $plugin->is_enabled = 1;
        $plugin->save();

        self::bootstrapPlugin($code);
        Cache::tag(self::$cacheTag)->clear();
    }

    /**
     * 禁用插件
     * @throws \Exception
     */
    public static function disable(string $code): void
    {
        $plugin = PluginModel::where('code', $code)->find();
        if (!$plugin) {
            throw new \Exception('插件未安装');
        }

        $plugin->is_enabled = 0;
        $plugin->save();

        self::removePluginHooks($code);
        Cache::tag(self::$cacheTag)->clear();
    }

    /**
     * 触发Hook：执行所有注册到该事件的回调
     * @param string $event Hook事件名
     * @param mixed $data 传递的数据
     * @return array 所有回调的返回值数组
     */
    public static function fire(string $event, mixed $data = null): array
    {
        self::bootstrap();

        $results = [];
        if (!empty(self::$hooks[$event])) {
            foreach (self::$hooks[$event] as $item) {
                $callback = $item['callback'] ?? $item;
                $results[] = $callback($data);
            }
        }
        return $results;
    }

    /**
     * 注册Hook（由 PluginApi 调用）
     * @param string $pluginCode 插件标识
     * @param string $hook Hook名称
     * @param callable $callback 回调
     */
    public static function registerHook(string $pluginCode, string $hook, callable $callback): void
    {
        if (!isset(self::$hooks[$hook])) {
            self::$hooks[$hook] = [];
        }
        self::$hooks[$hook][] = [
            'callback' => $callback,
            '__plugin' => $pluginCode,
        ];
    }

    /**
     * 初始化所有已启用插件（惰性加载，仅执行一次）
     */
    public static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }
        self::$bootstrapped = true;

        $plugins = PluginModel::where('is_enabled', 1)->column('code');
        foreach ($plugins as $code) {
            self::bootstrapPlugin((string) $code);
        }
    }

    /**
     * 初始化单个插件：加载 bootstrap.php
     */
    protected static function bootstrapPlugin(string $code): void
    {
        $bootstrapFile = root_path() . 'plugin/' . $code . '/bootstrap.php';
        if (!file_exists($bootstrapFile)) {
            return;
        }

        $plugin = PluginModel::where('code', $code)->find();
        $config = $plugin ? $plugin->config : [];

        $pluginApi = new PluginApi($code, $config);
        include $bootstrapFile;
    }

    /**
     * 移除指定插件的所有Hook
     */
    protected static function removePluginHooks(string $code): void
    {
        foreach (self::$hooks as $event => $callbacks) {
            self::$hooks[$event] = array_values(array_filter($callbacks, function ($cb) use ($code) {
                return !isset($cb['__plugin']) || $cb['__plugin'] !== $code;
            }));
            if (empty(self::$hooks[$event])) {
                unset(self::$hooks[$event]);
            }
        }
    }

    /**
     * 从 plugin.json 的 config schema 提取默认值
     */
    protected static function getDefaultConfig(array $configSchema): array
    {
        $config = [];
        foreach ($configSchema as $key => $item) {
            $config[$key] = $item['default'] ?? '';
        }
        return $config;
    }
}
