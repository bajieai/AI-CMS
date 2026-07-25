<?php
/**
 * V2.9.23 D-1: 插件管理CLI命令增强
 * 支持 list/install/uninstall/enable/disable/config 子命令
 * 非交互模式支持 -y/--force 参数
 */

declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\common\service\PluginService;
use app\common\model\Plugin as PluginModel;

class PluginCommand extends Command
{
    protected function configure()
    {
        $this->setName('plugin')
            ->setDescription('插件管理CLI工具: list/install/uninstall/enable/disable/config')
            ->addArgument('action', Argument::REQUIRED, '操作类型: list|install|uninstall|enable|disable|config|hooks')
            ->addArgument('code', Argument::OPTIONAL, '插件标识代码')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制操作，跳过确认')
            ->addOption('json', 'j', Option::VALUE_NONE, '以JSON格式输出')
            ->addOption('config-key', 'k', Option::VALUE_REQUIRED, '配置项键名(config动作专用)')
            ->addOption('config-value', 'v', Option::VALUE_REQUIRED, '配置项值(config动作专用)');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        $code   = $input->getArgument('code');
        $force  = $input->hasOption('force') && $input->getOption('force');
        $json   = $input->hasOption('json') && $input->getOption('json');

        try {
            match ($action) {
                'list'       => $this->doList($output, $json),
                'install'    => $this->doInstall($code, $force, $output),
                'uninstall'  => $this->doUninstall($code, $force, $output),
                'enable'     => $this->doEnable($code, $output),
                'disable'    => $this->doDisable($code, $output),
                'config'     => $this->doConfig($code, $input, $output),
                'hooks'      => $this->doHooks($output, $json),
                default      => throw new \InvalidArgumentException("未知操作: {$action}"),
            };
        } catch (\Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 列出所有插件
     */
    protected function doList(Output $output, bool $json): void
    {
        $plugins = PluginService::scanPlugins();

        if ($json) {
            $output->write(json_encode($plugins, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return;
        }

        if (empty($plugins)) {
            $output->info('暂无插件');
            return;
        }

        $headers = ['代码', '名称', '版本', '作者', '已安装', '已启用', 'Hook数'];
        $rows    = [];
        foreach ($plugins as $p) {
            $rows[] = [
                $p['code'] ?? '-',
                $p['name'] ?? '-',
                $p['version'] ?? '-',
                $p['author'] ?? '-',
                ($p['is_installed'] ?? 0) ? '✓' : '✗',
                ($p['is_enabled'] ?? 0) ? '✓' : '✗',
                count($p['hooks'] ?? []),
            ];
        }
        $output->table($headers, $rows);
        $output->info(sprintf('共 %d 个插件', count($plugins)));
    }

    /**
     * 安装插件
     */
    protected function doInstall(?string $code, bool $force, Output $output): void
    {
        if (!$code) {
            throw new \InvalidArgumentException('请提供插件代码: plugin:install <code>');
        }

        $pluginDir = root_path() . 'plugin/' . $code;
        if (!is_dir($pluginDir)) {
            throw new \RuntimeException("插件目录不存在: {$pluginDir}");
        }

        $jsonFile = $pluginDir . '/plugin.json';
        if (!file_exists($jsonFile)) {
            throw new \RuntimeException("插件信息文件缺失: {$jsonFile}");
        }

        $info = json_decode(file_get_contents($jsonFile), true);
        $output->info("插件: {$info['name']} v{$info['version']} by {$info['author']}");

        if (!$force) {
            $output->warning('即将安装插件，使用 --force 跳过确认');
        }

        PluginService::install($code);
        $output->info("✓ 插件 [{$code}] 安装成功");
    }

    /**
     * 卸载插件
     */
    protected function doUninstall(?string $code, bool $force, Output $output): void
    {
        if (!$code) {
            throw new \InvalidArgumentException('请提供插件代码: plugin:uninstall <code>');
        }

        $plugin = PluginModel::where('code', $code)->find();
        if (!$plugin) {
            throw new \RuntimeException("插件未安装: {$code}");
        }

        $output->warning("即将卸载插件 [{$code}] — {$plugin->name}");
        if (!$force) {
            $output->warning('使用 --force 跳过确认');
        }

        PluginService::uninstall($code);
        $output->info("✓ 插件 [{$code}] 已卸载");
    }

    /**
     * 启用插件
     */
    protected function doEnable(?string $code, Output $output): void
    {
        if (!$code) {
            throw new \InvalidArgumentException('请提供插件代码: plugin:enable <code>');
        }

        PluginService::enable($code);
        $output->info("✓ 插件 [{$code}] 已启用");
    }

    /**
     * 禁用插件
     */
    protected function doDisable(?string $code, Output $output): void
    {
        if (!$code) {
            throw new \InvalidArgumentException('请提供插件代码: plugin:disable <code>');
        }

        PluginService::disable($code);
        $output->info("✓ 插件 [{$code}] 已禁用");
    }

    /**
     * 查看/设置插件配置
     */
    protected function doConfig(?string $code, Input $input, Output $output): void
    {
        if (!$code) {
            throw new \InvalidArgumentException('请提供插件代码: plugin:config <code>');
        }

        $plugin = PluginModel::where('code', $code)->find();
        if (!$plugin) {
            throw new \RuntimeException("插件未安装: {$code}");
        }

        $key   = $input->getOption('config-key');
        $value = $input->getOption('config-value');

        $config = $plugin->config ?: [];

        if ($key === null) {
            // 查看全部配置
            $output->info("插件 [{$code}] 当前配置:");
            if (empty($config)) {
                $output->info('(空)');
            } else {
                $output->table(['键', '值'], array_map(fn($k, $v) => [$k, is_array($v) ? json_encode($v) : (string)$v], array_keys($config), array_values($config)));
            }
            return;
        }

        if ($value === null) {
            // 查看单个配置
            $output->info("{$key} = " . (isset($config[$key]) ? json_encode($config[$key]) : '(未设置)'));
            return;
        }

        // 设置配置
        $config[$key] = $this->castValue($value);
        $plugin->config = $config;
        $plugin->save();
        $output->info("✓ 配置已更新: {$key} = {$value}");
    }

    /**
     * 列出所有已注册的Hook
     */
    protected function doHooks(Output $output, bool $json): void
    {
        $ref = new \ReflectionClass(PluginService::class);
        $prop = $ref->getProperty('hooks');
        $hooks = $prop->getValue() ?: [];

        if ($json) {
            $output->write(json_encode($hooks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return;
        }

        if (empty($hooks)) {
            $output->info('暂无已注册的Hook');
            return;
        }

        $rows = [];
        foreach ($hooks as $event => $callbacks) {
            foreach ($callbacks as $cb) {
                $rows[] = [$event, $cb['__plugin'] ?? 'unknown'];
            }
        }
        $output->table(['Hook事件', '所属插件'], $rows);
        $output->info(sprintf('共 %d 个事件, %d 个回调', count($hooks), count($rows)));
    }

    /**
     * 智能类型转换配置值
     */
    protected function castValue(string $value): mixed
    {
        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
