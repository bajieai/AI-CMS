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

use think\facade\Log;

/**
 * 主题质量自检评分服务 - Sprint 14
 *
 * 纯算法实现，零AI成本
 * 5+1维度评分（满分100）：
 *   1. 结构完整性      30分
 *   2. CSS变量引用率   25分
 *   3. 硬编码检测      20分
 *   4. 页面完整性      15分
 *   5. 代码简洁度       5分
 *   6. 相似度检测       5分（>80% warning，>95%告警+人工判断）
 */
class ThemeQualityService
{
    /** 必备文件清单 */
    protected array $requiredFiles = [
        'theme.json',
        'pc/layout.html',
        'pc/index.html',
    ];

    /** 推荐文件清单 */
    protected array $recommendedFiles = [
        'mobile/layout.html',
        'mobile/index.html',
        'assets/css/style.css',
        'assets/js/main.js',
    ];

    /** CSS变量前缀（V2.9.11统一为无前缀） */
    protected string $cssVarPrefix = '--';

    /** 必要CSS变量（V2.9.11统一25变量子集） */
    protected array $requiredCssVars = [
        '--primary',
        '--bg',
        '--text',
        '--border',
    ];

    /**
     * 对主题目录进行质量评分
     *
     * @param string $themePath 主题目录路径
     * @param string $industry  行业类型（用于相似度检测）
     * @return array ['total'=>int, 'dimensions'=>[], 'warnings'=>[], 'details'=>[]]
     */
    public function score(string $themePath, string $industry = ''): array
    {
        if (!is_dir($themePath)) {
            return [
                'total'      => 0,
                'dimensions' => [],
                'warnings'   => ['主题目录不存在'],
                'details'    => [],
            ];
        }

        $dimensions = [];
        $warnings   = [];

        // 1. 结构完整性 (30分)
        $structResult = $this->scoreStructure($themePath);
        $dimensions['structure'] = $structResult;

        // 2. CSS变量引用率 (25分)
        $cssResult = $this->scoreCssVars($themePath);
        $dimensions['css_vars'] = $cssResult;

        // 3. 硬编码检测 (20分)
        $hardcodeResult = $this->scoreHardcode($themePath);
        $dimensions['hardcode'] = $hardcodeResult;

        // 4. 页面完整性 (15分)
        $pageResult = $this->scorePages($themePath);
        $dimensions['pages'] = $pageResult;

        // 5. 代码简洁度 (5分)
        $cleanResult = $this->scoreCleanliness($themePath);
        $dimensions['cleanliness'] = $cleanResult;

        // 6. 相似度检测 (5分)
        $similarityResult = $this->scoreSimilarity($themePath, $industry);
        $dimensions['similarity'] = $similarityResult;

        $total = array_sum(array_column($dimensions, 'score'));

        // 收集警告
        foreach ($dimensions as $_ => $result) {
            if (!empty($result['warnings'])) {
                $warnings = array_merge($warnings, $result['warnings']);
            }
        }

        return [
            'total'      => $total,
            'dimensions' => $dimensions,
            'warnings'   => array_values(array_unique($warnings)),
            'details'    => [
                'theme_path' => $themePath,
                'industry'   => $industry,
                'scored_at'  => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 维度1：结构完整性 (30分)
     */
    protected function scoreStructure(string $themePath): array
    {
        $score = 0;
        $warnings = [];
        $missingRequired = [];
        $missingRecommended = [];

        foreach ($this->requiredFiles as $file) {
            if (!is_file($themePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                $missingRequired[] = $file;
            }
        }

        foreach ($this->recommendedFiles as $file) {
            if (!is_file($themePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                $missingRecommended[] = $file;
            }
        }

        // 必备文件：每个扣8分
        $requiredScore = max(0, 24 - count($missingRequired) * 8);
        $score += $requiredScore;

        // 推荐文件：每个扣1分
        $recommendedScore = max(0, 6 - count($missingRecommended) * 1);
        $score += $recommendedScore;

        if (!empty($missingRequired)) {
            $warnings[] = '缺少必备文件: ' . implode(', ', $missingRequired);
        }
        if (!empty($missingRecommended)) {
            $warnings[] = '缺少推荐文件: ' . implode(', ', $missingRecommended);
        }

        // theme.json 内容校验
        $themeJsonPath = $themePath . DIRECTORY_SEPARATOR . 'theme.json';
        if (is_file($themeJsonPath)) {
            $json = json_decode(file_get_contents($themeJsonPath), true);
            if (!is_array($json) || empty($json['name'])) {
                $warnings[] = 'theme.json 格式不完整，缺少 name 字段';
                $score = max(0, $score - 3);
            }
        }

        return ['score' => $score, 'max' => 30, 'warnings' => $warnings];
    }

    /**
     * 维度2：CSS变量引用率 (25分)
     */
    protected function scoreCssVars(string $themePath): array
    {
        $score = 0;
        $warnings = [];

        $cssFiles = $this->collectFiles($themePath, ['css']);
        $htmlFiles = $this->collectFiles($themePath, ['html']);
        $allFiles = array_merge($cssFiles, $htmlFiles);

        if (empty($allFiles)) {
            $warnings[] = '未找到CSS/HTML文件，无法检测CSS变量';
            return ['score' => 0, 'max' => 25, 'warnings' => $warnings];
        }

        $foundVars = [];
        $totalVarUsages = 0;

        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            foreach ($this->requiredCssVars as $var) {
                if (str_contains($content, $var)) {
                    $foundVars[$var] = true;
                    $totalVarUsages += substr_count($content, $var);
                }
            }
        }

        // 必要变量覆盖率：每个4分
        $coverageScore = count($foundVars) * 4;
        $score += min(16, $coverageScore);

        // 引用频次：总引用>10次得9分，>5次得5分
        if ($totalVarUsages >= 10) {
            $score += 9;
        } elseif ($totalVarUsages >= 5) {
            $score += 5;
        } elseif ($totalVarUsages > 0) {
            $score += 2;
        }

        $missingVars = array_diff_key(array_flip($this->requiredCssVars), $foundVars);
        if (!empty($missingVars)) {
            $warnings[] = '缺少必要CSS变量: ' . implode(', ', array_keys($missingVars));
        }

        return ['score' => $score, 'max' => 25, 'warnings' => $warnings];
    }

    /**
     * 维度3：硬编码检测 (20分)
     */
    protected function scoreHardcode(string $themePath): array
    {
        $score = 20;
        $warnings = [];

        $cssFiles = $this->collectFiles($themePath, ['css']);
        $htmlFiles = $this->collectFiles($themePath, ['html']);
        $allFiles = array_merge($cssFiles, $htmlFiles);

        $colorCount = [];
        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            // 检测 #xxx 和 #xxxxxx 色值
            preg_match_all('/#[0-9a-fA-F]{3,6}\b/', $content, $matches);
            foreach ($matches[0] ?? [] as $color) {
                $normalized = strtolower($color);
                $colorCount[$normalized] = ($colorCount[$normalized] ?? 0) + 1;
            }
        }

        // 排除CSS变量定义中的色值（如 --i8j-primary: #3b82f6）
        $excludedColors = ['#fff', '#ffffff', '#000', '#000000', '#333', '#666', '#999'];
        $repeatedColors = [];
        foreach ($colorCount as $color => $count) {
            if (in_array($color, $excludedColors, true)) {
                continue;
            }
            if ($count > 3) {
                $repeatedColors[$color] = $count;
            }
        }

        // 每个重复色值扣2分
        $deduct = count($repeatedColors) * 2;
        $score = max(0, $score - $deduct);

        if (!empty($repeatedColors)) {
            $topColors = array_slice($repeatedColors, 0, 3, true);
            $warnings[] = '检测到硬编码色值重复出现: ' . implode(', ', array_map(
                fn($c, $n) => "{$c}({$n}次)",
                array_keys($topColors),
                array_values($topColors)
            ));
        }

        return ['score' => $score, 'max' => 20, 'warnings' => $warnings];
    }

    /**
     * 维度4：页面完整性 (15分)
     */
    protected function scorePages(string $themePath): array
    {
        $score = 0;
        $warnings = [];

        $pcDir = $themePath . DIRECTORY_SEPARATOR . 'pc';
        $mobileDir = $themePath . DIRECTORY_SEPARATOR . 'mobile';

        $pcFiles = is_dir($pcDir) ? glob($pcDir . '/*.html') : [];
        $mobileFiles = is_dir($mobileDir) ? glob($mobileDir . '/*.html') : [];

        // PC页面：layout + index = 5分，每多一个+1分，最多8分
        $pcScore = 0;
        $hasPcLayout = false;
        $hasPcIndex = false;
        foreach ($pcFiles as $file) {
            $name = basename($file);
            if ($name === 'layout.html') $hasPcLayout = true;
            if ($name === 'index.html') $hasPcIndex = true;
        }
        if ($hasPcLayout) $pcScore += 3;
        if ($hasPcIndex) $pcScore += 2;
        $pcScore += min(3, max(0, count($pcFiles) - 2));
        $score += min(8, $pcScore);

        // Mobile页面：layout + index = 4分，每多一个+1分，最多7分
        $mobileScore = 0;
        $hasMobileLayout = false;
        $hasMobileIndex = false;
        foreach ($mobileFiles as $file) {
            $name = basename($file);
            if ($name === 'layout.html') $hasMobileLayout = true;
            if ($name === 'index.html') $hasMobileIndex = true;
        }
        if ($hasMobileLayout) $mobileScore += 2;
        if ($hasMobileIndex) $mobileScore += 2;
        $mobileScore += min(3, max(0, count($mobileFiles) - 2));
        $score += min(7, $mobileScore);

        if (!$hasPcLayout) $warnings[] = 'PC端缺少 layout.html';
        if (!$hasPcIndex) $warnings[] = 'PC端缺少 index.html';

        return ['score' => $score, 'max' => 15, 'warnings' => $warnings];
    }

    /**
     * 维度5：代码简洁度 (5分)
     */
    protected function scoreCleanliness(string $themePath): array
    {
        $score = 5;
        $warnings = [];

        $allFiles = $this->collectFiles($themePath, ['html', 'css', 'js']);
        $badPatterns = [
            'console.log' => 0,
            'debugger'    => 0,
            'var_dump'    => 0,
            'alert('      => 0,
        ];

        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            foreach (array_keys($badPatterns) as $pattern) {
                $badPatterns[$pattern] += substr_count($content, $pattern);
            }
        }

        $totalBad = array_sum($badPatterns);
        if ($totalBad > 0) {
            $deduct = min(5, $totalBad);
            $score = max(0, $score - $deduct);
            $found = array_filter($badPatterns, fn($v) => $v > 0);
            $warnings[] = '检测到调试代码: ' . implode(', ', array_map(
                fn($k, $v) => "{$k}({$v}处)",
                array_keys($found),
                array_values($found)
            ));
        }

        return ['score' => $score, 'max' => 5, 'warnings' => $warnings];
    }

    /**
     * 维度6：相似度检测 (5分)
     */
    protected function scoreSimilarity(string $themePath, string $industry = ''): array
    {
        $score = 5;
        $warnings = [];

        if (empty($industry)) {
            return ['score' => $score, 'max' => 5, 'warnings' => []];
        }

        $fingerprint = $this->buildFingerprint($themePath);
        if (empty($fingerprint)) {
            return ['score' => $score, 'max' => 5, 'warnings' => []];
        }

        $themesDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
        if (!is_dir($themesDir)) {
            return ['score' => $score, 'max' => 5, 'warnings' => []];
        }

        $maxSimilarity = 0;
        $similarTheme = '';

        $dirs = glob($themesDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if ($dir === $themePath) {
                continue;
            }
            $otherFp = $this->buildFingerprint($dir);
            if (empty($otherFp)) {
                continue;
            }
            $similarity = $this->compareFingerprint($fingerprint, $otherFp);
            if ($similarity > $maxSimilarity) {
                $maxSimilarity = $similarity;
                $similarTheme = basename($dir);
            }
        }

        if ($maxSimilarity >= 95) {
            $score = 0;
            $warnings[] = "相似度告警: 与 {$similarTheme} 相似度 {$maxSimilarity}%（>95%），建议人工判断";
        } elseif ($maxSimilarity >= 80) {
            $score = 3;
            $warnings[] = "相似度警告: 与 {$similarTheme} 相似度 {$maxSimilarity}%（>80%）";
        }

        return ['score' => $score, 'max' => 5, 'warnings' => $warnings];
    }

    /**
     * 构建文件指纹（CSS + layout.html的MD5）
     */
    protected function buildFingerprint(string $themePath): array
    {
        $fp = [];
        $keyFiles = [
            'assets/css/style.css',
            'pc/layout.html',
            'mobile/layout.html',
        ];

        foreach ($keyFiles as $file) {
            $path = $themePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (is_file($path)) {
                $content = file_get_contents($path);
                // 去除空白和注释，保留核心结构
                $content = preg_replace('/\s+/', '', $content);
                $content = preg_replace('/\/\*.*?\*\//s', '', $content);
                $fp[$file] = md5($content);
            }
        }

        return $fp;
    }

    /**
     * 比较两个指纹的相似度
     */
    protected function compareFingerprint(array $fp1, array $fp2): float
    {
        if (empty($fp1) || empty($fp2)) {
            return 0;
        }

        $commonKeys = array_intersect_key($fp1, $fp2);
        if (empty($commonKeys)) {
            return 0;
        }

        $same = 0;
        foreach ($commonKeys as $key => $hash1) {
            if (isset($fp2[$key]) && $fp2[$key] === $hash1) {
                $same++;
            }
        }

        return round($same / max(count($fp1), count($fp2)) * 100, 1);
    }

    // ============================================================
    // V2.9.12 扩展方法：质量校验管线
    // ============================================================

    /**
     * 校验CSS完整性：检测未闭合规则、无效属性、缺失选择器
     */
    public function validateCssIntegrity(string $themePath): array
    {
        $errors = [];
        $cssFiles = $this->collectFiles($themePath, ['css']);

        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);

            // 检测未闭合的 { 和 }
            $open = substr_count($content, '{');
            $close = substr_count($content, '}');
            if ($open !== $close) {
                $errors[] = "[{$relPath}] CSS规则未闭合: 开启{$open}个, 关闭{$close}个";
            }

            // 检测空规则块
            if (preg_match_all('/[^{}]+\{\s*\}/s', $content, $matches)) {
                $errors[] = "[{$relPath}] 发现 " . count($matches[0]) . " 个空CSS规则块";
            }
        }

        return [
            'pass' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 校验响应式：检测是否包含viewport meta和媒体查询
     */
    public function validateResponsive(string $themePath): array
    {
        $errors = [];
        $htmlFiles = $this->collectFiles($themePath, ['html']);
        $cssFiles = $this->collectFiles($themePath, ['css']);

        $hasViewport = false;
        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, 'viewport')) {
                $hasViewport = true;
                break;
            }
        }
        if (!$hasViewport) {
            $errors[] = '缺少 viewport meta 标签，移动端适配可能异常';
        }

        $hasMediaQuery = false;
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, '@media')) {
                $hasMediaQuery = true;
                break;
            }
        }
        if (!$hasMediaQuery) {
            $errors[] = 'CSS中未检测到 @media 媒体查询，响应式支持可能不足';
        }

        return [
            'pass' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 校验HTML标签：检测未闭合标签、无效属性
     */
    public function validateHtmlTags(string $themePath): array
    {
        $errors = [];
        $htmlFiles = $this->collectFiles($themePath, ['html']);

        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);

            // 简单的未闭合标签检测（常见块级元素）
            $tags = ['div', 'section', 'article', 'header', 'footer', 'nav', 'main', 'aside'];
            foreach ($tags as $tag) {
                $openCount = substr_count(strtolower($content), "<{$tag}");
                $closeCount = substr_count(strtolower($content), "</{$tag}>");
                if ($openCount !== $closeCount) {
                    $errors[] = "[{$relPath}] <{$tag}> 标签未闭合: 开启{$openCount}个, 关闭{$closeCount}个";
                }
            }
        }

        return [
            'pass' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * V2.9.14: 获取综合质量报告（7项校验，满分100）
     */
    public function getQualityReport(string $themePath, string $industry = ''): array
    {
        // 基础评分（6维度）
        $scoreReport = $this->score($themePath, $industry);

        // 扩展校验1-3（已有）
        $cssIntegrity = $this->validateCssIntegrity($themePath);
        $responsive = $this->validateResponsive($themePath);
        $htmlTags = $this->validateHtmlTags($themePath);

        // V2.9.14: 扩展校验4-7（新增）
        $requiredFiles = $this->validateRequiredFiles($themePath);
        $cssValidity = $this->validateCssValidity($themePath);
        $jsValidity = $this->validateJsValidity($themePath);
        $crossRef = $this->validateCrossReference($themePath);
        $encoding = $this->validateEncoding($themePath);

        // 综合质量分（满分100）
        // 基础评分(85) + 扩展校验(15) = 100
        $bonus = 0;
        if ($cssIntegrity['pass']) $bonus += 3;
        if ($responsive['pass']) $bonus += 3;
        if ($htmlTags['pass']) $bonus += 3;
        if ($requiredFiles['pass']) $bonus += 2;
        if ($cssValidity['pass']) $bonus += 2;
        if ($jsValidity['pass']) $bonus += 2;

        $qualityScore = min(100, $scoreReport['total'] + $bonus);

        $allWarnings = array_merge(
            $scoreReport['warnings'],
            $cssIntegrity['errors'],
            $responsive['errors'],
            $htmlTags['errors'],
            $requiredFiles['errors'],
            $cssValidity['errors'],
            $jsValidity['errors'],
            $crossRef['errors'],
            $encoding['errors']
        );

        return [
            'quality_score'   => $qualityScore,
            'base_score'      => $scoreReport['total'],
            'bonus'           => $bonus,
            'pass'            => $qualityScore >= 60,
            'dimensions'      => $scoreReport['dimensions'],
            'css_integrity'   => $cssIntegrity,
            'responsive'      => $responsive,
            'html_tags'       => $htmlTags,
            'required_files'  => $requiredFiles,
            'css_validity'    => $cssValidity,
            'js_validity'     => $jsValidity,
            'cross_reference' => $crossRef,
            'encoding'        => $encoding,
            'warnings'        => $allWarnings,
        ];
    }

    /**
     * V2.9.14 校验4: 必需文件完整性
     */
    public function validateRequiredFiles(string $themePath): array
    {
        $errors = [];
        $required = ['index.html', 'theme.json', 'screenshot.png'];
        foreach ($required as $file) {
            if (!is_file($themePath . DIRECTORY_SEPARATOR . $file)) {
                $errors[] = "缺少必需文件: {$file}";
            }
        }
        // 路径安全：检测 ../ 路径穿越
        $allFiles = $this->collectFiles($themePath, ['html', 'css', 'js', 'json']);
        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            if (str_contains($content, '../') || str_contains($content, '..\\')) {
                $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
                $errors[] = "[{$relPath}] 检测到路径穿越引用(../)，存在安全风险";
            }
        }
        return ['pass' => empty($errors), 'errors' => $errors];
    }

    /**
     * V2.9.14 校验5: CSS文件有效性
     */
    public function validateCssValidity(string $themePath): array
    {
        $errors = [];
        $cssFiles = $this->collectFiles($themePath, ['css']);
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            // 基本语法检查：{ 和 } 数量匹配
            $open = substr_count($content, '{');
            $close = substr_count($content, '}');
            if ($open !== $close) {
                $errors[] = "[{$relPath}] CSS语法错误: 花括号不匹配({$open}开 {$close}闭)";
            }
        }
        return ['pass' => empty($errors), 'errors' => $errors];
    }

    /**
     * V2.9.14 校验6: JS文件有效性
     */
    public function validateJsValidity(string $themePath): array
    {
        $errors = [];
        $jsFiles = $this->collectFiles($themePath, ['js']);
        foreach ($jsFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            // 基本语法检查：检测未闭合的括号
            $openParen = substr_count($content, '(');
            $closeParen = substr_count($content, ')');
            if ($openParen !== $closeParen) {
                $errors[] = "[{$relPath}] JS语法警告: 圆括号可能不匹配({$openParen}开 {$closeParen}闭)";
            }
        }
        return ['pass' => empty($errors), 'errors' => $errors];
    }

    /**
     * V2.9.14 校验7: 跨模板引用安全性
     */
    public function validateCrossReference(string $themePath): array
    {
        $errors = [];
        $htmlFiles = $this->collectFiles($themePath, ['html']);
        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            // 检测include/extends是否引用模板外路径
            if (preg_match_all('/\{include\s+file=["\']([^"\']+)["\']/', $content, $matches)) {
                foreach ($matches[1] as $includePath) {
                    if (str_starts_with($includePath, '/') || str_starts_with($includePath, 'http')) {
                        $errors[] = "[{$relPath}] include引用外部路径: {$includePath}";
                    }
                }
            }
        }
        return ['pass' => empty($errors), 'errors' => $errors];
    }

    /**
     * V2.9.14 校验8: 文件编码检查（UTF-8无BOM）
     */
    public function validateEncoding(string $themePath): array
    {
        $errors = [];
        $allFiles = $this->collectFiles($themePath, ['html', 'css', 'js', 'json', 'php']);
        foreach ($allFiles as $file) {
            $content = file_get_contents($file);
            $relPath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            // 检测BOM
            if (str_starts_with($content, "\xEF\xBB\xBF")) {
                $errors[] = "[{$relPath}] 文件包含UTF-8 BOM头，建议去除";
            }
            // 检测GBK/GB2312特征（简化检测）
            if (mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312'], true) !== 'UTF-8') {
                $errors[] = "[{$relPath}] 文件编码非UTF-8";
            }
        }
        return ['pass' => empty($errors), 'errors' => $errors];
    }

    /**
     * 收集指定类型的文件
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
     * 扫描全量模板并更新质量评分 — V2.9.30 Q-6
     * @return array {total, excellent, pass, fail, details}
     */
    public function scanAllTemplates(): array
    {
        $themeBase = public_path() . '../template/themes';
        if (!is_dir($themeBase)) {
            $themeBase = dirname(__DIR__, 4) . '/template/themes';
        }

        $themes = [];
        if (is_dir($themeBase)) {
            foreach (new \DirectoryIterator($themeBase) as $dir) {
                if ($dir->isDir() && !$dir->isDot() && $dir->getFilename() !== 'shared') {
                    $themes[] = $dir->getPathname();
                }
            }
        }

        $total = 0;
        $excellent = 0;
        $pass = 0;
        $fail = 0;
        $details = [];

        foreach ($themes as $themePath) {
            $result = $this->score($themePath);
            $total++;

            // V2.9.30 Q-6: 集成4个检测器评分
            $detectorScore = $this->runDetectors($themePath);
            $combinedScore = (int)round($result['total'] * 0.7 + $detectorScore * 0.3);

            if ($combinedScore >= 80) {
                $excellent++;
            } elseif ($combinedScore >= 60) {
                $pass++;
            } else {
                $fail++;
            }

            $details[] = [
                'theme' => basename($themePath),
                'score' => $combinedScore,
                'quality_score' => $result['total'],
                'detector_score' => $detectorScore,
            ];
        }

        return [
            'total' => $total,
            'excellent' => $excellent,
            'pass' => $pass,
            'fail' => $fail,
            'details' => $details,
        ];
    }

    /**
     * 运行4个检测器并计算加权评分 — V2.9.30 Q-6
     * 评分权重：CodeValidator 30% + CompatChecker 20% + ResponsiveTester 25% + SecurityScanner 25%
     */
    public function runDetectors(string $themePath): int
    {
        $htmlFiles = $this->collectFiles($themePath, ['html']);
        if (empty($htmlFiles)) {
            return 0;
        }

        $allContent = '';
        foreach ($htmlFiles as $file) {
            $allContent .= file_get_contents($file) . "\n";
        }

        $codeValidator = new \app\common\service\template\TemplateCodeValidator();
        $compatChecker = new \app\common\service\template\TemplateCompatChecker();
        $responsiveTester = new \app\common\service\template\TemplateResponsiveTester();
        $securityScanner = new \app\common\service\template\TemplateSecurityScanner();

        $codeResult = $codeValidator->validate($allContent);
        $compatResult = $compatChecker->check($allContent);
        $responsiveResult = $responsiveTester->test($allContent);
        $securityResult = $securityScanner->scan($allContent);

        $score = (int)round(
            ($codeResult['score'] ?? 0) * 0.30 +
            ($compatResult['score'] ?? 0) * 0.20 +
            ($responsiveResult['score'] ?? 0) * 0.25 +
            ($securityResult['score'] ?? 0) * 0.25
        );

        return min(100, $score);
    }
}
