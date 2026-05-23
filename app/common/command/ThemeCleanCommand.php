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
use think\facade\Db;

/**
 * V2.9.11: 清理不可用AI主题
 *
 * 用法:
 *   php think theme:clean              # 默认dry-run模式，仅预览
 *   php think theme:clean --force      # 实际执行删除
 *   php think theme:clean --all        # 清理所有ai-theme-*（含可用主题）
 */
class ThemeCleanCommand extends Command
{
    protected function configure()
    {
        $this->setName('theme:clean')
            ->setDescription('清理不可用/废弃的AI生成主题')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制删除（默认dry-run）')
            ->addOption('all', 'a', Option::VALUE_NONE, '清理所有ai-theme-*主题（含可用主题）');
    }

    protected function execute(Input $input, Output $output)
    {
        $force = (bool) $input->getOption('force');
        $all = (bool) $input->getOption('all');
        $mode = $force ? '<error>FORCE</error>' : '<comment>DRY-RUN</comment>';

        $themesDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
        $skinsDir  = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin' . DIRECTORY_SEPARATOR . 'themes';

        if (!is_dir($themesDir)) {
            $output->writeln('<error>主题目录不存在: ' . $themesDir . '</error>');
            return 1;
        }

        $output->writeln("<info>=== AI-CMS Theme Clean (V2.9.11) ===</info> 模式: {$mode}");
        $output->writeln('');

        $targets = [];
        foreach (glob($themesDir . DIRECTORY_SEPARATOR . 'ai-theme-*', GLOB_ONLYDIR) as $dir) {
            $themeName = basename($dir);
            $reason = $this->checkDeletable($dir, $all);
            if ($reason !== null) {
                $targets[] = [
                    'theme_name' => $themeName,
                    'theme_dir'  => $dir,
                    'skin_dir'   => $skinsDir . DIRECTORY_SEPARATOR . $themeName,
                    'reason'     => $reason,
                ];
            }
        }

        if (empty($targets)) {
            $output->writeln('<info>未发现需要清理的AI主题</info>');
            return 0;
        }

        $output->writeln('<comment>待清理主题列表（共 ' . count($targets) . ' 个）:</comment>');
        foreach ($targets as $t) {
            $output->writeln("  - <error>{$t['theme_name']}</error>  原因: {$t['reason']}");
        }
        $output->writeln('');

        if (!$force) {
            $output->writeln('<comment>当前为预览模式，未执行实际删除。加 --force 执行清理。</comment>');
            return 0;
        }

        // 执行删除
        $deletedThemes = [];
        $deletedSkins = [];
        $dbDeleted = 0;

        foreach ($targets as $t) {
            // 1. 删除模板目录
            if (is_dir($t['theme_dir'])) {
                $this->rmDir($t['theme_dir']);
                $deletedThemes[] = $t['theme_name'];
            }

            // 2. 删除皮肤目录
            if (is_dir($t['skin_dir'])) {
                $this->rmDir($t['skin_dir']);
                $deletedSkins[] = $t['theme_name'];
            }

            // 3. 删除数据库记录
            try {
                $dbDeleted += Db::name('ai_theme_record')
                    ->where('theme_name', $t['theme_name'])
                    ->delete();
            } catch (\Throwable $e) {
                $output->writeln('<error>  DB删除失败: ' . $e->getMessage() . '</error>');
            }
        }

        $output->writeln('<info>清理完成:</info>');
        $output->writeln('  模板目录: ' . count($deletedThemes) . ' 个');
        $output->writeln('  皮肤目录: ' . count($deletedSkins) . ' 个');
        $output->writeln('  数据库记录: ' . $dbDeleted . ' 条');
        $output->writeln('');
        $output->writeln('<info>✅ 已清理主题: ' . implode(', ', $deletedThemes) . '</info>');

        return 0;
    }

    /**
     * 判断主题是否可删除。返回null表示保留，返回字符串表示删除原因。
     */
    protected function checkDeletable(string $themeDir, bool $allMode): ?string
    {
        $themeJsonPath = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';

        // --all 模式：所有 ai-theme-* 都删
        if ($allMode) {
            return '--all 模式强制清理';
        }

        // theme.json 不存在 → 不可用
        if (!is_file($themeJsonPath)) {
            return 'theme.json 缺失';
        }

        $data = json_decode(file_get_contents($themeJsonPath), true);
        if (!is_array($data)) {
            return 'theme.json 解析失败';
        }

        // pages 为空对象/数组 → 不可用（只有元数据骨架）
        $pages = $data['pages'] ?? [];
        if (empty($pages) || $pages === (object)[]) {
            return 'pages 为空（仅有元数据骨架）';
        }

        // 关键文件缺失 → 不可用
        $required = ['pc/layout.html', 'pc/index.html', 'assets/css/style.css'];
        foreach ($required as $file) {
            if (!is_file($themeDir . DIRECTORY_SEPARATOR . $file)) {
                return "关键文件缺失: {$file}";
            }
        }

        return null; // 保留
    }

    /**
     * 递归删除目录
     */
    protected function rmDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        rmdir($dir);
    }
}
