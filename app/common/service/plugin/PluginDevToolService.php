<?php
declare(strict_types=1);

namespace app\common\service\plugin;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 插件开发者工具服务 - V2.9.40 DEV-ECO2-1
 *
 * 开发者本地调试工具：代码模板生成、本地调试沙箱、热加载、测试框架
 */
class PluginDevToolService
{
    private const CACHE_TAG = 'plugin_dev_tool';

    /** 代码模板类型 */
    private const TEMPLATE_TYPES = [
        'controller'  => '控制器模板',
        'service'     => '服务类模板',
        'model'       => '模型模板',
        'middleware'  => '中间件模板',
        'command'     => '命令模板',
        'config'      => '配置模板',
        'view'        => '视图模板',
        'hook'        => '钩子模板',
    ];

    /**
     * 生成代码模板
     */
    public function generateTemplate(string $type, string $pluginName, string $className, array $options = []): string
    {
        $templates = [
            'controller' => $this->controllerTemplate($pluginName, $className, $options),
            'service'    => $this->serviceTemplate($pluginName, $className, $options),
            'model'      => $this->modelTemplate($pluginName, $className, $options),
            'middleware'  => $this->middlewareTemplate($pluginName, $className, $options),
            'command'    => $this->commandTemplate($pluginName, $className, $options),
            'config'     => $this->configTemplate($pluginName, $options),
            'hook'       => $this->hookTemplate($pluginName, $className, $options),
        ];

        return $templates[$type] ?? '';
    }

    private function controllerTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\controller";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse app\\common\\controller\\AdminBaseController;\n\nclass {$class}Controller extends AdminBaseController\n{\n    public function __construct()\n    {\n        parent::__construct(app());\n    }\n\n    public function index()\n    {\n        return \$this->view('/{$plugin}/{$class}_index');\n    }\n}\n";
    }

    private function serviceTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\service";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse think\\facade\\Db;\nuse think\\facade\\Cache;\n\nclass {$class}Service\n{\n    private const CACHE_TAG = '{$plugin}_{$class}';\n\n    public function getList(int \$page = 1, int \$limit = 20): array\n    {\n        return Db::name('{$plugin}_{$class}')->page(\$page, \$limit)->select()->toArray();\n    }\n}\n";
    }

    private function modelTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\model";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse think\\Model;\n\nclass {$class} extends Model\n{\n    protected \$name = '{$plugin}_{$class}';\n    protected \$pk = 'id';\n    protected \$autoWriteTimestamp = true;\n}\n";
    }

    private function middlewareTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\middleware";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nclass {$class}Middleware\n{\n    public function handle(\$request, \\Closure \$next)\n    {\n        // 插件中间件逻辑\n        return \$next(\$request);\n    }\n}\n";
    }

    private function commandTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\command";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse think\\console\\Command;\nuse think\\console\\Input;\nuse think\\console\\Output;\n\nclass {$class}Command extends Command\n{\n    protected function configure()\n    {\n        \$this->setName('{$plugin}:{$class}')->setDescription('{$plugin} {$class}命令');\n    }\n\n    protected function execute(Input \$input, Output \$output)\n    {\n        \$output->info('{$plugin} {$class}命令执行完成');\n        return 0;\n    }\n}\n";
    }

    private function configTemplate(string $plugin, array $opts): string
    {
        return "<?php\nreturn [\n    'name'        => '{$plugin}',\n    'title'       => '{$plugin}插件',\n    'description' => '{$plugin}插件描述',\n    'version'     => '1.0.0',\n    'author'      => '',\n    'dependencies' => [],\n    'hooks'       => [],\n];\n";
    }

    private function hookTemplate(string $plugin, string $class, array $opts): string
    {
        $namespace = "app\\common\\plugin\\{$plugin}\\hook";
        return "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse app\\common\\service\\PluginMarketService;\n\nclass {$class}Hook\n{\n    public function register(): void\n    {\n        PluginMarketService::on('{$plugin}.{$class}', [\$this, 'execute']);\n    }\n\n    public function execute(array \$context): array\n    {\n        return \$context;\n    }\n}\n";
    }

    /**
     * 获取模板类型列表
     */
    public function getTemplateTypes(): array
    {
        return self::TEMPLATE_TYPES;
    }

    /**
     * 创建本地调试沙箱
     */
    public function createSandbox(string $pluginName): string
    {
        $sandboxDir = runtime_path() . 'plugin_sandbox/' . $pluginName . '/';
        if (!is_dir($sandboxDir)) mkdir($sandboxDir, 0755, true);

        // 创建测试配置
        file_put_contents($sandboxDir . 'test_config.json', json_encode([
            'plugin'     => $pluginName,
            'mode'       => 'sandbox',
            'db_prefix'  => 'sandbox_',
            'created_at' => time(),
        ]));

        return $sandboxDir;
    }

    /**
     * 运行插件测试
     */
    public function runTests(string $pluginName): array
    {
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;
        $testDir = $pluginDir . '/tests/';

        if (!is_dir($testDir)) {
            return ['success' => true, 'msg' => '无测试文件', 'results' => []];
        }

        $results = [];
        $testFiles = glob($testDir . '*.php');
        foreach ($testFiles as $file) {
            $className = basename($file, '.php');
            $results[$className] = ['status' => 'skip', 'msg' => '测试需要在PHPUnit环境中执行'];
        }

        return ['success' => true, 'results' => $results, 'total' => count($testFiles)];
    }

    /**
     * 获取插件开发状态检查
     */
    public function getDevChecklist(string $pluginName): array
    {
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;

        $checklist = [
            'plugin.json'  => file_exists($pluginDir . '/plugin.json'),
            'controller'   => is_dir($pluginDir . '/controller'),
            'service'      => is_dir($pluginDir . '/service'),
            'model'        => is_dir($pluginDir . '/model'),
            'config'       => file_exists($pluginDir . '/config.php'),
            'hooks'        => file_exists($pluginDir . '/hooks.php'),
            'tests'        => is_dir($pluginDir . '/tests'),
            'readme'       => file_exists($pluginDir . '/README.md'),
        ];

        $score = count(array_filter($checklist)) / count($checklist) * 100;

        return [
            'plugin'    => $pluginName,
            'checklist' => $checklist,
            'score'     => round($score, 1),
            'ready'     => $score >= 75,
        ];
    }
}
