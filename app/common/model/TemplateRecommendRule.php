<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

/**
 * 模板推荐规则模型 — V2.9.26 P-1
 *
 * 支持规则类型：manual(手动置顶), ai(AI推荐), category(分类热门),
 *              festival(节日特推), new_release(新品首发)
 */
class TemplateRecommendRule extends Model
{
    protected $name = 'template_recommend_rule';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'template_ids' => 'json',
        'conditions'   => 'json',
    ];

    public const CACHE_TAG = 'template_recommend';

    /**
     * 获取生效中的规则列表
     */
    public static function getActiveRules(int $limit = 20): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('active_rules_' . $limit, function () use ($limit) {
            $now = date('Y-m-d H:i:s');
            return self::where('status', 1)
                ->where(function ($query) use ($now) {
                    $query->whereNull('start_time')->whereOr('start_time', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_time')->whereOr('end_time', '>=', $now);
                })
                ->order('priority', 'desc')
                ->order('sort', 'asc')
                ->limit($limit)
                ->select()
                ->toArray();
        }, 300);
    }

    /**
     * 根据规则类型筛选
     */
    public static function getByType(string $ruleType): array
    {
        return self::where('rule_type', $ruleType)
            ->where('status', 1)
            ->order('priority', 'desc')
            ->select()
            ->toArray();
    }
}
