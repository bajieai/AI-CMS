<?php
declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

/**
 * 插件打包命令 - V2.9.40 DEV-ECO2-1
 *
 * 将插件源码打包为可分发ZIP文件
 * 包含：代码扫描→配置校验→依赖检查→ZIP打包→签名生成
 */
class PluginBuildCommand extends Command
{
    protected function configure()
    {
        $this->setName('plugin:build')
            ->setDescription('打包插件为可分发ZIP文件')
            ->addArgument('plugin_name', Argument::REQUIRED, '插件名称');
    }

    protected function execute(Input $input, Output $output)
    {
        $pluginName = $input->getArgument('plugin_name');
        if (empty($pluginName)) {
            $output->error('请指定插件名称: plugin:build <plugin_name>');
            return 1;
        }

        $output->info('开始打包插件: ' . $pluginName);

        // Step1: 检查插件目录是否存在
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;
        if (!is_dir($pluginDir)) {
            $output->error('插件目录不存在: ' . $pluginDir);
            return 1;
        }

        // Step2: 校验plugin.json配置文件
        $configFile = $pluginDir . '/plugin.json';
        if (!file_exists($configFile)) {
            $output->error('缺少plugin.json配置文件');
            return 1;
        }
        $config = json_decode(file_get_contents($configFile), true);
        if (empty($config) || empty($config['name']) || empty($config['version'])) {
            $output->error('plugin.json缺少name或version字段');
            return 1;
        }
        $output->info('配置校验通过: name=' . $config['name'] . ' version=' . $config['version']);

        // Step3: 代码扫描（检查安全关键词）
        $securityIssues = $this->scanSecurity($pluginDir);
        if (!empty($securityIssues)) {
            $output->warning('发现潜在安全问题:');
            foreach ($securityIssues as $issue) {
                $output->comment('  - ' . $issue);
            }
            // 安全问题仅警告不阻断打包
        }

        // Step4: 依赖检查
        $dependencies = $config['dependencies'] ?? [];
        foreach ($dependencies as $dep => $version) {
            if (!Db::name('plugin_market')->where('name', $dep)->find()) {
                $output->warning('依赖插件未安装: ' . $dep . '@' . $version);
            }
        }

        // Step5: ZIP打包
        $outputDir = runtime_path() . 'plugin_build/';
        if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

        $zipFile = $outputDir . $pluginName . '-' . $config['version'] . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $output->error('无法创建ZIP文件');
            return 1;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileCount = 0;
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($pluginDir) + 1);
            $zip->addFile($filePath, $pluginName . '/' . $relativePath);
            $fileCount++;
        }
        $zip->close();

        $output->info('打包完成: ' . $zipFile . ' (' . $fileCount . '个文件)');

        // Step6: 生成签名文件
        $signature = hash_file('sha256', $zipFile);
        $sigFile = $zipFile . '.sig';
        file_put_contents($sigFile, json_encode([
            'plugin'    => $pluginName,
            'version'   => $config['version'],
            'sha256'    => $signature,
            'build_time' => time(),
            'file_count' => $fileCount,
        ]));

        $output->info('签名文件: ' . $sigFile);
        $output->info('SHA256: ' . $signature);

        Log::info('插件打包完成: ' . $pluginName . ' v' . $config['version']);
        return 0;
    }

    /**
     * 安全扫描
     */
    private function scanSecurity(string $dir): array
    {
        $issues = [];
        $dangerousPatterns = [
            'eval('            => '使用了eval()函数',
            'exec('            => '使用了exec()函数',
            'shell_exec('      => '使用了shell_exec()函数',
            'system('          => '使用了system()函数',
            'passthru('        => '使用了passthru()函数',
            'file_put_contents' => '使用了file_put_contents()写文件',
            'unlink('          => '使用了unlink()删除文件',
        ];

        $phpFiles = glob($dir . '/**/*.php', GLOB_BRACE);
        if (empty($phpFiles)) {
            $phpFiles = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir)
            );
            foreach ($iterator as $f) {
                if ($f->getExtension() === 'php') $phpFiles[] = $f->getRealPath();
            }
        }

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            foreach ($dangerousPatterns as $pattern => $desc) {
                if (stripos($content, $pattern) !== false) {
                    $issues[] = basename($file) . ': ' . $desc;
                }
            }
        }

        return $issues;
    }
}
