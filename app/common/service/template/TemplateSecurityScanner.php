<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板安全扫描器 — V2.9.29 T-5 / V2.9.30 Q-6增强
 * 检测规则：10条（原7条+新增3条）
 */
class TemplateSecurityScanner
{
    /**
     * 危险模式列表
     */
    private const DANGEROUS_PATTERNS = [
        // 原有规则
        '/eval\s*\(/i' => ['msg' => '检测到eval()函数调用', 'severity' => 'high'],
        '/system\s*\(/i' => ['msg' => '检测到system()函数调用', 'severity' => 'high'],
        '/exec\s*\(/i' => ['msg' => '检测到exec()函数调用', 'severity' => 'high'],
        '/<\?php/i' => ['msg' => '检测到PHP代码嵌入', 'severity' => 'high'],
        '/onerror\s*=/i' => ['msg' => '检测到onerror事件可能存在XSS风险', 'severity' => 'medium'],
        '/javascript:/i' => ['msg' => '检测到javascript:协议可能存在XSS风险', 'severity' => 'medium'],
        '/<iframe/i' => ['msg' => '检测到iframe标签需人工审核', 'severity' => 'low'],
        // V2.9.30 Q-6 新增规则
        '/<script[^>]*>[^<]*document\.write/i' => ['msg' => '检测到script内使用document.write()，可能存在XSS风险', 'severity' => 'high'],
        '/<a[^>]*href\s*=\s*["\']\s*javascript:/i' => ['msg' => '检测到a标签href属性使用javascript:协议', 'severity' => 'high'],
        '/document\.cookie/i' => ['msg' => '检测到document.cookie访问，可能存在敏感信息泄露风险', 'severity' => 'medium'],
    ];

    /**
     * 扫描模板安全风险
     * @param string $content 模板内容
     * @return array {risks: array, safe: bool, score: int(0-100)}
     */
    public function scan(string $content): array
    {
        $risks = [];
        $totalRules = count(self::DANGEROUS_PATTERNS);
        $failedRules = 0;

        foreach (self::DANGEROUS_PATTERNS as $pattern => $info) {
            if (preg_match($pattern, $content)) {
                $risks[] = [
                    'pattern' => $pattern,
                    'message' => $info['msg'],
                    'severity' => $info['severity'],
                ];
                $failedRules++;
            }
        }

        $passedRules = $totalRules - $failedRules;

        // 严重风险加权扣分
        $highRisks = count(array_filter($risks, fn($r) => $r['severity'] === 'high'));
        $mediumRisks = count(array_filter($risks, fn($r) => $r['severity'] === 'medium'));
        $penalty = $highRisks * 15 + $mediumRisks * 5;
        $score = max(0, (int)round($passedRules / $totalRules * 100) - $penalty);

        return [
            'risks' => $risks,
            'safe' => empty($risks),
            'passed_rules' => $passedRules,
            'total_rules' => $totalRules,
            'high_risks' => $highRisks,
            'medium_risks' => $mediumRisks,
            'score' => $score,
        ];
    }
}
