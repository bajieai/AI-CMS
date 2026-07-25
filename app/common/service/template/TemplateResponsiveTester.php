<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板响应式检测器 — V2.9.29 T-5 / V2.9.30 Q-6增强
 * 检测规则：8条（原3条+新增5条）
 */
class TemplateResponsiveTester
{
    /**
     * 测试模板响应式设计
     * @param string $content 模板内容
     * @return array {viewport, flexbox, grid, score: int(0-100)}
     */
    public function test(string $content): array
    {
        $totalRules = 8;
        $passedRules = 0;
        $details = [];

        // 规则1: viewport meta标签检查
        $hasViewport = strpos($content, 'viewport') !== false;
        $details['viewport'] = $hasViewport;
        if ($hasViewport) $passedRules++;

        // 规则2: Flexbox布局检查
        $hasFlex = strpos($content, 'flex') !== false || strpos($content, 'd-flex') !== false;
        $details['flexbox'] = $hasFlex;
        if ($hasFlex) $passedRules++;

        // 规则3: Grid布局检查
        $hasGrid = strpos($content, 'grid') !== false || strpos($content, 'row') !== false;
        $details['grid'] = $hasGrid;
        if ($hasGrid) $passedRules++;

        // 规则4: 媒体查询检查
        $hasMediaQuery = strpos($content, '@media') !== false;
        $details['media_query'] = $hasMediaQuery;
        if ($hasMediaQuery) $passedRules++;

        // 规则5: 响应式断点检查（320px/768px/1920px）
        $hasBreakpoint = preg_match('/(\d{3,4})px/', $content);
        $details['breakpoint'] = (bool)$hasBreakpoint;
        if ($hasBreakpoint) $passedRules++;

        // 规则6: 字体缩放检查（rem/em单位）
        $hasRelativeFont = preg_match('/font-size\s*:\s*[\d.]+(rem|em)/i', $content);
        $details['relative_font'] = (bool)$hasRelativeFont;
        if ($hasRelativeFont) $passedRules++;

        // 规则7: 图片响应式检查（max-width:100%或img-fluid）
        $hasResponsiveImg = strpos($content, 'max-width:100%') !== false
            || strpos($content, 'max-width: 100%') !== false
            || strpos($content, 'img-fluid') !== false
            || strpos($content, 'img-responsive') !== false;
        $details['responsive_img'] = $hasResponsiveImg;
        if ($hasResponsiveImg) $passedRules++;

        // 规则8: 触控区域检查（min-width/min-height ≥ 44px的按钮或链接）
        $hasTouchTarget = preg_match('/min-(width|height)\s*:\s*(4[4-9]|[5-9]\d|\d{3,})px/i', $content);
        $details['touch_target'] = (bool)$hasTouchTarget;
        if ($hasTouchTarget) $passedRules++;

        $score = (int)round($passedRules / $totalRules * 100);
        $details['passed_rules'] = $passedRules;
        $details['total_rules'] = $totalRules;
        $details['score'] = $score;
        $details['message'] = $score >= 75 ? '响应式设计良好' : ($score >= 50 ? '响应式设计一般，建议优化' : '响应式设计不足');

        return $details;
    }
}
