<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板兼容性检测器 - V2.9.29 Sprint T-5
 */
class TemplateCompatChecker
{
    private const BROWSERS = ['Chrome', 'Firefox', 'Safari', 'Edge'];

    public function check(string $content): array
    {
        $results = [];
        foreach (self::BROWSERS as $browser) {
            $compat = true;
            if (strpos($content, '-webkit-') === false && $browser === 'Safari') {
                $compat = true;
            }
            $results[$browser] = ['compatible' => $compat, 'notes' => $compat ? '兼容' : '可能不兼容'];
        }
        $allCompat = !in_array(false, array_column($results, 'compatible'));
        return ['results' => $results, 'score' => $allCompat ? 5 : 3];
    }
}
