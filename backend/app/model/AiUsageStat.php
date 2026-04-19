<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * AI使用统计模型
 */
class AiUsageStat extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_ai_usage_stats';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = false;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 时间戳格式
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 类型转换
     */
    protected $type = [
        'user_id' => 'integer',
        'model_id' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'float',
        'request_count' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 模型关联
     */
    public function model()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    /**
     * 记录使用
     */
    public static function record(
        int $userId,
        int $modelId,
        int $inputTokens,
        int $outputTokens,
        float $cost = 0
    ): self {
        $today = date('Y-m-d');
        
        // 查找今日记录
        $stat = self::where('user_id', '=', $userId)
            ->where('model_id', '=', $modelId)
            ->where('date', '=', $today)
            ->find();
        
        if ($stat) {
            // 更新现有记录
            $stat->input_tokens += $inputTokens;
            $stat->output_tokens += $outputTokens;
            $stat->total_tokens += ($inputTokens + $outputTokens);
            $stat->cost += $cost;
            $stat->request_count += 1;
            $stat->save();
        } else {
            // 创建新记录
            $stat = self::create([
                'user_id' => $userId,
                'model_id' => $modelId,
                'date' => $today,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $inputTokens + $outputTokens,
                'cost' => $cost,
                'request_count' => 1,
            ]);
        }
        
        return $stat;
    }

    /**
     * 获取用户今日统计
     */
    public static function getUserTodayStats(int $userId): array
    {
        $today = date('Y-m-d');
        
        $stats = self::where('user_id', '=', $userId)
            ->where('date', '=', $today)
            ->select();
        
        $total = [
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'cost' => 0,
            'request_count' => 0,
        ];
        
        foreach ($stats as $stat) {
            $total['input_tokens'] += $stat->input_tokens;
            $total['output_tokens'] += $stat->output_tokens;
            $total['total_tokens'] += $stat->total_tokens;
            $total['cost'] += $stat->cost;
            $total['request_count'] += $stat->request_count;
        }
        
        return $total;
    }

    /**
     * 获取用户指定日期范围统计
     */
    public static function getUserStatsByDateRange(
        int $userId,
        string $startDate,
        string $endDate
    ): array {
        return self::where('user_id', '=', $userId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->select()
            ->toArray();
    }

    /**
     * 获取每日趋势
     */
    public static function getDailyTrend(int $userId, int $days = 7): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');
        
        $stats = Db::query(
            "SELECT date, SUM(input_tokens) as input_tokens, 
                    SUM(output_tokens) as output_tokens, 
                    SUM(total_tokens) as total_tokens,
                    SUM(cost) as cost,
                    SUM(request_count) as request_count
             FROM i8j_aicms_ai_usage_stats
             WHERE user_id = ? AND date >= ? AND date <= ?
             GROUP BY date
             ORDER BY date ASC",
            [$userId, $startDate, $endDate]
        );
        
        return $stats;
    }

    /**
     * 获取全局统计
     */
    public static function getGlobalStats(string $startDate = '', string $endDate = ''): array
    {
        $query = Db::name('ai_usage_stats');
        
        if ($startDate) {
            $query->where('stat_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('stat_date', '<=', $endDate);
        }
        
        // 分别查询各聚合值，避免连续赋值覆盖
        $totalInputTokens = (clone $query)->sum('total_input_tokens') ?: 0;
        $totalOutputTokens = (clone $query)->sum('total_output_tokens') ?: 0;
        $totalCost = (clone $query)->sum('total_cost') ?: 0;
        $totalRequests = (clone $query)->sum('task_count') ?: 0;
        
        return [
            'total_input_tokens' => (int)$totalInputTokens,
            'total_output_tokens' => (int)$totalOutputTokens,
            'total_tokens' => (int)$totalInputTokens + (int)$totalOutputTokens,
            'total_cost' => (float)$totalCost,
            'total_requests' => (int)$totalRequests,
        ];
    }

    /**
     * 获取模型使用排行
     */
    public static function getModelRanking(string $startDate = '', string $endDate = '', int $limit = 10): array
    {
        $query = Db::table('i8j_aicms_ai_usage_stats')
            ->field('model_id, SUM(total_tokens) as total_tokens, SUM(request_count) as request_count')
            ->group('model_id')
            ->order('total_tokens', 'desc')
            ->limit($limit);
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        return $query->select()->toArray();
    }
}
