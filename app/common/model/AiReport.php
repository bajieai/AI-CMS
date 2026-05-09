<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI分析报告模型 - V2.9.1 M9
 * 对应表: i8j_ai_report
 */
class AiReport extends Model
{
    // 表名（不含前缀）
    protected $name = 'ai_report';

    // 自动时间戳（使用int时间戳）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'status'       => 'integer',
        'period_start' => 'integer',
        'period_end'   => 'integer',
        'raw_data'     => 'json',
        'findings'     => 'json',
        'anomalies'    => 'json',
        'recommendations' => 'json',
        'sections'     => 'json',
        'create_time'  => 'integer',
        'update_time'  => 'integer',
    ];

    // 状态常量
    public const STATUS_GENERATING = 0;
    public const STATUS_COMPLETED  = 1;
    public const STATUS_PUBLISHED  = 2;
    public const STATUS_FAILED     = 3;

    protected static array $statusMap = [
        self::STATUS_GENERATING => '生成中',
        self::STATUS_COMPLETED  => '已完成',
        self::STATUS_PUBLISHED  => '已发布',
        self::STATUS_FAILED     => '失败',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return self::$statusMap[$data['status'] ?? 0] ?? '未知';
    }

    /**
     * 按类型和时间段查询
     */
    public static function getByPeriod(string $type, int $start, int $end): ?self
    {
        return self::where('type', $type)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->find();
    }

    /**
     * 获取最新报告
     */
    public static function getLatest(string $type = 'daily', int $limit = 10): array
    {
        return self::where('type', $type)
            ->where('status', self::STATUS_COMPLETED)
            ->order('period_end', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
