<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.25 N-2: 模板日统计汇总模型
 */
class TemplateDailyStats extends Model
{
    protected $name = 'template_daily_stats';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 获取或创建某天某模板的统计记录
     */
    public static function getOrCreate(int $templateId, string $date): self
    {
        $record = self::where('template_id', $templateId)->where('stats_date', $date)->find();
        if (!$record) {
            $record = self::create([
                'template_id' => $templateId,
                'stats_date' => $date,
                'create_time' => time(),
            ]);
        }
        return $record;
    }

    /**
     * 增量更新统计
     */
    public static function increment(int $templateId, string $date, string $field, int $amount = 1): void
    {
        $record = self::getOrCreate($templateId, $date);
        $record->$field = ($record->$field ?? 0) + $amount;
        $record->save();
    }
}
