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
use think\console\input\Option;
use think\console\Output;
use app\common\service\theme\ThemeSchemaService;

/**
 * V2.9.9 I-1: Schema迁移CLI
 * --dry-run   预览变更
 * --rollback  从.bak恢复
 */
class ThemeMigrateCommand extends Command
{
    protected function configure()
    {
        $this->setName('theme:migrate')
            ->setDescription('迁移/修复主题theme.json至市场标准格式')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '仅预览，不写入')
            ->addOption('rollback', 'r', Option::VALUE_OPTIONAL, '从.bak回滚指定主题', '');
    }

    protected function execute(Input $input, Output $output)
    {
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run');
        $rollback = $input->getOption('rollback');
        $themesDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';

        if ($rollback !== '') {
            return $this->doRollback($rollback, $themesDir, $output);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themesDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $changed = 0;
        $skipped = 0;
        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'theme.json') continue;
            $dir = basename($file->getPath());
            $path = $file->getPathname();

            $result = $this->migrateOne($dir, $path, $dryRun, $output);
            if ($result === 'changed') $changed++;
            else $skipped++;
        }

        if ($dryRun) {
            $output->writeln("<comment>[DRY-RUN] 预览完成: {$changed} 个需修改, {$skipped} 个无需修改</comment>");
        } else {
            $output->writeln("<info>迁移完成: {$changed} 个已修改, {$skipped} 个无需修改</info>");
        }
        return 0;
    }

    private function migrateOne(string $code, string $path, bool $dryRun, Output $output): string
    {
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            $output->writeln("<error>[{$code}] JSON解析失败</error>");
            return 'skipped';
        }

        $original = $data;
        $schema = ThemeSchemaService::validate($path);

        // 补全市场标准字段
        $defaults = [
            'name'        => $code,
            'version'     => '1.0.0',
            'description' => '',
            'author'      => '八界AI-CMS',
            'category'    => 'other',
            'tags'        => [],
            'preview'     => '',
            'type'        => 'frontend',
            'supports'    => ['pc'],
            'default_device' => 'pc',
            'colors'      => (object)[],
            'layouts'     => (object)[],
            'assets'      => (object)[],
            'options'     => (object)[],
            'pages'       => (object)[],
        ];

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $default;
            }
        }

        // 确保category在允许列表中
        $allowed = config('ai.theme_industry_categories', []);
        if (!isset($allowed[$data['category']])) {
            $data['category'] = 'other';
        }

        if ($data === $original) {
            return 'skipped';
        }

        if ($dryRun) {
            $output->writeln("<comment>[{$code}] 需修改: " . implode(', ', array_keys(array_diff_assoc($data, $original))) . "</comment>");
            return 'changed';
        }

        // 备份
        $bakPath = $path . '.bak.' . date('YmdHis');
        copy($path, $bakPath);

        // 写入
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        $output->writeln("<info>[{$code}] 已迁移 (备份: {$bakPath})</info>");
        return 'changed';
    }

    private function doRollback(string $code, string $themesDir, Output $output): int
    {
        if (empty($code)) {
            $output->writeln("<error>请指定主题标识: --rollback=theme-code</error>");
            return 1;
        }
        $dir = $themesDir . DIRECTORY_SEPARATOR . $code;
        $jsonPath = $dir . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($jsonPath)) {
            $output->writeln("<error>主题 {$code} 不存在</error>");
            return 1;
        }

        // 查找最新备份
        $bakFiles = glob($jsonPath . '.bak.*');
        if (empty($bakFiles)) {
            $output->writeln("<error>主题 {$code} 无备份文件</error>");
            return 1;
        }
        rsort($bakFiles);
        $latestBak = $bakFiles[0];

        copy($latestBak, $jsonPath);
        $output->writeln("<info>主题 {$code} 已从 {$latestBak} 回滚</info>");
        return 0;
    }
}
