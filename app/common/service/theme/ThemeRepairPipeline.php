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

namespace app\common\service\theme;

use app\common\model\TemplateStore;
use think\facade\Log;

/**
 * 模板修复管线 - V2.9.12新增
 *
 * 功能：质量校验 + 自动修复 + 复检循环（max 3次）
 */
class ThemeRepairPipeline
{
    /**
     * 质量校验入口
     */
    public function validate(TemplateStore $store): array
    {
        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $store->slug;

        // 如果主题目录不存在，基于store记录进行元数据校验
        if (!is_dir($themePath)) {
            return [
                'pass' => false,
                'quality_score' => 0,
                'message' => '主题目录不存在，无法校验',
                'report' => [],
            ];
        }

        $qualityService = new ThemeQualityService();
        $report = $qualityService->getQualityReport($themePath, $store->category->slug ?? '');

        $pass = $report['quality_score'] >= 60;

        // 更新数据库质量评分
        $store->quality_score = $report['quality_score'];
        $store->save();

        return [
            'pass' => $pass,
            'quality_score' => $report['quality_score'],
            'message' => $pass ? '质量校验通过' : '质量校验未通过，建议修复',
            'report' => $report,
        ];
    }

    /**
     * 修复管线入口
     * 检测→修复→复检循环（max 3次），修复率目标≥80%
     */
    public function repair(TemplateStore $store): array
    {
        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $store->slug;
        if (!is_dir($themePath)) {
            return ['success' => false, 'message' => '主题目录不存在', 'logs' => []];
        }

        $logs = [];
        $maxCycles = 3;
        $initialScore = $store->quality_score;

        for ($cycle = 1; $cycle <= $maxCycles; $cycle++) {
            $cycleLogs = [];
            $cycleLogs[] = $this->log("===== 修复循环 {$cycle}/{$maxCycles} =====", $store->slug);

            // 执行修复
            $cssResult = $this->repairCss($themePath);
            $htmlResult = $this->repairHtml($themePath);

            $cycleLogs = array_merge($cycleLogs, $cssResult['logs'], $htmlResult['logs']);

            // 复检
            $qualityService = new ThemeQualityService();
            $report = $qualityService->getQualityReport($themePath, $store->category->slug ?? '');
            $newScore = $report['quality_score'];

            $store->quality_score = $newScore;
            $store->save();

            $cycleLogs[] = $this->log("复检评分: {$newScore}/100", $store->slug);

            $logs = array_merge($logs, $cycleLogs);

            // 如果评分达标或没有变化，提前结束
            if ($newScore >= 80 || $newScore === $initialScore) {
                break;
            }
        }

        $finalScore = $store->quality_score;
        $repairRate = $initialScore > 0 ? round((($finalScore - $initialScore) / (100 - $initialScore)) * 100, 1) : ($finalScore >= 80 ? 100 : 0);

        return [
            'success' => $finalScore >= 80,
            'initial_score' => $initialScore,
            'final_score' => $finalScore,
            'repair_rate' => $repairRate,
            'message' => "修复完成，初始{$initialScore}分 → 最终{$finalScore}分，修复率{$repairRate}%",
            'logs' => $logs,
        ];
    }

    /**
     * CSS修复器
     */
    protected function repairCss(string $themePath): array
    {
        $logs = [];
        $cssFiles = $this->collectFiles($themePath, ['css']);

        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $original = $content;
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            $changed = false;

            // 1. 补全缺失选择器（检测裸属性声明）
            // 如果文件中存在不在任何规则块内的属性声明，包装到 * 选择器中
            // 这是一个简化的处理，实际CSS解析较复杂

            // 2. 修复属性值：统一引号、补充分号
            $content = preg_replace('/([a-z-]+)\s*:\s*([^;{}]+)(?![;}])/', '$1: $2;', $content);
            if ($content !== $original) {
                $changed = true;
            }

            // 3. 关闭未闭合规则：如果 { 和 } 不匹配，尝试补全
            $open = substr_count($content, '{');
            $close = substr_count($content, '}');
            if ($open > $close) {
                $content .= str_repeat("\n}", $open - $close);
                $changed = true;
                $logs[] = $this->log("[{$relPath}] 补全 " . ($open - $close) . " 个未闭合CSS规则", $relPath);
            }

            if ($changed) {
                file_put_contents($file, $content, LOCK_EX);
                $logs[] = $this->log("[{$relPath}] CSS修复已写入", $relPath);
            }
        }

        return ['logs' => $logs];
    }

    /**
     * HTML修复器
     */
    protected function repairHtml(string $themePath): array
    {
        $logs = [];
        $htmlFiles = $this->collectFiles($themePath, ['html']);

        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            $original = $content;
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            $changed = false;

            // 1. 修复未闭合标签（常见块级元素）
            $tags = ['div', 'section', 'article', 'header', 'footer', 'nav', 'main', 'aside'];
            foreach ($tags as $tag) {
                $openCount = substr_count(strtolower($content), "<{$tag}");
                $closeCount = substr_count(strtolower($content), "</{$tag}>");
                if ($openCount > $closeCount) {
                    // 在文件末尾补全闭合标签
                    $content .= str_repeat("\n</{$tag}>", $openCount - $closeCount);
                    $changed = true;
                    $logs[] = $this->log("[{$relPath}] 补全 <{$tag}> 闭合标签 " . ($openCount - $closeCount) . " 个", $relPath);
                }
            }

            // 2. 清理无效属性（如空style、空class）
            $content = preg_replace('/\s+(style|class)=""/', '', $content);
            if ($content !== $original) {
                $changed = true;
            }

            // 3. 规范结构：确保HTML有基本结构（如果缺少DOCTYPE）
            if (stripos($content, '<!DOCTYPE') === false && stripos($content, '<html') === false) {
                $content = "<!DOCTYPE html>\n<html lang=\"zh-CN\">\n<head><meta charset=\"UTF-8\"></head>\n<body>\n" . $content . "\n</body>\n</html>";
                $changed = true;
                $logs[] = $this->log("[{$relPath}] 补充HTML基础结构", $relPath);
            }

            if ($changed) {
                file_put_contents($file, $content, LOCK_EX);
                $logs[] = $this->log("[{$relPath}] HTML修复已写入", $relPath);
            }
        }

        return ['logs' => $logs];
    }

    /**
     * 收集文件
     */
    protected function collectFiles(string $themePath, array $extensions): array
    {
        $files = [];
        if (!is_dir($themePath)) {
            return $files;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    /**
     * 记录修复日志
     */
    protected function log(string $message, string $file = ''): array
    {
        return [
            'time' => date('Y-m-d H:i:s'),
            'file' => $file,
            'message' => $message,
        ];
    }
}
