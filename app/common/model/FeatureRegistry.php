<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 功能点注册模型 — V2.9.30 Q-2
 */
class FeatureRegistry extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 按Sprint查询功能点
     */
    public function getBySprint(string $sprintCode): array
    {
        return Cache::remember(
            'feature_registry:sprint:' . $sprintCode,
            function () use ($sprintCode) {
                return self::where('sprint_code', $sprintCode)
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
            },
            600
        );
    }

    /**
     * 获取所有功能点
     */
    public function getAllFeatures(): array
    {
        return Cache::remember(
            'feature_registry:all',
            function () {
                return self::order('sprint_code', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
            },
            600
        );
    }
}
