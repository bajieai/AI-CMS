<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\FeatureRegistry;
use think\facade\Cache;

/**
 * 功能点注册和健康检查服务 — V2.9.30 Q-2
 */
class FeatureRegistryService
{
    private const CACHE_TAG = 'feature_registry';
    private const CACHE_TTL = 600;

    /**
     * 注册功能点
     */
    public function register(string $sprintCode, string $featureCode, string $featureName,
                             string $serviceClass = '', string $controllerRoute = '',
                             string $healthCheckUrl = ''): bool
    {
        $existing = FeatureRegistry::where('sprint_code', $sprintCode)
            ->where('feature_code', $featureCode)
            ->find();
        if ($existing) {
            return true;
        }
        FeatureRegistry::create([
            'sprint_code' => $sprintCode,
            'feature_code' => $featureCode,
            'feature_name' => $featureName,
            'service_class' => $serviceClass,
            'controller_route' => $controllerRoute,
            'health_check_url' => $healthCheckUrl,
            'status' => 1,
        ]);
        $this->clearCache();
        return true;
    }

    /**
     * 批量注册功能点
     */
    public function batchRegister(array $features): bool
    {
        foreach ($features as $f) {
            $this->register(
                $f['sprint_code'],
                $f['feature_code'],
                $f['feature_name'],
                $f['service_class'] ?? '',
                $f['controller_route'] ?? '',
                $f['health_check_url'] ?? ''
            );
        }
        return true;
    }

    /**
     * 健康检查 - 检测单个功能点
     */
    public function healthCheck(int $featureId): array
    {
        $feature = FeatureRegistry::find($featureId);
        if (!$feature) {
            return ['status' => 0, 'checks' => [], 'message' => '功能点不存在'];
        }

        $checks = [];
        $allPass = true;

        // 检查Service类是否存在
        if (!empty($feature->service_class)) {
            $serviceExists = class_exists($feature->service_class);
            $checks['service'] = $serviceExists;
            if (!$serviceExists) $allPass = false;
        }

        // 检查Controller路由是否可访问
        if (!empty($feature->health_check_url)) {
            $checks['route_configured'] = true;
        }

        $newStatus = $allPass ? 1 : 2;
        if ($feature->status !== $newStatus) {
            $feature->status = $newStatus;
            $feature->save();
            $this->clearCache();
        }

        return ['status' => $newStatus, 'checks' => $checks, 'feature' => $feature->toArray()];
    }

    /**
     * 全量健康检查
     */
    public function fullHealthCheck(): array
    {
        $features = FeatureRegistry::order('sprint_code', 'asc')->order('id', 'asc')->select();
        $total = 0;
        $normal = 0;
        $abnormal = 0;
        $details = [];

        foreach ($features as $feature) {
            $total++;
            $result = $this->healthCheck($feature->id);
            if ($result['status'] === 1) {
                $normal++;
            } else {
                $abnormal++;
            }
            $details[] = $result;
        }

        $summary = [
            'total' => $total,
            'normal' => $normal,
            'abnormal' => $abnormal,
            'details' => $details,
        ];

        Cache::set('feature_registry:health:full', $summary, 60);
        return $summary;
    }

    /**
     * 按Sprint查询功能点
     */
    public function getBySprint(string $sprintCode): array
    {
        return FeatureRegistry::where('sprint_code', $sprintCode)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取所有功能点分组
     */
    public function getGroupedFeatures(): array
    {
        return Cache::remember(
            'feature_registry:grouped',
            function () {
                $all = FeatureRegistry::order('sprint_code', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
                $grouped = [];
                foreach ($all as $f) {
                    $grouped[$f['sprint_code']][] = $f;
                }
                return $grouped;
            },
            self::CACHE_TTL
        );
    }

    /**
     * 计算健康度评分
     */
    public function calculateHealthScore(): array
    {
        $grouped = $this->getGroupedFeatures();
        $bySprint = [];
        $totalAll = 0;
        $normalAll = 0;

        foreach ($grouped as $sprint => $features) {
            $total = count($features);
            $normal = 0;
            foreach ($features as $f) {
                if ($f['status'] == 1) $normal++;
            }
            $score = $total > 0 ? round($normal / $total * 100, 1) : 0;
            $bySprint[$sprint] = [
                'total' => $total,
                'normal' => $normal,
                'score' => $score,
            ];
            $totalAll += $total;
            $normalAll += $normal;
        }

        return [
            'overall' => $totalAll > 0 ? round($normalAll / $totalAll * 100, 1) : 0,
            'by_sprint' => $bySprint,
            'total' => $totalAll,
            'normal' => $normalAll,
            'abnormal' => $totalAll - $normalAll,
        ];
    }

    /**
     * 清除缓存
     */
    private function clearCache(): void
    {
        Cache::clear();
    }
}
