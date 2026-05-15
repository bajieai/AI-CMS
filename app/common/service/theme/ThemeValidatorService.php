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

        // 4. V2.9.8 B-3: CSS质量评分
        $qualityResult = $this->cssQualityScore($themePath);
        $qualityPassed = ($qualityResult['total_score'] ?? 0) >= 60;

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

        // 质量低于60分加入warnings（不阻断，进入人工审核队列）
        if (!$qualityPassed) {
            $warnings[] = [
                'rule_id' => 'CSS-QUALITY-001',
                'file'    => 'style.css',
                'level'   => 'warning',
                'message' => "CSS质量评分不足: {$qualityResult['total_score']}分（及格线60分），进入人工审核队列",
                'quality' => $qualityResult,
            ];
        }

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
        if ($qualityPassed) {
            $summaryParts[] = 'CSS质量: ' . $qualityResult['total_score'] . '分';
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
            'css_quality' => $qualityResult,
            'quality_passed' => $qualityPassed,
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
     * 单文件校验（用于局部重生成后校验）
     *
     * 仅对单个文件执行语法 + XSS 校验，不含 CSS 变量检查
     *
     * @param string $filePath 单个文件路径
     * @return array 同 validate() 返回结构
     */
    public function validateFile(string $filePath): array
    {
        if (!is_file($filePath)) {
            return [
                'passed'    => false,
                'errors'    => [['level' => 'error', 'message' => '文件不存在: ' . $filePath]],
                'warnings'  => [],
                'infos'     => [],
                'summary'   => '文件不存在',
                'xss_risks' => [],
                'has_xss_high' => false,
                'has_syntax_error' => true,
            ];
        }

        // 1. 语法校验（单文件模式）
        $syntaxResult = $this->validateSyntaxFile($filePath);

        // 2. XSS扫描（单文件模式）
        $xssResult = $this->scanXssFile($filePath);

        $errors = array_filter($syntaxResult, fn($r) => $r['level'] === 'error');
        $warnings = array_filter($syntaxResult, fn($r) => $r['level'] === 'warning');
        $infos = array_filter($syntaxResult, fn($r) => $r['level'] === 'info');

        $xssHigh = array_filter($xssResult, fn($r) => $r['level'] === 'high');
        $hasSyntaxError = !empty($errors);
        $hasXssHigh = !empty($xssHigh);

        $summaryParts = [];
        if ($hasSyntaxError) {
            $summaryParts[] = '语法错误: ' . count($errors) . ' 项';
        }
        if ($hasXssHigh) {
            $summaryParts[] = 'XSS高危: ' . count($xssHigh) . ' 项';
        }

        return [
            'passed'    => !$hasSyntaxError && !$hasXssHigh,
            'errors'    => array_values($errors),
            'warnings'  => array_values($warnings),
            'infos'     => array_values($infos),
            'summary'   => empty($summaryParts) ? '单文件校验通过' : implode('，', $summaryParts),
            'xss_risks' => array_values($xssResult),
            'has_xss_high' => $hasXssHigh,
            'has_syntax_error' => $hasSyntaxError,
        ];
    }

    /**
     * 单文件语法校验
     */
    public function validateSyntaxFile(string $filePath): array
    {
        $validator = new \app\common\command\TemplateValidator();
        return $validator->validateFile($filePath);
    }

    /**
     * 单文件XSS扫描
     */
    public function scanXssFile(string $filePath): array
    {
        $scanner = new \app\common\command\TemplateXssScanner();
        return $scanner->scanFile($filePath);
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
            '--primary' => '主色调',
            '--bg'      => '背景色',
            '--text'    => '文字色',
            '--border'  => '边框色',
        ];

        $foundVars = [];
        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);

            foreach ($requiredVars as $var => $desc) {
                if (str_contains($content, $var)) {
                    $foundVars[$var] = true;
                }
            }

            // 检查 var() 用法
            if (str_contains($content, 'var(--')) {
                $matches = [];
                if (preg_match_all('/var\(--[a-z-]+\)/', $content, $matches)) {
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

    /**
     * V2.9.8 B-3: CSS质量7维度评分
     *
     * @param string $themePath 主题目录或CSS文件路径
     * @return array ['total_score'=>float, 'dimensions'=>array, 'passed'=>bool, 'level'=>string]
     */
    public function cssQualityScore(string $themePath): array
    {
        $css = $this->extractCssContent($themePath);
        if (empty($css)) {
            return ['total_score' => 0, 'dimensions' => [], 'passed' => false, 'level' => 'low'];
        }

        $dimensions = [
            'css_variables' => ['weight' => 20, 'min' => 10, 'score' => $this->countCssVarUsage($css)],
            'transitions'   => ['weight' => 15, 'min' => 3,  'score' => $this->countTransitions($css)],
            'box_shadows'   => ['weight' => 10, 'min' => 1,  'score' => $this->countBoxShadows($css)],
            'media_queries' => ['weight' => 15, 'min' => 1,  'score' => $this->countMediaQueries($css)],
            'color_depth'   => ['weight' => 20, 'min' => 4,  'score' => $this->countUniqueColors($css)],
            'pseudo_states' => ['weight' => 10, 'min' => 3,  'score' => $this->countPseudoClasses($css)],
            'spacing'       => ['weight' => 10, 'min' => 5,  'score' => $this->countSpacingDeclarations($css)],
        ];

        $totalScore = 0;
        $resultDimensions = [];
        foreach ($dimensions as $dim => $config) {
            $dimScore = $config['min'] > 0
                ? min(100, ($config['score'] / $config['min']) * 100)
                : ($config['score'] > 0 ? 100 : 0);
            $resultDimensions[$dim] = [
                'detected' => $config['score'],
                'required' => $config['min'],
                'score'    => round($dimScore, 1),
                'weight'   => $config['weight'],
                'weighted' => round($dimScore * $config['weight'] / 100, 1),
            ];
            $totalScore += $dimScore * $config['weight'] / 100;
        }

        $totalScore = round($totalScore, 1);
        return [
            'total_score' => $totalScore,
            'dimensions'  => $resultDimensions,
            'passed'      => $totalScore >= 60,
            'level'       => $totalScore >= 80 ? 'excellent' : ($totalScore >= 60 ? 'good' : 'low'),
        ];
    }

    /**
     * 提取主题目录下所有CSS内容
     */
    protected function extractCssContent(string $themePath): string
    {
        $css = '';
        if (is_file($themePath)) {
            return file_get_contents($themePath);
        }
        if (!is_dir($themePath)) return '';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'css') {
                $css .= file_get_contents($file->getPathname()) . "\n";
            }
            if ($file->isFile() && $file->getExtension() === 'html') {
                $content = file_get_contents($file->getPathname());
                // 提取<style>标签内容
                if (preg_match_all('/<style[^>]*>(.*?)<\/style>/si', $content, $m)) {
                    foreach ($m[1] as $style) {
                        $css .= $style . "\n";
                    }
                }
            }
        }
        return $css;
    }

    protected function countCssVarUsage(string $css): int
    {
        preg_match_all('/var\(--[a-zA-Z0-9_-]+\)/', $css, $m);
        return count($m[0]);
    }

    protected function countTransitions(string $css): int
    {
        preg_match_all('/(?:transition|animation)\s*:/i', $css, $m);
        return count($m[0]);
    }

    protected function countBoxShadows(string $css): int
    {
        preg_match_all('/(?:box-shadow|text-shadow)\s*:/i', $css, $m);
        return count($m[0]);
    }

    protected function countMediaQueries(string $css): int
    {
        preg_match_all('/@media\s/', $css, $m);
        return count($m[0]);
    }

    protected function countUniqueColors(string $css): int
    {
        preg_match_all('/#(?:[0-9a-fA-F]{3,8})\b|rgba?\s*\([^)]+\)/i', $css, $m);
        return count(array_unique(array_map('strtolower', $m[0])));
    }

    protected function countPseudoClasses(string $css): int
    {
        preg_match_all('/:(?:hover|active|focus|visited|focus-within|focus-visible)/i', $css, $m);
        return count($m[0]);
    }

    protected function countSpacingDeclarations(string $css): int
    {
        preg_match_all('/(?:padding|margin)\s*:\s*\d+/', $css, $m);
        return count($m[0]);
    }
}
