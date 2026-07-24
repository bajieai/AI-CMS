<?php
declare(strict_types=1);

namespace app\common\service\operation;

use think\facade\Db;
use think\facade\Cache;

class OperationTaskService
{
    private const CACHE_TAG = 'operation_task';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_OVERDUE = 'overdue';

    public function create(array $data): array
    {
        $id = Db::name('operation_task')->insertGetId(array_merge($data, ['status' => self::STATUS_PENDING, 'create_time' => time()]));
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function assign(int $taskId, int $memberId): array
    {
        Db::name('operation_task')->where('id', $taskId)->update(['assignee_id' => $memberId, 'status' => self::STATUS_IN_PROGRESS, 'update_time' => time()]);
        Cache::clear();
        return ['success' => true];
    }

    public function updateStatus(int $taskId, string $status): array
    {
        Db::name('operation_task')->where('id', $taskId)->update(['status' => $status, 'update_time' => time()]);
        Cache::clear();
        return ['success' => true];
    }

    public function getList(array $filters = []): array
    {
        $query = Db::name('operation_task')->order('create_time', 'desc');
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['assignee_id'])) $query->where('assignee_id', $filters['assignee_id']);
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        $total = $query->count();
        $page = max(1, (int)($filters['page'] ?? 1));
        $list = $query->page($page, 20)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            $total = Db::name('operation_task')->count();
            $completed = Db::name('operation_task')->where('status', self::STATUS_COMPLETED)->count();
            $onTime = Db::name('operation_task')->where('status', self::STATUS_COMPLETED)->where('complete_time', '<=', Db::raw('deadline'))->count();
            $typeDist = Db::name('operation_task')->field('type, COUNT(*) as count')->group('type')->select()->toArray();
            return ['total' => $total, 'completed' => $completed, 'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0, 'on_time_rate' => $completed > 0 ? round($onTime / $completed * 100, 1) : 0, 'type_distribution' => $typeDist];
        }, 300);
    }
}
