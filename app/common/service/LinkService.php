<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Link;

/**
 * 友情链接服务
 */
class LinkService
{
    /**
     * 获取链接列表（后台）
     */
    public function getList(array $params = [], int $pageSize = 20)
    {
        try {
            $query = Link::order('sort', 'asc')->order('id', 'desc');

            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', (int) $params['status']);
            }

            return $query->paginate($pageSize);
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                return new \think\Collection([]);
            }
            throw $e;
        }
    }

    /**
     * 创建链接
     */
    public function create(array $data): bool
    {
        $link = new Link();
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $link->save($data);
    }

    /**
     * 获取单条链接
     */
    public function getById(int $id): ?array
    {
        $link = Link::find($id);
        return $link ? $link->toArray() : null;
    }

    /**
     * 更新链接
     */
    public function update(int $id, array $data): bool
    {
        $link = Link::find($id);
        if (empty($link)) {
            return false;
        }
        $data['update_time'] = time();
        return $link->save($data);
    }

    /**
     * 删除链接
     */
    public function delete(int $id): bool
    {
        $link = Link::find($id);
        if (empty($link)) {
            return false;
        }
        return $link->delete();
    }

    /**
     * 获取启用的友情链接列表（前台使用）
     */
    public function getActiveList(int $limit = 50): array
    {
        return Link::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取友情链接列表（前台模板标签使用）
     */
    public function getLinkList(int $limit = 10, int $status = 1, int $group = 0): array
    {
        try {
            $query = Link::order('sort', 'asc')->order('id', 'desc');
            if ($status !== '') {
                $query->where('status', (int) $status);
            }
            if ($group > 0) {
                $query->where('group_id', $group);
            }
            return $query->limit($limit)->select()->toArray();
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                return [];
            }
            throw $e;
        }
    }
}
