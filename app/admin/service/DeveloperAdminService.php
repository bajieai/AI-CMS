<?php
declare(strict_types=1);
namespace app\admin\service;

use app\common\model\Developer;
use app\common\model\User;
use think\facade\Db;

/**
 * 开发者管理服务 (V2.9.29 D-1)
 */
class DeveloperAdminService
{
    public function getList(int $page = 1, int $limit = 15, array $filter = []): array
    {
        $query = Developer::order('id', 'desc');
        if (!empty($filter['status'])) $query->where('status', $filter['status']);
        if (!empty($filter['level'])) $query->where('level', $filter['level']);
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function approve(int $id, string $remark = ''): bool
    {
        $dev = Developer::find($id);
        if (!$dev) return false;
        $dev->status = Developer::STATUS_APPROVED;
        $dev->audit_remark = $remark;
        return $dev->save();
    }

    public function reject(int $id, string $remark = ''): bool
    {
        $dev = Developer::find($id);
        if (!$dev) return false;
        $dev->status = Developer::STATUS_REJECTED;
        $dev->audit_remark = $remark;
        return $dev->save();
    }

    public function disable(int $id): bool
    {
        $dev = Developer::find($id);
        if (!$dev) return false;
        $dev->status = Developer::STATUS_DISABLED;
        return $dev->save();
    }

    public function updateLevel(int $id, int $level): bool
    {
        $dev = Developer::find($id);
        if (!$dev) return false;
        $dev->level = $level;
        return $dev->save();
    }
}
