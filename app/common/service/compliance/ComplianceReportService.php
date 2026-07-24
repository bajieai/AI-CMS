<?php
declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Cache;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 合规报告服务 - V2.9.40 COMPLIANCE2-2
 *
 * 合规报告生成：GDPR合规报告、数据安全报告、审计统计报告
 */
class ComplianceReportService
{
    private const CACHE_TAG = 'compliance_report';
    private const CACHE_TTL = 3600;

    /**
     * 生成GDPR合规报告
     */
    public function generateGdprReport(): array
    {
        return Cache::remember('gdpr_report', function () {
            $gdprService = new GdprService();

            return [
                'title'     => 'GDPR合规报告',
                'date'      => date('Y-m-d'),
                'cookie_compliance' => [
                    'total_users'      => Db::name('member')->count(),
                    'consented_users'  => Db::name('cookie_consent')->where('status', 1)->count(),
                    'consent_rate'     => round(Db::name('cookie_consent')->where('status', 1)->count() / max(Db::name('member')->count(), 1) * 100, 1),
                    'categories'       => Db::name('cookie_consent')->group('category')->column('count(*) as cnt', 'category'),
                ],
                'data_requests' => [
                    'total_requests'    => Db::name('gdpr_request')->count(),
                    'access_requests'   => Db::name('gdpr_request')->where('type', 'access')->count(),
                    'deletion_requests' => Db::name('gdpr_request')->where('type', 'deletion')->count(),
                    'portability_requests' => Db::name('gdpr_request')->where('type', 'portability')->count(),
                    'pending'           => Db::name('gdpr_request')->where('status', 'pending')->count(),
                    'completed'         => Db::name('gdpr_request')->where('status', 'completed')->count(),
                ],
                'privacy_policy' => [
                    'current_version'   => Db::name('privacy_policy')->where('status', 1)->order('id', 'desc')->value('version') ?: '1.0',
                    'last_update'       => Db::name('privacy_policy')->where('status', 1)->order('id', 'desc')->value('created_at') ?: 0,
                    'total_versions'    => Db::name('privacy_policy')->count(),
                ],
                'overall_score' => $this->calcGdprScore(),
            ];
        }, self::CACHE_TTL);
    }

    private function calcGdprScore(): float
    {
        $consentRate = Db::name('cookie_consent')->where('status', 1)->count() / max(Db::name('member')->count(), 1);
        $completionRate = Db::name('gdpr_request')->where('status', 'completed')->count() / max(Db::name('gdpr_request')->count(), 1);
        $hasPolicy = Db::name('privacy_policy')->where('status', 1)->count() > 0;

        $score = $consentRate * 30 + $completionRate * 30 + ($hasPolicy ? 40 : 0);
        return round($score, 1);
    }

    /**
     * 生成数据安全报告
     */
    public function generateSecurityReport(): array
    {
        return [
            'title' => '数据安全合规报告',
            'date'  => date('Y-m-d'),
            'data_masking' => [
                'total_rules'    => Db::name('data_mask_rule')->count(),
                'active_rules'   => Db::name('data_mask_rule')->where('status', 1)->count(),
                'masked_fields'  => Db::name('data_mask_rule')->where('status', 1)->sum('field_count') ?: 0,
            ],
            'classification' => [
                'total_items'    => Db::name('data_classification')->count(),
                'public_items'   => Db::name('data_classification')->where('level', 'public')->count(),
                'internal_items' => Db::name('data_classification')->where('level', 'internal')->count(),
                'confidential_items' => Db::name('data_classification')->where('level', 'confidential')->count(),
                'restricted_items'   => Db::name('data_classification')->where('level', 'restricted')->count(),
            ],
            'audit_events' => [
                'total_today'    => Db::name('audit_log')->whereTime('created_at', 'today')->count(),
                'high_risk_today' => Db::name('audit_log')->where('risk_level', 'high')->whereTime('created_at', 'today')->count(),
                'suspicious_ips'  => Db::name('audit_log')->where('risk_level', 'high')->whereTime('created_at', 'today')->group('ip')->column('ip'),
            ],
            'overall_score' => $this->calcSecurityScore(),
        ];
    }

    private function calcSecurityScore(): float
    {
        $maskRules = Db::name('data_mask_rule')->where('status', 1)->count();
        $classItems = Db::name('data_classification')->count();
        $highRisk = Db::name('audit_log')->where('risk_level', 'high')->whereTime('created_at', 'today')->count();

        $score = min($maskRules * 5, 30) + min($classItems * 2, 30) + (max(10 - $highRisk, 0) * 4);
        return round(min($score, 100), 1);
    }

    /**
     * 生成审计统计报告
     */
    public function generateAuditReport(string $period = 'monthly'): array
    {
        $timeFilter = match ($period) {
            'daily'   => 'today',
            'weekly'  => 'week',
            'monthly' => 'month',
            default   => 'month',
        };

        return [
            'title'  => '审计统计报告 - ' . $period,
            'date'   => date('Y-m-d'),
            'period' => $period,
            'summary' => [
                'total_events'    => Db::name('audit_log')->whereTime('created_at', $timeFilter)->count(),
                'unique_users'    => Db::name('audit_log')->whereTime('created_at', $timeFilter)->group('user_id')->count(),
                'unique_ips'      => Db::name('audit_log')->whereTime('created_at', $timeFilter)->group('ip')->count(),
            ],
            'action_distribution' => Db::name('audit_log')
                ->whereTime('created_at', $timeFilter)
                ->group('action')
                ->column('count(*) as cnt', 'action'),
            'module_distribution' => Db::name('audit_log')
                ->whereTime('created_at', $timeFilter)
                ->group('module')
                ->column('count(*) as cnt', 'module'),
            'risk_distribution' => Db::name('audit_log')
                ->whereTime('created_at', $timeFilter)
                ->group('risk_level')
                ->column('count(*) as cnt', 'risk_level'),
        ];
    }

    /**
     * 保存合规报告
     */
    public function saveReport(string $type, array $data): int
    {
        $id = Db::name('compliance_report')->insertGetId([
            'type'        => $type,
            'title'       => $data['title'] ?? '',
            'content'     => json_encode($data),
            'score'       => (float) ($data['overall_score'] ?? 0),
            'status'      => 1,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 获取报告列表
     */
    public function getReportList(int $page = 1, int $limit = 20): array
    {
        return Db::name('compliance_report')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
}
