<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板安全扫描器 - V2.9.29 Sprint T-5
 */
class TemplateSecurityScanner
{
    private const DANGEROUS_PATTERNS = [
        '/eval\s*\(/i' => '检测到eval()函数调用',
        '/system\s*\(/i' => '检测到system()函数调用',
        '/exec\s*\(/i' => '检测到exec()函数调用',
        '/<\?php/i' => '检测到PHP代码嵌入',
        '/onerror\s*=/i' => '检测到onerror事件可能存在XSS风险',
        '/javascript:/i' => '检测到javascript:协议可能存在XSS风险',
        '/<iframe/i' => '检测到iframe标签需人工审核',
    ];

    public function scan(string $content): array
    {
        $risks = [];
        foreach (self::DANGEROUS_PATTERNS as $pattern => $message) {
            if (preg_match($pattern, $content)) {
                $risks[] = ['pattern' => $pattern, 'message' => $message];
            }
        }
        return [
            'risks' => $risks,
            'safe' => empty($risks),
            'score' => empty($risks) ? 5 : (count($risks) > 2 ? 1 : 3),
        ];
    }
}
