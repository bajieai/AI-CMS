<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板响应式检测器 - V2.9.29 Sprint T-5
 */
class TemplateResponsiveTester
{
    public function test(string $content): array
    {
        $hasViewport = strpos($content, 'viewport') !== false || strpos($content, '@media') !== false;
        $hasFlex = strpos($content, 'flex') !== false || strpos($content, 'd-flex') !== false;
        $hasGrid = strpos($content, 'grid') !== false || strpos($content, 'row') !== false;

        $score = ($hasViewport ? 2 : 0) + ($hasFlex ? 2 : 0) + ($hasGrid ? 1 : 0);
        return [
            'viewport' => $hasViewport,
            'flexbox' => $hasFlex,
            'grid' => $hasGrid,
            'score' => min($score, 5),
            'message' => $score >= 3 ? '响应式设计良好' : '响应式设计不足',
        ];
    }
}
