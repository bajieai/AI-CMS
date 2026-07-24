<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateAuditReport;
use app\common\model\TemplateStore;

/**
 * 模板自动审核服务 (V2.9.29 T-5)
 * 
 * 调用4个独立检测器Service：
 * - TemplateCodeValidator（代码规范）
 * - TemplateCompatChecker（兼容性）
 * - TemplateResponsiveTester（响应式）
 * - TemplateSecurityScanner（安全扫描）
 */
class TemplateAutoAuditService
{
    private TemplateCodeValidator $codeValidator;
    private TemplateCompatChecker $compatChecker;
    private TemplateResponsiveTester $responsiveTester;
    private TemplateSecurityScanner $securityScanner;

    public function __construct()
    {
        $this->codeValidator = new TemplateCodeValidator();
        $this->compatChecker = new TemplateCompatChecker();
        $this->responsiveTester = new TemplateResponsiveTester();
        $this->securityScanner = new TemplateSecurityScanner();
    }

    public function audit(int $templateId): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'error' => '模板不存在'];

        $content = $this->collectTemplateContent($template);
        $issues = [];

        // 1. 代码规范检测（独立Service）
        $codeResult = $this->codeValidator->validate($content);
        $codeScore = $codeResult['score'];
        if (!$codeResult['valid']) $issues['code'] = $codeResult['issues'];

        // 2. 兼容性检测（独立Service）
        $compatResult = $this->compatChecker->check($content);
        $compatScore = $compatResult['score'];
        if (isset($compatResult['results'])) {
            $incompatible = array_filter($compatResult['results'], fn($r) => !$r['compatible']);
            if (!empty($incompatible)) $issues['compatibility'] = $incompatible;
        }

        // 3. 响应式检测（独立Service）
        $responsiveResult = $this->responsiveTester->test($content);
        $responsiveScore = $responsiveResult['score'];
        if ($responsiveScore < 3) $issues['responsive'] = $responsiveResult['message'];

        // 4. 安全扫描（独立Service）
        $securityResult = $this->securityScanner->scan($content);
        $securityScore = $securityResult['score'];
        if (!$securityResult['safe']) $issues['security'] = $securityResult['risks'];

        $totalScore = round(($codeScore + $compatScore + $responsiveScore + $securityScore) / 4, 1);

        $report = TemplateAuditReport::create([
            'template_id' => $templateId,
            'code_quality_score' => $codeScore,
            'compatibility_score' => $compatScore,
            'responsive_score' => $responsiveScore,
            'security_score' => $securityScore,
            'total_score' => $totalScore,
            'issues' => json_encode($issues, JSON_UNESCAPED_UNICODE),
            'status' => $totalScore >= 3.0 ? 1 : 2,
            'create_time' => time(),
        ]);

        return [
            'success' => true,
            'report_id' => $report->id,
            'total_score' => $totalScore,
            'passed' => $totalScore >= 3.0,
            'scores' => [
                'code' => $codeScore,
                'compatibility' => $compatScore,
                'responsive' => $responsiveScore,
                'security' => $securityScore,
            ],
            'issues' => $issues,
        ];
    }

    public function getReport(int $templateId): array
    {
        $report = TemplateAuditReport::where('template_id', $templateId)
            ->order('id', 'desc')->find();
        if (!$report) return $this->audit($templateId);
        return $report->toArray();
    }

    private function collectTemplateContent(TemplateStore $template): string
    {
        $content = $template->description ?? '';
        $themePath = root_path() . 'template/themes/';
        $skins = ['default', 'corporate'];
        foreach ($skins as $skin) {
            $tplFile = $themePath . $skin . '/pc/' . ($template->code ?? '') . '.html';
            if (file_exists($tplFile)) {
                $content .= file_get_contents($tplFile);
            }
        }
        return $content;
    }
}
