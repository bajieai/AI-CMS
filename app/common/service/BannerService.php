<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Banner;

/**
 * 轮播图服务
 */
class BannerService
{
    /**
     * 获取轮播图列表（后台）
     */
    public function getList(array $params = [], int $pageSize = 20)
    {
        try {
            $query = Banner::order('sort', 'asc')->order('id', 'desc');

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
     * 创建轮播图
     */
    public function create(array $data): bool
    {
        $banner = new Banner();
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $banner->save($data);
    }

    /**
     * 获取单条轮播图
     */
    public function getById(int $id): ?array
    {
        $banner = Banner::find($id);
        return $banner ? $banner->toArray() : null;
    }

    /**
     * 更新轮播图
     */
    public function update(int $id, array $data): bool
    {
        $banner = Banner::find($id);
        if (empty($banner)) {
            return false;
        }
        $data['update_time'] = time();
        return $banner->save($data);
    }

    /**
     * 删除轮播图
     */
    public function delete(int $id): bool
    {
        $banner = Banner::find($id);
        if (empty($banner)) {
            return false;
        }
        return $banner->delete();
    }

    /**
     * 获取启用的轮播图列表（前台使用）
     */
    public function getActiveList(int $limit = 10): array
    {
        $list = Banner::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->limit($limit)
            ->select();

        $result = [];
        foreach ($list as $item) {
            if ($item->is_active) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 获取轮播图列表（前台模板标签使用）
     */
    public function getBannerList(int $limit = 5, int $status = 1): array
    {
        try {
            $query = Banner::order('sort', 'asc')->order('id', 'desc');
            if ($status !== '') {
                $query->where('status', (int) $status);
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
