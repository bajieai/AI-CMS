<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use ZipArchive;

/**
 * 模板打包CLI工具 — V2.9.33 DEV-1
 * php think template:pack --dir=<目录> --name=<名称> --version=<版本>
 */
class TemplatePackCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('template:pack')
             ->addOption('dir', '', Option::VALUE_REQUIRED, '模板目录路径')
             ->addOption('output', '', Option::VALUE_OPTIONAL, '输出路径', './')
             ->addOption('name', '', Option::VALUE_REQUIRED, '模板名称')
             ->addOption('version', '', Option::VALUE_OPTIONAL, '版本号', 'v1.0.0')
             ->setDescription('打包模板为.tpkg文件');
    }

    protected function execute(Input $input, Output $output): void
    {
        $dir = $input->getOption('dir');
        $name = $input->getOption('name');
        $version = $input->getOption('version');
        $outputPath = $input->getOption('output');

        if (!$dir || !$name) {
            $output->writeln('<error>缺少必要参数: --dir 和 --name</error>');
            $output->writeln('用法: php think template:pack --dir=./my-template --name=restaurant --version=v1.0.0');
            return;
        }

        if (!is_dir($dir)) {
            $output->writeln("<error>目录不存在: {$dir}</error>");
            return;
        }

        $output->writeln('<info>=== 模板打包工具 ===</info>');
        $output->writeln("模板名称: {$name}");
        $output->writeln("版本: {$version}");
        $output->writeln("源目录: {$dir}");

        // 1. 校验目录结构
        $output->writeln('<info>[1/4] 校验目录结构...</info>');
        $requiredFiles = ['index.html', 'css/style.css'];
        $missingFiles = [];
        foreach ($requiredFiles as $file) {
            if (!file_exists($dir . '/' . $file)) {
                $missingFiles[] = $file;
            }
        }
        if (!empty($missingFiles)) {
            $output->writeln('<error>缺少必要文件: ' . implode(', ', $missingFiles) . '</error>');
            return;
        }
        $output->writeln('<info>✓ 目录结构校验通过</info>');

        // 2. 生成template.json
        $output->writeln('<info>[2/4] 生成配置文件...</info>');
        $templateJson = [
            'name'        => $name,
            'version'     => $version,
            'description' => '',
            'author'      => '',
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $jsonPath = $dir . '/template.json';
        if (!file_exists($jsonPath)) {
            file_put_contents($jsonPath, json_encode($templateJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $output->writeln('<info>✓ 生成template.json</info>');
        } else {
            $output->writeln('<info>✓ template.json已存在</info>');
        }

        // 3. 打包为ZIP
        $output->writeln('<info>[3/4] 打包中...</info>');
        $packageName = "{$name}_{$version}.tpkg";
        $packagePath = rtrim($outputPath, '/') . '/' . $packageName;

        $zip = new ZipArchive();
        if ($zip->open($packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $output->writeln("<error>无法创建打包文件: {$packagePath}</error>");
            return;
        }

        $this->addDirToZip($zip, $dir, '');
        $zip->close();

        $fileSize = round(filesize($packagePath) / 1024, 2);
        $output->writeln("<info>✓ 打包完成: {$packagePath} ({$fileSize} KB)</info>");

        // 4. 输出校验报告
        $output->writeln('<info>[4/4] 校验报告</info>');
        $output->writeln('  目录结构: ✓ 通过');
        $output->writeln('  配置文件: ✓ 通过');
        $output->writeln("  打包文件: {$packageName} ({$fileSize} KB)");
        $output->writeln("<info>=== 打包成功 ===</info>");
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            $zipPath = $prefix === '' ? $file : $prefix . '/' . $file;

            if (is_dir($path)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirToZip($zip, $path, $zipPath);
            } else {
                $zip->addFile($path, $zipPath);
            }
        }
        closedir($handle);
    }
}
