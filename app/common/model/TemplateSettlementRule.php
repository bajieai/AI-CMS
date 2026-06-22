<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 结算规则模型 — V2.9.28 M-7
 */
class TemplateSettlementRule extends Model
{
    protected $name = 'template_settlement_rule';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const CYCLE_MONTHLY = 1;   // 月结
    const CYCLE_QUARTERLY = 2; // 季结
    const CYCLE_YEARLY = 3;    // 年结

    protected $type = [
        'developer_id' => 'integer',
        'commission_rate' => 'float',
        'min_withdraw' => 'float',
        'settle_cycle' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 获取开发者的结算规则（回退到默认）
     */
    public static function getForDeveloper(int $developerId): array
    {
        $rule = self::where('developer_id', $developerId)->where('status', 1)->find();
        if ($rule) {
            return $rule->toArray();
        }
        // 返回系统默认值
        return [
            'developer_id' => $developerId,
            'commission_rate' => (float)config('template_store.commission_rate', 30),
            'min_withdraw' => (float)config('template_store.min_withdraw', 100),
            'settle_cycle' => self::CYCLE_MONTHLY,
        ];
    }
}
