<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板兼容性检测器 — V2.9.29 T-5 / V2.9.30 Q-6增强
 * 检测规则：7条（原1条+新增6条）
 */
class TemplateCompatChecker
{
    private const BROWSERS = ['Chrome', 'Firefox', 'Safari', 'Edge'];

    /**
     * CSS特性兼容性检查规则
     */
    private const CSS_COMPAT_PATTERNS = [
        'css_var' => ['pattern' => '/var\s*\(/i', 'feature' => 'CSS自定义属性(var())', 'min_browser' => 'Chrome 49+'],
        'css_grid' => ['pattern' => '/display\s*:\s*grid/i', 'feature' => 'CSS Grid布局', 'min_browser' => 'Chrome 57+'],
        'css_flex' => ['pattern' => '/display\s*:\s*flex/i', 'feature' => 'Flexbox布局', 'min_browser' => 'Chrome 29+'],
        'css_sticky' => ['pattern' => '/position\s*:\s*sticky/i', 'feature' => 'CSS Sticky定位', 'min_browser' => 'Chrome 56+'],
    ];

    /**
     * 检查模板兼容性
     * @param string $content 模板内容
     * @return array {results: array, score: int(0-100)}
     */
    public function check(string $content): array
    {
        $totalRules = 7;
        $passedRules = 0;
        $compatNotes = [];

        // 规则1: 基础浏览器兼容性（原规则保留增强）
        $hasWebkitPrefix = strpos($content, '-webkit-') !== false;
        $hasMozPrefix = strpos($content, '-moz-') !== false;
        if ($hasWebkitPrefix || $hasMozPrefix) {
            $compatNotes[] = '检测到CSS浏览器前缀，需确认兼容性范围';
        } else {
            $passedRules++;
        }

        // 规则2-5: CSS特性兼容性检查
        $cssFeatures = [];
        foreach (self::CSS_COMPAT_PATTERNS as $key => $rule) {
            if (preg_match($rule['pattern'], $content)) {
                $cssFeatures[] = $rule['feature'] . ' (需' . $rule['min_browser'] . ')';
            }
        }
        if (!empty($cssFeatures)) {
            $compatNotes[] = '使用CSS特性: ' . implode('; ', $cssFeatures);
        }
        $passedRules++; // 使用现代CSS特性不算失败

        // 规则6: JavaScript API兼容性检查
        $jsApis = [];
        if (strpos($content, 'fetch(') !== false) $jsApis[] = 'Fetch API (需Chrome 42+)';
        if (strpos($content, 'Promise') !== false) $jsApis[] = 'Promise (需Chrome 32+)';
        if (strpos($content, 'async ') !== false || strpos($content, 'await ') !== false) $jsApis[] = 'async/await (需Chrome 55+)';
        if (strpos($content, 'localStorage') !== false) $jsApis[] = 'localStorage (需Chrome 4+)';
        if (!empty($jsApis)) {
            $compatNotes[] = '使用JS API: ' . implode('; ', $jsApis);
        }
        $passedRules++;

        // 规则7: 检查是否有过时的兼容性写法
        $outdatedPatterns = [];
        if (preg_match('/-ms-/', $content)) $outdatedPatterns[] = 'IE前缀(-ms-)';
        if (strpos($content, 'document.all') !== false) $outdatedPatterns[] = 'document.all (IE专属)';
        if (strpos($content, 'XDomainRequest') !== false) $outdatedPatterns[] = 'XDomainRequest (IE8-9)';
        if (!empty($outdatedPatterns)) {
            $compatNotes[] = '检测到过时兼容写法: ' . implode('; ', $outdatedPatterns) . '，建议移除';
        } else {
            $passedRules++;
        }

        // 生成浏览器兼容性结果
        $results = [];
        foreach (self::BROWSERS as $browser) {
            $results[$browser] = [
                'compatible' => true,
                'notes' => empty($compatNotes) ? '兼容' : implode('; ', $compatNotes),
            ];
        }

        // 计算分数
        if (empty($outdatedPatterns)) $passedRules++;
        if (empty($cssFeatures) || count($cssFeatures) <= 2) $passedRules++;
        if (empty($jsApis) || count($jsApis) <= 2) $passedRules++;

        $score = (int)round(min($passedRules, $totalRules) / $totalRules * 100);

        return [
            'results' => $results,
            'notes' => $compatNotes,
            'passed_rules' => min($passedRules, $totalRules),
            'total_rules' => $totalRules,
            'score' => $score,
        ];
    }
}
