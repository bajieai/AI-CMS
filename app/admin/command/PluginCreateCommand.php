<?php

declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

/**
 * V2.9.35 PLUG-5: 插件脚手架CLI命令
 * php think plugin:create {name}
 */
class PluginCreateCommand extends Command
{
    protected function configure()
    {
        $this->setName('plugin:create')
            ->addArgument('name', Argument::REQUIRED, '插件标识符')
            ->setDescription('创建插件骨架');
    }

    protected function execute(Input $input, Output $output)
    {
        $identifier = $input->getArgument('name');
        $pluginRoot = root_path() . 'plugin' . DIRECTORY_SEPARATOR;
        $pluginPath = $pluginRoot . $identifier . DIRECTORY_SEPARATOR;

        if (is_dir($pluginPath)) {
            $output->error("插件 {$identifier} 已存在");
            return;
        }

        @mkdir($pluginPath, 0755, true);

        $pluginPhp = "<?php\n\nreturn [\n    'identifier'  => '{$identifier}',\n    'name'        => '{$identifier}',\n    'description' => '插件描述',\n    'version'     => '1.0.0',\n    'author'      => '',\n    'homepage'    => '',\n    'hooks'       => [],\n    'config'      => [],\n    'permissions' => ['db_read'],\n    'menu'        => [],\n];\n";
        file_put_contents($pluginPath . 'plugin.php', $pluginPhp);

        $configPhp = "<?php\n\nreturn [\n    'enabled' => true,\n    'settings' => [],\n];\n";
        file_put_contents($pluginPath . 'config.php', $configPhp);

        $hooksPhp = "<?php\n\n// 钩子注册示例\nreturn [];\n";
        file_put_contents($pluginPath . 'hooks.php', $hooksPhp);

        file_put_contents($pluginPath . 'README.md', "# {$identifier} 插件\n\nV2.9.35 自动生成\n");

        $output->info("插件 {$identifier} 骨架已创建: {$pluginPath}");
    }
}
