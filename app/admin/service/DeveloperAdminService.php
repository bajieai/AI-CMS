<?php
declare(strict_types=1);

namespace app\admin\service;

use app\common\model\Developer;
use think\facade\Cache;

/**
 * 开发者管理服务 - V2.9.29 Sprint D-1
 */
class DeveloperAdminService
{
    private const CACHE_TAG = 'developer';

    public function getList(int $page, int $pageSize, array $filter = []): array
    {
        $query = Developer::order('id', 'desc');
        if (isset($filter['status'])) $query->where('status', $filter['status']);
        if (!empty($filter['keyword'])) {
            $kw = $filter['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('real_name', "%{$kw}%")
                  ->whereOr('contact_email', 'like', "%{$kw}%")
                  ->whereOr('contact_phone', 'like', "%{$kw}%");
            });
        }
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function getById(int $id): ?Developer
    {
        return Developer::find($id);
    }

    public function audit(int $id, int $status, string $remark = ''): bool
    {
        $dev = Developer::find($id);
        if (!$dev) return false;
        $dev->status = $status;
        $dev->audit_remark = $remark;
        $result = $dev->save();
        Cache::tag(self::CACHE_TAG)->clear();
        return (bool) $result;
    }

    public function disable(int $id): bool
    {
        return (bool) Developer::where('id', $id)->update(['status' => 3]);
    }

    public function enable(int $id): bool
    {
        return (bool) Developer::where('id', $id)->update(['status' => 1]);
    }

    public function getStats(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('dev_stats', function () {
            return [
                'total' => Developer::count(),
                'pending' => Developer::where('status', 0)->count(),
                'approved' => Developer::where('status', 1)->count(),
                'disabled' => Developer::where('status', 3)->count(),
            ];
        }, 300);
    }
}
