<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-2: 灰度发布引擎
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 灰度发布引擎 - V2.9.39 SYS-ROBUST-2
 * 百分比/分群/IP/时间窗口 + 监控 + 回滚
 */
class GrayscaleReleaseService
{
    protected const CACHE_TAG = 'grayscale';
    protected const CACHE_TTL = 60;

    protected string $table = 'grayscale_release';

    // 策略类型
    public const STRATEGY_PERCENTAGE = 'percentage';
    public const STRATEGY_USER_GROUP = 'user_group';
    public const STRATEGY_IP_LIST    = 'ip_list';
    public const STRATEGY_TIME_WINDOW= 'time_window';
    public const STRATEGY_WHITELIST  = 'whitelist';

    // 状态
    public const STATUS_DRAFT     = 0;
    public const STATUS_ACTIVE    = 1;
    public const STATUS_PAUSED    = 2;
    public const STATUS_ROLLED_BACK = 3;
    public const STATUS_COMPLETED = 4;

    /**
     * 创建灰度发布计划
     */
    public function create(array $data): array
    {
        $id = Db::name($this->table)->insertGetId([
            'name'          => $data['name'] ?? '',
            'description'   => $data['description'] ?? '',
            'feature_key'   => $data['feature_key'] ?? '',
            'strategy'      => $data['strategy'] ?? self::STRATEGY_PERCENTAGE,
            'config'        => json_encode($data['config'] ?? [], JSON_UNESCAPED_UNICODE),
            'target_url'    => $data['target_url'] ?? '',
            'fallback_url'  => $data['fallback_url'] ?? '',
            'status'        => self::STATUS_DRAFT,
            'creator_id'    => $data['creator_id'] ?? 0,
            'start_time'    => $data['start_time'] ?? null,
            'end_time'      => $data['end_time'] ?? null,
            'create_time'   => time(),
            'update_time'   => time(),
        ]);

        Cache::clear();
        Log::info('[Grayscale] 创建灰度计划', ['id' => $id, 'name' => $data['name'] ?? '']);

        return ['id' => $id];
    }

    /**
     * 启动灰度发布
     */
    public function activate(int $id): array
    {
        $plan = Db::name($this->table)->find($id);
        if (!$plan) {
            return ['success' => false, 'msg' => '计划不存在'];
        }

        if ($plan['status'] !== self::STATUS_DRAFT && $plan['status'] !== self::STATUS_PAUSED) {
            return ['success' => false, 'msg' => '当前状态无法启动'];
        }

        Db::name($this->table)->where('id', $id)->update([
            'status'      => self::STATUS_ACTIVE,
            'start_time'  => $plan['start_time'] ?: date('Y-m-d H:i:s'),
            'update_time' => time(),
        ]);

        Cache::clear();
        Log::info('[Grayscale] 灰度计划已启动', ['id' => $id]);

        return ['success' => true];
    }

    /**
     * 暂停灰度发布
     */
    public function pause(int $id): array
    {
        $result = Db::name($this->table)->where('id', $id)
            ->where('status', self::STATUS_ACTIVE)
            ->update(['status' => self::STATUS_PAUSED, 'update_time' => time()]);

        Cache::clear();

        return ['success' => $result > 0];
    }

    /**
     * 回滚灰度发布
     */
    public function rollback(int $id, string $reason = ''): array
    {
        $plan = Db::name($this->table)->find($id);
        if (!$plan) {
            return ['success' => false, 'msg' => '计划不存在'];
        }

        Db::name($this->table)->where('id', $id)->update([
            'status'        => self::STATUS_ROLLED_BACK,
            'rollback_reason' => $reason,
            'rollback_time' => date('Y-m-d H:i:s'),
            'update_time'   => time(),
        ]);

        Cache::clear();
        Log::warning('[Grayscale] 灰度回滚', ['id' => $id, 'reason' => $reason]);

        return ['success' => true];
    }

    /**
     * 完成灰度发布（全量上线）
     */
    public function complete(int $id): array
    {
        $result = Db::name($this->table)->where('id', $id)
            ->whereIn('status', [self::STATUS_ACTIVE])
            ->update([
                'status'      => self::STATUS_COMPLETED,
                'end_time'    => date('Y-m-d H:i:s'),
                'update_time' => time(),
            ]);

        Cache::clear();
        Log::info('[Grayscale] 灰度全量上线', ['id' => $id]);

        return ['success' => $result > 0];
    }

    /**
     * 检查用户是否在灰度范围内
     */
    public function isInGrayscale(string $featureKey, ?int $userId = null, ?string $ip = null): bool
    {
        $cacheKey = 'grayscale_check_' . $featureKey . '_' . ($userId ?? 0) . '_' . ($ip ?? '');

        return Cache::remember($cacheKey, function () use ($featureKey, $userId, $ip) {
            $plan = Db::name($this->table)
                ->where('feature_key', $featureKey)
                ->where('status', self::STATUS_ACTIVE)
                ->find();

            if (!$plan) {
                return false;
            }

            // 检查时间窗口
            if (!empty($plan['start_time']) && time() < strtotime($plan['start_time'])) {
                return false;
            }
            if (!empty($plan['end_time']) && time() > strtotime($plan['end_time'])) {
                return false;
            }

            $config = json_decode($plan['config'] ?: '{}', true);

            return match ($plan['strategy']) {
                self::STRATEGY_PERCENTAGE  => $this->checkPercentage($userId ?? 0, $config),
                self::STRATEGY_USER_GROUP  => $this->checkUserGroup($userId ?? 0, $config),
                self::STRATEGY_IP_LIST     => $this->checkIpList($ip ?? '', $config),
                self::STRATEGY_TIME_WINDOW => $this->checkTimeWindow($config),
                self::STRATEGY_WHITELIST   => $this->checkWhitelist($userId ?? 0, $config),
                default                    => false,
            };
        }, self::CACHE_TTL);
    }

    /**
     * 百分比策略
     */
    protected function checkPercentage(int $userId, array $config): bool
    {
        $percentage = (int) ($config['percentage'] ?? 0);
        if ($percentage <= 0) {
            return false;
        }
        if ($percentage >= 100) {
            return true;
        }
        // 基于用户ID的稳定hash，确保同一用户每次结果一致
        $hash = crc32('grayscale_' . $userId);
        return ($hash % 100) < $percentage;
    }

    /**
     * 用户分群策略
     */
    protected function checkUserGroup(int $userId, array $config): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $groupIds = $config['group_ids'] ?? [];
        if (empty($groupIds)) {
            return false;
        }

        try {
            $userGroupIds = Db::name('member_segment_member')
                ->where('member_id', $userId)
                ->whereIn('segment_id', $groupIds)
                ->count();
            return $userGroupIds > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * IP列表策略
     */
    protected function checkIpList(string $ip, array $config): bool
    {
        if (empty($ip)) {
            return false;
        }
        $allowedIps = $config['ip_list'] ?? [];
        $allowedRanges = $config['ip_ranges'] ?? [];

        if (in_array($ip, $allowedIps, true)) {
            return true;
        }

        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 时间窗口策略
     */
    protected function checkTimeWindow(array $config): bool
    {
        $start = $config['window_start'] ?? null;
        $end = $config['window_end'] ?? null;
        $now = date('H:i:s');

        if ($start && $end) {
            return $now >= $start && $now <= $end;
        }
        return false;
    }

    /**
     * 白名单策略
     */
    protected function checkWhitelist(int $userId, array $config): bool
    {
        $whitelist = $config['user_ids'] ?? [];
        return in_array($userId, $whitelist, true);
    }

    /**
     * 检查IP是否在范围内
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $mask] = explode('/', $range);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int) $mask);
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }
        return $ip === $range;
    }

    /**
     * 获取灰度计划列表
     */
    public function getList(array $params = []): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $status = $params['status'] ?? null;

        $query = Db::name($this->table);
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 获取灰度计划详情
     */
    public function getDetail(int $id): ?array
    {
        $plan = Db::name($this->table)->find($id);
        if ($plan) {
            $plan['config'] = json_decode($plan['config'] ?: '{}', true);
            $plan['metrics'] = $this->getMetrics($id);
        }
        return $plan;
    }

    /**
     * 获取灰度监控指标
     */
    public function getMetrics(int $planId): array
    {
        $cacheKey = 'grayscale_metrics_' . $planId;

        return Cache::remember($cacheKey, function () use ($planId) {
            try {
                $total = Db::name('grayscale_log')->where('plan_id', $planId)->count();
                $inGrayscale = Db::name('grayscale_log')->where('plan_id', $planId)->where('in_grayscale', 1)->count();
                $errors = Db::name('grayscale_log')->where('plan_id', $planId)->where('has_error', 1)->count();

                $rate = $total > 0 ? round($inGrayscale / $total * 100, 2) : 0;
                $errorRate = $inGrayscale > 0 ? round($errors / $inGrayscale * 100, 2) : 0;

                return [
                    'total_requests'   => $total,
                    'grayscale_count'  => $inGrayscale,
                    'error_count'      => $errors,
                    'grayscale_rate'   => $rate,
                    'error_rate'       => $errorRate,
                ];
            } catch (\Throwable) {
                return [
                    'total_requests'  => 0,
                    'grayscale_count' => 0,
                    'error_count'     => 0,
                    'grayscale_rate'  => 0,
                    'error_rate'      => 0,
                ];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 记录灰度访问日志
     */
    public function logAccess(int $planId, ?int $userId, string $ip, bool $inGrayscale, bool $hasError = false): void
    {
        try {
            Db::name('grayscale_log')->insert([
                'plan_id'      => $planId,
                'user_id'      => $userId ?? 0,
                'ip_address'   => $ip,
                'in_grayscale' => $inGrayscale ? 1 : 0,
                'has_error'    => $hasError ? 1 : 0,
                'create_time'  => time(),
            ]);
        } catch (\Throwable) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 更新灰度计划
     */
    public function update(int $id, array $data): bool
    {
        $update = [];
        foreach (['name', 'description', 'feature_key', 'strategy', 'target_url', 'fallback_url', 'start_time', 'end_time'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        if (isset($data['config'])) {
            $update['config'] = json_encode($data['config'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($update)) {
            return false;
        }

        $update['update_time'] = time();
        $result = Db::name($this->table)->where('id', $id)->update($update);
        Cache::clear();

        return $result > 0;
    }

    /**
     * 删除灰度计划（仅草稿/已完成/已回滚可删除）
     */
    public function delete(int $id): array
    {
        $plan = Db::name($this->table)->find($id);
        if (!$plan) {
            return ['success' => false, 'msg' => '计划不存在'];
        }

        if (in_array($plan['status'], [self::STATUS_ACTIVE, self::STATUS_PAUSED], true)) {
            return ['success' => false, 'msg' => '运行中的计划无法删除'];
        }

        Db::name($this->table)->delete($id);
        Cache::clear();

        return ['success' => true];
    }
}
