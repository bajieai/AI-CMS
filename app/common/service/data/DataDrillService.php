<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 数据钻取服务 - V2.9.39 DATA-DEEP-4
 * 功能：时间 / 地域 / 分类 / 渠道 / 用户维度下钻
 */
class DataDrillService
{
    private const CACHE_TAG = 'data_drill';
    private const CACHE_TTL = 300;

    public const DIMENSION_TIME     = 'time';
    public const DIMENSION_REGION   = 'region';
    public const DIMENSION_CATEGORY = 'category';
    public const DIMENSION_CHANNEL  = 'channel';
    public const DIMENSION_USER     = 'user';

    public const GRANULARITY_HOUR  = 'hour';
    public const GRANULARITY_DAY   = 'day';
    public const GRANULARITY_WEEK  = 'week';
    public const GRANULARITY_MONTH = 'month';

    /**
     * 执行数据钻取
     */
    public function drill(string $dimension, array $params = []): array
    {
        $startTime = (int) ($params['start_time'] ?? strtotime('-30 days'));
        $endTime   = (int) ($params['end_time'] ?? time());

        $cacheKey = 'drill_' . md5(json_encode([$dimension, $params]));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = match ($dimension) {
            self::DIMENSION_TIME     => $this->drillByTime($startTime, $endTime, $params),
            self::DIMENSION_REGION   => $this->drillByRegion($startTime, $endTime, $params),
            self::DIMENSION_CATEGORY => $this->drillByCategory($startTime, $endTime, $params),
            self::DIMENSION_CHANNEL  => $this->drillByChannel($startTime, $endTime, $params),
            self::DIMENSION_USER     => $this->drillByUser($startTime, $endTime, $params),
            default                  => ['success' => false, 'msg' => "未知维度: {$dimension}"],
        };

        $result['dimension'] = $dimension;
        $result['date_range'] = [
            'start' => date('Y-m-d', $startTime),
            'end'   => date('Y-m-d', $endTime),
        ];

        Cache::set($cacheKey, $result, self::CACHE_TTL);
        return $result;
    }

    /**
     * 按时间维度钻取
     */
    public function drillByTime(int $startTime, int $endTime, array $params = []): array
    {
        $granularity = $params['granularity'] ?? self::GRANULARITY_DAY;
        $sourceTable = $params['source_table'] ?? 'visit_log';
        $timeField   = $params['time_field'] ?? 'visit_time';
        $metric      = $params['metric'] ?? 'pv';

        $dateFormat = match ($granularity) {
            self::GRANULARITY_HOUR  => '%Y-%m-%d %H:00',
            self::GRANULARITY_DAY   => '%Y-%m-%d',
            self::GRANULARITY_WEEK  => '%Y-%u',
            self::GRANULARITY_MONTH => '%Y-%m',
            default                 => '%Y-%m-%d',
        };

        try {
            $metricExpr = match ($metric) {
                'pv'    => 'COUNT(*) as value',
                'uv'    => 'COUNT(DISTINCT visitor_id) as value',
                'views' => 'SUM(views) as value',
                default => 'COUNT(*) as value',
            };

            $rows = Db::name($sourceTable)
                ->field([
                    "FROM_UNIXTIME({$timeField}, '{$dateFormat}') as label",
                    $metricExpr,
                ])
                ->where($timeField, '>=', $startTime)
                ->where($timeField, '<=', $endTime)
                ->group('label')
                ->order('label', 'asc')
                ->select()
                ->toArray();

            $total = array_sum(array_column($rows, 'value'));
            $avg   = count($rows) > 0 ? $total / count($rows) : 0;

            $prevStart = $startTime - ($endTime - $startTime);
            $prevRow = Db::name($sourceTable)
                ->field([$metricExpr])
                ->where($timeField, '>=', $prevStart)
                ->where($timeField, '<', $startTime)
                ->find();
            $prevTotal = (int) ($prevRow['value'] ?? 0);
            $changePct = $prevTotal > 0 ? round(($total - $prevTotal) / $prevTotal * 100, 1) : 0;

            return [
                'success'     => true,
                'granularity' => $granularity,
                'data'        => $rows,
                'summary'     => [
                    'total'      => $total,
                    'avg'        => round($avg, 2),
                    'prev_total' => $prevTotal,
                    'change_pct' => $changePct,
                    'trend'      => $changePct > 0 ? 'up' : ($changePct < 0 ? 'down' : 'flat'),
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("时间维度钻取失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 按地域维度钻取
     */
    public function drillByRegion(int $startTime, int $endTime, array $params = []): array
    {
        $sourceTable = $params['source_table'] ?? 'visit_log';
        $timeField   = $params['time_field'] ?? 'visit_time';
        $metric      = $params['metric'] ?? 'pv';

        try {
            $metricExpr = match ($metric) {
                'pv' => 'COUNT(*) as value',
                'uv' => 'COUNT(DISTINCT visitor_id) as value',
                default => 'COUNT(*) as value',
            };

            $hasRegion = $this->tableHasColumn($sourceTable, 'region');
            $regionField = $params['region_field'] ?? 'region';

            if (!$hasRegion) {
                $hasIp = $this->tableHasColumn($sourceTable, 'ip');
                if (!$hasIp) {
                    return ['success' => false, 'msg' => '数据表无地域字段，无法按地域钻取', 'data' => []];
                }
                $regionField = 'ip';
            }

            $rows = Db::name($sourceTable)
                ->field(["{$regionField} as label", $metricExpr])
                ->where($timeField, '>=', $startTime)
                ->where($timeField, '<=', $endTime)
                ->group($regionField)
                ->order('value', 'DESC')
                ->limit($params['limit'] ?? 20)
                ->select()
                ->toArray();

            $total = array_sum(array_column($rows, 'value'));
            foreach ($rows as &$row) {
                $row['percentage'] = $total > 0 ? round($row['value'] / $total * 100, 1) : 0;
            }

            return [
                'success'      => true,
                'region_field' => $regionField,
                'data'         => $rows,
                'summary'      => [
                    'total'         => $total,
                    'regions'       => count($rows),
                    'top_region'    => $rows[0]['label'] ?? '',
                    'top_value'     => $rows[0]['value'] ?? 0,
                    'concentration' => !empty($rows) && $total > 0
                        ? round($rows[0]['value'] / $total * 100, 1) : 0,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("地域维度钻取失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 按分类维度钻取
     */
    public function drillByCategory(int $startTime, int $endTime, array $params = []): array
    {
        $metric = $params['metric'] ?? 'views';

        try {
            $metricExpr = match ($metric) {
                'views'    => 'SUM(c.views) as value',
                'count'    => 'COUNT(*) as value',
                default    => 'SUM(c.views) as value',
            };

            $rows = Db::name('content')
                ->alias('c')
                ->field(['c.cate_id as label', $metricExpr])
                ->where('c.create_time', '>=', $startTime)
                ->where('c.create_time', '<=', $endTime)
                ->group('c.cate_id')
                ->order('value', 'DESC')
                ->limit($params['limit'] ?? 20)
                ->select()
                ->toArray();

            $cateNames = Db::name('cate')->column('name', 'id');
            foreach ($rows as &$row) {
                $row['name'] = $cateNames[$row['label']] ?? '未分类';
            }

            $total = array_sum(array_column($rows, 'value'));
            foreach ($rows as &$row) {
                $row['percentage'] = $total > 0 ? round($row['value'] / $total * 100, 1) : 0;
            }

            return [
                'success' => true,
                'data'    => $rows,
                'summary' => [
                    'total'        => $total,
                    'categories'   => count($rows),
                    'top_category' => $rows[0]['name'] ?? '',
                    'top_value'    => $rows[0]['value'] ?? 0,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("分类维度钻取失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 按渠道维度钻取
     */
    public function drillByChannel(int $startTime, int $endTime, array $params = []): array
    {
        $sourceTable = $params['source_table'] ?? 'visit_log';
        $timeField   = $params['time_field'] ?? 'visit_time';
        $metric      = $params['metric'] ?? 'pv';

        try {
            $metricExpr = match ($metric) {
                'pv' => 'COUNT(*) as value',
                'uv' => 'COUNT(DISTINCT visitor_id) as value',
                default => 'COUNT(*) as value',
            };

            $channelField = null;
            foreach (['source', 'referer_host', 'channel', 'device_type'] as $field) {
                if ($this->tableHasColumn($sourceTable, $field)) {
                    $channelField = $field;
                    break;
                }
            }

            if (!$channelField) {
                return ['success' => false, 'msg' => '数据表无渠道字段，无法按渠道钻取', 'data' => []];
            }

            $rows = Db::name($sourceTable)
                ->field(["{$channelField} as label", $metricExpr])
                ->where($timeField, '>=', $startTime)
                ->where($timeField, '<=', $endTime)
                ->where($channelField, '<>', '')
                ->group($channelField)
                ->order('value', 'DESC')
                ->limit($params['limit'] ?? 20)
                ->select()
                ->toArray();

            $total = array_sum(array_column($rows, 'value'));
            foreach ($rows as &$row) {
                $row['percentage'] = $total > 0 ? round($row['value'] / $total * 100, 1) : 0;
            }

            return [
                'success'       => true,
                'channel_field' => $channelField,
                'data'           => $rows,
                'summary'        => [
                    'total'       => $total,
                    'channels'    => count($rows),
                    'top_channel' => $rows[0]['label'] ?? '',
                    'top_value'   => $rows[0]['value'] ?? 0,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("渠道维度钻取失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 按用户维度钻取
     */
    public function drillByUser(int $startTime, int $endTime, array $params = []): array
    {
        $metric = $params['metric'] ?? 'activity';

        try {
            switch ($metric) {
                case 'activity':
                    $rows = Db::name('visit_log')
                        ->field(['visitor_id as label', 'COUNT(*) as value'])
                        ->where('visit_time', '>=', $startTime)
                        ->where('visit_time', '<=', $endTime)
                        ->group('visitor_id')
                        ->order('value', 'DESC')
                        ->limit($params['limit'] ?? 20)
                        ->select()
                        ->toArray();
                    break;

                case 'level':
                    $rows = Db::name('member')
                        ->alias('m')
                        ->join('member_level l', 'm.level_id = l.id', 'LEFT')
                        ->field(['IFNULL(l.level_name, "普通用户") as label', 'COUNT(*) as value'])
                        ->where('m.create_time', '>=', $startTime)
                        ->where('m.create_time', '<=', $endTime)
                        ->group('m.level_id')
                        ->order('value', 'DESC')
                        ->select()
                        ->toArray();
                    break;

                case 'new_vs_returning':
                    $newUsers = Db::name('member')
                        ->whereBetween('create_time', [$startTime, $endTime])
                        ->count();
                    $allVisitors = Db::name('visit_log')
                        ->where('visit_time', '>=', $startTime)
                        ->where('visit_time', '<=', $endTime)
                        ->distinct(true)
                        ->field('visitor_id')
                        ->count('visitor_id');
                    $returningUsers = max(0, $allVisitors - $newUsers);
                    $rows = [
                        ['label' => '新用户', 'value' => $newUsers],
                        ['label' => '回访用户', 'value' => $returningUsers],
                    ];
                    break;

                default:
                    $rows = Db::name('visit_log')
                        ->field(['visitor_id as label', 'COUNT(*) as value'])
                        ->where('visit_time', '>=', $startTime)
                        ->where('visit_time', '<=', $endTime)
                        ->group('visitor_id')
                        ->order('value', 'DESC')
                        ->limit($params['limit'] ?? 20)
                        ->select()
                        ->toArray();
            }

            $total = array_sum(array_column($rows, 'value'));
            foreach ($rows as &$row) {
                $row['percentage'] = $total > 0 ? round($row['value'] / $total * 100, 1) : 0;
            }

            return [
                'success' => true,
                'metric'  => $metric,
                'data'    => $rows,
                'summary' => [
                    'total'     => $total,
                    'count'     => count($rows),
                    'top_user'  => $rows[0]['label'] ?? '',
                    'top_value' => $rows[0]['value'] ?? 0,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("用户维度钻取失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'data' => []];
        }
    }

    // ========================================================================
    // 工具方法
    // ========================================================================

    /**
     * 检查表是否有指定列（带缓存）
     */
    private function tableHasColumn(string $table, string $column): bool
    {
        $cacheKey = "col_exists_{$table}_{$column}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (bool) $cached;
        }

        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $fullTable = $prefix . $table;
            $result = Db::query("SHOW COLUMNS FROM `{$fullTable}` LIKE ?", [$column]);
            $exists = !empty($result);
            Cache::set($cacheKey, $exists ? 1 : 0, 3600);
            return $exists;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取表前缀
     */
    private function getPrefix(): string
    {
        return Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
    }

    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
    }
}
