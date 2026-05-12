<?php
declare(strict_types=1);

namespace app\common\service\theme;

use app\common\command\TemplateValidator;
use app\common\command\TemplateXssScanner;

/**
 * 主题校验流水线服务 - V3.0 Phase 2
 *
 * 封装 TemplateValidator + TemplateXssScanner 的校验逻辑，
 * 供 Service 层直接调用（无需 exec() 执行 CLI 脚本）。
 */
class ThemeValidatorService
{
    /**
     * 执行完整校验流水线
     *
     * @param string $themePath 主题目录路径或文件路径
     * @return array [
     *     'passed'    => bool,       // 是否全部通过
     *     'errors'    => array,      // 错误项
     *     'warnings'  => array,      // 警告项
     *     'infos'     => array,      // 建议项
     *     'summary'   => string,     // 汇总信息
     *     'xss_risks' => array,      // XSS风险项
     * ]
     */
    public function validate(string $themePath): array
    {
        // 1. 语法与标签配对校验
        $syntaxResult = $this->validateSyntax($themePath);

        // 2. XSS安全扫描
        $xssResult = $this->scanXss($themePath);

        // 3. CSS变量规范检查
        $cssVarResult = $this->checkCssVars($themePath);

        // 汇总结果
        $errors = array_merge(
            array_filter($syntaxResult, fn($r) => $r['level'] === 'error'),
            array_filter($cssVarResult, fn($r) => $r['level'] === 'error')
        );
        $warnings = array_merge(
            array_filter($syntaxResult, fn($r) => $r['level'] === 'warning'),
            array_filter($cssVarResult, fn($r) => $r['level'] === 'warning')
        );
        $infos = array_merge(
            array_filter($syntaxResult, fn($r) => $r['level'] === 'info'),
            array_filter($cssVarResult, fn($r) => $r['level'] === 'info')
        );

        $xssHigh = array_filter($xssResult, fn($r) => $r['level'] === 'high');
        $xssMedium = array_filter($xssResult, fn($r) => $r['level'] === 'medium');

        $hasSyntaxError = !empty($errors);
        $hasXssHigh = !empty($xssHigh);

        $summaryParts = [];
        if ($hasSyntaxError) {
            $summaryParts[] = '语法错误: ' . count($errors) . ' 项';
        }
        if ($hasXssHigh) {
            $summaryParts[] = 'XSS高危: ' . count($xssHigh) . ' 项';
        }
        if (!empty($xssMedium)) {
            $summaryParts[] = 'XSS中危: ' . count($xssMedium) . ' 项';
        }
        if (!empty($warnings)) {
            $summaryParts[] = '警告: ' . count($warnings) . ' 项';
        }

        return [
            'passed'    => !$hasSyntaxError && !$hasXssHigh,
            'errors'    => array_values($errors),
            'warnings'  => array_values($warnings),
            'infos'     => array_values($infos),
            'summary'   => empty($summaryParts) ? '全部通过' : implode('，', $summaryParts),
            'xss_risks' => array_values($xssResult),
            'has_xss_high' => $hasXssHigh,
            'has_syntax_error' => $hasSyntaxError,
        ];
    }

    /**
     * 语法与标签配对校验
     */
    public function validateSyntax(string $themePath): array
    {
        $validator = new TemplateValidator();
        return $validator->validate($themePath);
    }

    /**
     * XSS安全扫描
     */
    public function scanXss(string $themePath): array
    {
        $scanner = new TemplateXssScanner();
        return $scanner->scan($themePath);
    }

    /**
     * CSS变量规范检查
     *
     * 检查主题是否包含必要的CSS变量引用，以及变量名是否规范
     */
    public function checkCssVars(string $themePath): array
    {
        $results = [];

        if (is_file($themePath)) {
            $files = [$themePath];
        } elseif (is_dir($themePath)) {
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['html', 'css'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        } else {
            return [];
        }

        $requiredVars = [
            '--i8j-primary' => '主色调',
            '--i8j-bg'      => '背景色',
            '--i8j-text'    => '文字色',
            '--i8j-border'  => '边框色',
        ];

        $foundVars = [];
        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);

            foreach ($requiredVars as $var => $desc) {
                if (str_contains($content, $var)) {
                    $foundVars[$var] = true;
                }
            }

            // 检查不规范的 var() 用法（如缺少回退值不是硬性要求，仅info）
            if (str_contains($content, 'var(--i8j-')) {
                $matches = [];
                if (preg_match_all('/var\(--i8j-[a-z-]+\)/', $content, $matches)) {
                    // 发现CSS变量引用，记录但不报错
                }
            }
        }

        foreach ($requiredVars as $var => $desc) {
            if (!isset($foundVars[$var])) {
                $results[] = [
                    'rule_id' => 'CSS-VAR-001',
                    'file'    => 'theme.css/html',
                    'level'   => 'warning',
                    'message' => "缺少必要CSS变量引用: {$var} ({$desc})",
                ];
            }
        }

        return $results;
    }
}
