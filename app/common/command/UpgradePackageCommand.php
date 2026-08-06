<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 在线升级包生成工具 - V2.9.44
 *
 * 用法：
 *   php think upgrade:package [from_version] [to_version] [--diff]
 *   php think upgrade:package 2.9.43 2.9.44
 *   php think upgrade:package 2.9.43 2.9.44 --diff
 *
 * 说明：
 *   - 非 --diff 模式：读取 runtime/upgrade/package_files/ 目录下的文件打包
 *   - --diff 模式：基于 git diff from_version..to_version 自动收集变更文件
 *   - 输出：runtime/upgrade/packages/ai-cms-upgrade-{to_version}.zip
 */
class UpgradePackageCommand extends Command
{
    protected function configure()
    {
        $this->setName('upgrade:package')
            ->addArgument('from_version', Argument::OPTIONAL, '起始版本号', '')
            ->addArgument('to_version', Argument::OPTIONAL, '目标版本号', '')
            ->addOption('diff', 'd', Option::VALUE_NONE, '基于 git diff 自动生成变更文件清单')
            ->addOption('source', 's', Option::VALUE_OPTIONAL, '源文件目录', '')
            ->addOption('output', 'o', Option::VALUE_OPTIONAL, '输出目录', '')
            ->setDescription('生成 AI-CMS 在线升级包');
    }

    protected function execute(Input $input, Output $output)
    {
        $fromVersion = trim($input->getArgument('from_version'));
        $toVersion = trim($input->getArgument('to_version'));
        $isDiff = $input->hasOption('diff') && $input->getOption('diff');
        $sourceDir = trim($input->getOption('source') ?: '');
        $outputDir = trim($input->getOption('output') ?: '');

        if (empty($fromVersion)) {
            $output->error('缺少参数 from_version，示例：php think upgrade:package 2.9.43 2.9.44');
            return 1;
        }
        if (empty($toVersion)) {
            $output->error('缺少参数 to_version，示例：php think upgrade:package 2.9.43 2.9.44');
            return 1;
        }

        $fromVersion = ltrim($fromVersion, 'vV');
        $toVersion = ltrim($toVersion, 'vV');

        $workPath = runtime_path() . 'upgrade' . DIRECTORY_SEPARATOR;
        if (empty($sourceDir)) {
            $sourceDir = $workPath . 'package_files' . DIRECTORY_SEPARATOR;
        }
        if (empty($outputDir)) {
            $outputDir = $workPath . 'packages' . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $output->info("开始生成升级包: v{$fromVersion} -> v{$toVersion}");

        // 收集文件清单
        if ($isDiff) {
            $files = $this->collectFilesByGitDiff($fromVersion, $toVersion, $sourceDir, $output);
        } else {
            $files = $this->collectFilesFromDir($sourceDir, $output);
        }

        if (empty($files)) {
            $output->warning('未收集到任何待升级文件');
            return 0;
        }

        // 生成 manifest
        $manifest = [
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'created_at' => date('Y-m-d H:i:s'),
            'files' => [],
            'deleted_files' => [],
            'sql_patches' => [],
            'run_after' => [],
            'run_after_suffix' => [],
        ];

        // 扫描 SQL 补丁
        $sqlDir = $sourceDir . 'database' . DIRECTORY_SEPARATOR;
        if (is_dir($sqlDir)) {
            $sqlFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sqlDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($sqlFiles as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), '.sql')) {
                    $relative = $this->getRelativePath($sourceDir, $file->getPathname());
                    $manifest['sql_patches'][] = $relative;
                }
            }
        }

        // 计算文件哈希
        foreach ($files as $relativePath => $absolutePath) {
            $manifest['files'][$relativePath] = hash_file('sha256', $absolutePath);
        }

        // 写入 manifest.json
        $manifestFile = $sourceDir . 'manifest.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $output->info('已生成 manifest.json');

        // 打包 ZIP
        $zipFile = $outputDir . 'ai-cms-upgrade-' . $toVersion . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $output->error('无法创建 ZIP 文件');
            return 1;
        }

        // 添加 manifest.json
        $zip->addFile($manifestFile, 'manifest.json');

        // 添加所有文件
        foreach ($files as $relativePath => $absolutePath) {
            $zip->addFile($absolutePath, $relativePath);
        }

        // 添加 SQL 补丁
        foreach ($manifest['sql_patches'] as $patch) {
            $absPath = $sourceDir . $patch;
            if (file_exists($absPath)) {
                $zip->addFile($absPath, $patch);
            }
        }

        $zip->close();

        // 清理临时 manifest（保留在 sourceDir 中方便查看）
        $output->info("升级包已生成: {$zipFile}");
        $output->info('文件数: ' . count($manifest['files']));
        $output->info('SQL补丁: ' . count($manifest['sql_patches']));

        return 0;
    }

    /**
     * 从目录收集文件
     */
    protected function collectFilesFromDir(string $sourceDir, Output $output): array
    {
        $files = [];
        if (!is_dir($sourceDir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $fileName = $file->getFilename();
            // 跳过 manifest.json 本身和隐藏文件
            if ($fileName === 'manifest.json' || str_starts_with($fileName, '.')) {
                continue;
            }
            $relative = $this->getRelativePath($sourceDir, $file->getPathname());
            $files[$relative] = $file->getPathname();
        }

        $output->info('从目录收集到 ' . count($files) . ' 个文件');
        return $files;
    }

    /**
     * 基于 git diff 收集变更文件
     */
    protected function collectFilesByGitDiff(string $fromVersion, string $toVersion, string $sourceDir, Output $output): array
    {
        $files = [];
        $rootPath = root_path();

        // 检查是否在 git 仓库中
        if (!is_dir($rootPath . '.git')) {
            $output->error('当前目录不是 git 仓库，无法使用 --diff 模式');
            return $files;
        }

        $tagFrom = 'v' . $fromVersion;
        $tagTo = 'v' . $toVersion;

        // 获取变更文件列表
        $cmd = 'cd ' . escapeshellarg($rootPath) . ' && git diff --name-only ' . escapeshellarg($tagFrom) . ' ' . escapeshellarg($tagTo) . ' 2>&1';
        exec($cmd, $diffOutput, $returnVar);
        if ($returnVar !== 0) {
            $output->warning('git diff 失败: ' . implode("\n", $diffOutput));
            return $files;
        }

        foreach ($diffOutput as $relativePath) {
            $relativePath = trim($relativePath);
            if (empty($relativePath)) {
                continue;
            }
            $sourcePath = $rootPath . $relativePath;
            if (!file_exists($sourcePath)) {
                continue; // 删除的文件不加入 files
            }
            $targetPath = $sourceDir . $relativePath;
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            copy($sourcePath, $targetPath);
            $files[$relativePath] = $targetPath;
        }

        $output->info('基于 git diff 收集到 ' . count($files) . ' 个文件');
        return $files;
    }

    /**
     * 计算相对路径
     */
    protected function getRelativePath(string $basePath, string $filePath): string
    {
        $basePath = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
        return ltrim(str_replace($basePath, '', $filePath), DIRECTORY_SEPARATOR);
    }
}
