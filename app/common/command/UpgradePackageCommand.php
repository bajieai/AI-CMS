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
            ->addOption('force', 'f', Option::VALUE_NONE, '跳过本地开发痕迹内容检查（谨慎使用）')
            ->addOption('source', 's', Option::VALUE_OPTIONAL, '源文件目录', '')
            ->addOption('output', 'o', Option::VALUE_OPTIONAL, '输出目录', '')
            ->setDescription('生成 AI-CMS 在线升级包');
    }

    protected function execute(Input $input, Output $output)
    {
        $fromVersion = trim($input->getArgument('from_version'));
        $toVersion = trim($input->getArgument('to_version'));
        $isDiff = $input->hasOption('diff') && $input->getOption('diff');
        $force = $input->hasOption('force') && $input->getOption('force');
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

        // V2.9.64: 打包前拦截本地开发痕迹（隐藏目录路径引用等），防止泄漏进发布资产
        if (!$force) {
            $traces = $this->findLocalTraceHits($files);
            if (!empty($traces)) {
                $output->error('检测到本地开发痕迹，已中止打包（共 ' . count($traces) . ' 处）：');
                foreach ($traces as $trace) {
                    $output->error('  - ' . $trace);
                }
                $output->warning('如确属正常内容，可加 --force 跳过此检查');
                return 1;
            }
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
                if ($file->isFile() && str_ends_with($file->getFilename(), '.sql')
                    && $file->getFilename() !== 'install.sql') {
                    $relative = $this->getRelativePath($sourceDir, $file->getPathname());
                    if ($this->isLocalOnlyPath($relative)) {
                        continue;
                    }
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
            $relative = $this->getRelativePath($sourceDir, $file->getPathname());
            // 跳过 manifest.json 本身、隐藏文件与本地开发专用目录
            if ($fileName === 'manifest.json' || $this->isLocalOnlyPath($relative)) {
                continue;
            }
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

        // V2.9.55: 导出前清空临时目录，防止历史版本残留文件混入升级包
        // （缺陷案例：2.9.55 包误收 2.9.54 的 SQL 补丁——sourceDir 固定且从不清理，
        //   上次导出的 database/v2.9.54_add_member_mobile.sql 被本次 SQL 扫描捡到）
        if (is_dir($sourceDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
        }

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
            // V2.9.64: 隐藏文件与本地开发专用目录一律不进升级包
            if ($this->isLocalOnlyPath($relativePath)) {
                continue;
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
     * 本地开发专用目录（不进升级包，避免本地开发产物随发布资产外泄）
     *
     * @var string[]
     */
    protected const LOCAL_ONLY_DIRS = [
        'docs', 'bin', 'tools', 'tests', 'build', 'deploy',
        '产品文档', 'node_modules', 'runtime', '_v1_archive', 'h5-frontend/node_modules',
    ];

    /**
     * 本地开发专用文件（不进升级包）
     *
     * @var string[]
     */
    protected const LOCAL_ONLY_FILES = [
        'CHANGELOG.md', 'docker-php.bat', 'phpunit.xml', '.phpunit.result.cache',
    ];

    /**
     * 判断相对路径是否属于"仅本地、不发布"的内容
     *
     * 规则：
     *   1. 任一路径段以 . 开头（隐藏文件/目录，如 .gitignore、本地工作区、IDE 配置）
     *   2. 首层目录命中 LOCAL_ONLY_DIRS
     *   3. 文件名命中 LOCAL_ONLY_FILES
     */
    protected function isLocalOnlyPath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === '') {
            return true;
        }

        $segments = array_values(array_filter(explode('/', $relativePath), static fn ($s) => $s !== ''));
        foreach ($segments as $segment) {
            if (str_starts_with($segment, '.')) {
                return true;
            }
        }

        $first = $segments[0] ?? '';
        if (in_array($first, self::LOCAL_ONLY_DIRS, true)) {
            return true;
        }
        // 支持 "h5-frontend/node_modules/xxx" 这类二级目录规则
        if (count($segments) >= 2 && in_array($first . '/' . $segments[1], self::LOCAL_ONLY_DIRS, true)) {
            return true;
        }

        $baseName = $segments[count($segments) - 1];
        if (in_array($baseName, self::LOCAL_ONLY_FILES, true)) {
            return true;
        }

        return false;
    }

    /**
     * 扫描待打包文件中是否引用了本地隐藏目录（如 IDE/工具工作区路径）
     *
     * 说明：仅按"以点开头的目录路径"这一通用特征识别，不内置任何具体工具名，
     *       目的是让本地开发痕迹无法随升级包发布出去。
     *
     * @param  array<string,string> $files 相对路径 => 绝对路径
     * @return string[] 命中描述列表，空数组表示干净
     */
    protected function findLocalTraceHits(array $files): array
    {
        $extensions = [
            'md', 'txt', 'php', 'sql', 'json', 'html', 'htm', 'js', 'css',
            'htaccess', 'yml', 'yaml', 'bat', 'sh', 'ini', 'env', 'xml', 'conf',
        ];
        $pattern = '/(?<![A-Za-z0-9_-])\.[A-Za-z0-9_-]{2,}\//';
        $hits = [];

        foreach ($files as $relativePath => $absolutePath) {
            $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            if (!in_array($ext, $extensions, true)) {
                continue;
            }
            if (!is_file($absolutePath) || filesize($absolutePath) > 2 * 1024 * 1024) {
                continue;
            }
            $content = (string) file_get_contents($absolutePath);
            if ($content === '' || preg_match_all($pattern, $content, $matches) === 0) {
                continue;
            }
            $samples = array_values(array_unique($matches[0]));
            $hits[] = $relativePath . ' => ' . implode(', ', array_slice($samples, 0, 5));
        }

        return $hits;
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
