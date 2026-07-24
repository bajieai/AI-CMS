<?php
declare(strict_types=1);
namespace app\common\service;

use think\facade\Filesystem;

/**
 * 插件脚手架生成服务 (V2.9.29 D-3)
 */
class PluginScaffoldService
{
    private const SCAFFOLD_TEMPLATE = [
        'Plugin.php' => '<?php
namespace plugin\{name};
use think\Model;

class Plugin extends \app\common\service\PluginBase
{
    public function install(): bool { return true; }
    public function uninstall(): bool { return true; }
    public function enable(): bool { return true; }
    public function disable(): bool { return true; }
}',
        'config.json' => '{
    "name": "{name}",
    "title": "{title}",
    "description": "{description}",
    "version": "1.0.0",
    "author": "",
    "hooks": []
}',
        'README.md' => '# {title}

## 描述
{description}

## 安装
1. 将本目录放入 `plugin/` 下
2. 在后台插件管理中启用

## Hook事件
在config.json中注册需要监听的Hook事件。
',
    ];

    public function generate(string $name, string $title, string $description, string $outputDir): array
    {
        $pluginDir = $outputDir . '/' . $name;
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir, 0755, true);
        }

        $replacements = [
            '{name}' => $name,
            '{title}' => $title,
            '{description}' => $description,
        ];

        foreach (self::SCAFFOLD_TEMPLATE as $file => $content) {
            $finalContent = str_replace(array_keys($replacements), array_values($replacements), $content);
            file_put_contents($pluginDir . '/' . $file, $finalContent);
        }

        // 创建子目录
        foreach (['controllers', 'models', 'views', 'static'] as $subDir) {
            mkdir($pluginDir . '/' . $subDir, 0755, true);
        }

        return ['success' => true, 'path' => $pluginDir];
    }
}
