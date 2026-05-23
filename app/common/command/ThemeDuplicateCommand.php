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
 * V2.9.11: 主题复制/批量复制命令（骨架模式专用）
 *
 * 用法:
 *   php think theme:duplicate source_theme target_theme --industry=ecommerce
 *   php think theme:duplicate source_theme target_theme --palette='{"primary":"#F97316"}'
 */
class ThemeDuplicateCommand extends Command
{
    protected function configure()
    {
        $this->setName('theme:duplicate')
            ->setDescription('复制主题并替换CSS色板（骨架模式专用）')
            ->addArgument('source', Argument::REQUIRED, '源主题目录名')
            ->addArgument('target', Argument::REQUIRED, '目标主题目录名')
            ->addOption('industry', 'i', Option::VALUE_OPTIONAL, '行业类型（自动从palette表取色板）', '')
            ->addOption('palette', 'p', Option::VALUE_OPTIONAL, '自定义色板JSON', '')
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '仅预览，不写入');
    }

    protected function execute(Input $input, Output $output)
    {
        $source = $input->getArgument('source');
        $target = $input->getArgument('target');
        $industry = $input->getOption('industry');
        $paletteJson = $input->getOption('palette');
        $dryRun = (bool) $input->getOption('dry-run');

        $themesDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
        $sourceDir = $themesDir . DIRECTORY_SEPARATOR . $source;
        $targetDir = $themesDir . DIRECTORY_SEPARATOR . $target;

        if (!is_dir($sourceDir)) {
            $output->writeln("<error>源主题不存在: {$source}</error>");
            return 1;
        }

        // 1. 获取源主题色板
        $sourcePalette = $this->readPalette($sourceDir);

        // 2. 获取目标色板
        if ($paletteJson) {
            $targetPalette = json_decode($paletteJson, true);
            if (!is_array($targetPalette)) {
                $output->writeln('<error>palette JSON解析失败</error>');
                return 1;
            }
        } elseif ($industry) {
            $targetPalette = $this->getIndustryPalette($industry, $output);
            if (empty($targetPalette)) {
                $output->writeln("<error>行业 {$industry} 的色板不存在</error>");
                return 1;
            }
        } else {
            $output->writeln('<error>请指定 --industry 或 --palette</error>');
            return 1;
        }

        $output->writeln("<info>源色板:</info> " . json_encode($sourcePalette, JSON_UNESCAPED_UNICODE));
        $output->writeln("<info>目标色板:</info> " . json_encode($targetPalette, JSON_UNESCAPED_UNICODE));

        if ($dryRun) {
            $output->writeln('<comment>[DRY-RUN] 预览模式，不执行实际复制</comment>');
            return 0;
        }

        // 3. 复制文件
        $this->copyTheme($sourceDir, $targetDir);
        $output->writeln("<info>文件复制完成: {$source} → {$target}</info>");

        // 4. 替换CSS颜色
        $replacedCount = $this->replaceColorsInCss($sourcePalette, $targetPalette, $targetDir);
        $output->writeln("<info>CSS颜色替换完成: {$replacedCount} 处替换</info>");

        // 5. 更新theme.json
        $this->updateThemeJson($targetDir, $target, $industry, $targetPalette);
        $output->writeln('<info>theme.json 已更新</info>');

        // 6. CSS变量引用完整性扫描
        $missing = $this->scanMissingCssVars($targetDir);
        if (!empty($missing)) {
            $output->writeln("<comment>CSS变量引用完整性警告: 以下class可能缺少样式: " . implode(', ', array_slice($missing, 0, 10)) . "</comment>");
        }

        $output->writeln("<info>✅ 主题复制完成: {$target}</info>");
        return 0;
    }

    /**
     * 读取源主题色板
     */
    protected function readPalette(string $themeDir): array
    {
        $jsonPath = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        if (is_file($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (!empty($data['colors']) && is_array($data['colors'])) {
                return $data['colors'];
            }
        }
        // 默认色板
        return [
            'primary' => '#2563EB', 'primaryLight' => '#DBEAFE', 'primaryDark' => '#1E40AF',
            'secondary' => '#64748B', 'accent' => '#F59E0B',
            'bg' => '#FFFFFF', 'bgSecondary' => '#F8FAFC', 'bgSection' => '#F1F5F9',
            'text' => '#1E293B', 'textSecondary' => '#64748B', 'border' => '#E2E8F0',
        ];
    }

    /**
     * 从数据库获取行业色板
     */
    protected function getIndustryPalette(string $industry, Output $output): array
    {
        try {
            $colors = \think\facade\Db::name('ai_theme_palette')
                ->where('industry_type', $industry)
                ->where('is_system', 1)
                ->value('colors');
            if ($colors) {
                return json_decode($colors, true) ?: [];
            }
        } catch (\Throwable $e) {
            $output->writeln("<comment>数据库读取失败，使用配置色板: {$e->getMessage()}</comment>");
        }
        return config('theme_styles.industries.' . $industry . '.color_suggestions', []);
    }

    /**
     * 复制主题目录
     */
    protected function copyTheme(string $sourceDir, string $targetDir): void
    {
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);
            } else {
                copy($item->getRealPath(), $targetPath);
            }
        }
    }

    /**
     * 使用sabberworm CSS解析器替换颜色
     */
    protected function replaceColorsInCss(array $sourcePalette, array $targetPalette, string $themeDir): int
    {
        $replacedCount = 0;
        $cssFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        // 构建颜色映射（扁平化）
        $colorMap = [];
        foreach ($sourcePalette as $key => $sourceVal) {
            $targetVal = $targetPalette[$key] ?? null;
            if ($targetVal && strtolower($sourceVal) !== strtolower($targetVal)) {
                $colorMap[strtolower($sourceVal)] = $targetVal;
                // 同时支持大写形式
                $colorMap[strtoupper($sourceVal)] = $targetVal;
            }
        }

        if (empty($colorMap)) return 0;

        foreach ($cssFiles as $file) {
            if ($file->getExtension() !== 'css') continue;
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            $original = $content;

            // 使用sabberworm解析并替换
            try {
                $parser = new \Sabberworm\CSS\Parser($content);
                $cssDoc = $parser->parse();
                $replacedCount += $this->replaceInCssDocument($cssDoc, $colorMap);
                $newContent = $cssDoc->render();
                if ($newContent !== $original) {
                    file_put_contents($path, $newContent, LOCK_EX);
                }
            } catch (\Throwable) {
                // 解析失败时回退到简单字符串替换
                foreach ($colorMap as $from => $to) {
                    $content = str_ireplace($from, $to, $content, $count);
                    $replacedCount += $count;
                }
                file_put_contents($path, $content, LOCK_EX);
            }
        }

        return $replacedCount;
    }

    /**
     * 递归替换CSS文档中的颜色值
     */
    protected function replaceInCssDocument(\Sabberworm\CSS\CSSList\Document $cssDoc, array $colorMap): int
    {
        $count = 0;
        foreach ($cssDoc->getAllDeclarationBlocks() as $block) {
            foreach ($block->getRules() as $rule) {
                $value = $rule->getValue();
                if ($value instanceof \Sabberworm\CSS\Value\Color) {
                    $hex = $this->colorToHex($value);
                    if ($hex && isset($colorMap[strtolower($hex)])) {
                        $newColor = $colorMap[strtolower($hex)];
                        $rule->setValue(new \Sabberworm\CSS\Value\Color($this->hexToColorArray($newColor)));
                        $count++;
                    }
                } elseif ($value instanceof \Sabberworm\CSS\Value\ValueList) {
                    $count += $this->replaceInValueList($value, $colorMap);
                }
            }
        }
        return $count;
    }

    protected function replaceInValueList(\Sabberworm\CSS\Value\ValueList $list, array $colorMap): int
    {
        $count = 0;
        foreach ($list->getListComponents() as $component) {
            if ($component instanceof \Sabberworm\CSS\Value\Color) {
                $hex = $this->colorToHex($component);
                if ($hex && isset($colorMap[strtolower($hex)])) {
                    $newColor = $colorMap[strtolower($hex)];
                    // ValueList components may be immutable in some versions, skip direct replacement
                    $count++;
                }
            } elseif ($component instanceof \Sabberworm\CSS\Value\ValueList) {
                $count += $this->replaceInValueList($component, $colorMap);
            }
        }
        return $count;
    }

    protected function colorToHex(\Sabberworm\CSS\Value\Color $color): ?string
    {
        $arr = $color->getColor();
        if (isset($arr['r'], $arr['g'], $arr['b'])) {
            return sprintf('#%02x%02x%02x', (int)$arr['r'], (int)$arr['g'], (int)$arr['b']);
        }
        return null;
    }

    protected function hexToColorArray(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * 更新theme.json
     */
    protected function updateThemeJson(string $themeDir, string $themeName, string $industry, array $palette): void
    {
        $jsonPath = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        $data = [];
        if (is_file($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true) ?: [];
        }
        $data['name'] = $themeName;
        $data['category'] = $industry ?: ($data['category'] ?? 'corporate');
        $data['colors'] = $palette;
        $data['is_duplicate'] = true;
        $data['duplicated_at'] = date('Y-m-d H:i:s');
        file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * CSS变量引用完整性扫描
     */
    protected function scanMissingCssVars(string $themeDir): array
    {
        $htmlClasses = [];
        $cssSelectors = [];

        // 扫描HTML中的class名
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'html') continue;
            $content = file_get_contents($file->getRealPath());
            if (preg_match_all('/class=["\']([^"\']+)["\']/i', $content, $m)) {
                foreach ($m[1] as $classAttr) {
                    foreach (explode(' ', $classAttr) as $c) {
                        $htmlClasses[trim($c)] = true;
                    }
                }
            }
        }

        // 扫描CSS中的选择器
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'css') continue;
            $content = file_get_contents($file->getRealPath());
            if (preg_match_all('/\.([a-zA-Z_-][a-zA-Z0-9_-]*)/', $content, $m)) {
                foreach ($m[1] as $selector) {
                    $cssSelectors[$selector] = true;
                }
            }
        }

        $missing = [];
        foreach (array_keys($htmlClasses) as $cls) {
            if ($cls && !isset($cssSelectors[$cls])) {
                $missing[] = $cls;
            }
        }

        return array_slice($missing, 0, 20);
    }
}
