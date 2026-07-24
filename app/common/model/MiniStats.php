<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 移动端统计模型
 * V2.9.37 MINI-FULL-5
 */
class MiniStats extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;

    // 按日期范围统计
    public function scopeDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('stats_date', [$start, $end]);
    }

    // 按类型统计
    public function scopeType($query, string $type)
    {
        return $query->where('stats_type', $type);
    }

    // 按平台统计
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
