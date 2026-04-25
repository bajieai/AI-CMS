<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Media;
use think\facade\Config;

/**
 * 媒体资源服务
 */
class MediaService
{
    /**
     * 获取媒体列表（后台分页）
     */
    public function getList(array $params = [], int $pageSize = 20)
    {
        try {
            $query = Media::with('user');

            if (!empty($params['filetype'])) {
                $query->where('filetype', $params['filetype']);
            }
            if (!empty($params['keyword'])) {
                $query->where('filename', 'like', '%' . $params['keyword'] . '%');
            }
            if (!empty($params['cate_id'])) {
                $query->where('cate_id', (int) $params['cate_id']);
            }

            return $query->order('id', 'desc')->paginate($pageSize);
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                return new \think\Collection([]);
            }
            throw $e;
        }
    }

    /**
     * 创建媒体记录
     */
    public function create(array $data): bool
    {
        $media = new Media();
        $data['user_id'] = session('user_id') ?: 0;
        $data['create_time'] = time();
        return $media->save($data);
    }

    /**
     * 更新媒体记录
     */
    public function update(int $id, array $data): bool
    {
        $media = Media::find($id);
        if (empty($media)) {
            return false;
        }
        return $media->save($data);
    }

    /**
     * 删除媒体记录及文件
     */
    public function delete(int $id): bool
    {
        $media = Media::find($id);
        if (empty($media)) {
            return false;
        }

        // 删除物理文件
        $filePath = public_path() . ltrim($media->filepath, '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        return $media->delete();
    }

    /**
     * 获取单条媒体记录
     */
    public function getById(int $id): ?array
    {
        $media = Media::with('user')->find($id);
        return $media ? $media->toArray() : null;
    }

    /**
     * 获取媒体列表（前台模板标签使用）
     */
    public function getMediaList(string $filetype = '', int $limit = 10, string $order = 'id desc')
    {
        try {
            $query = Media::where('filetype', $filetype ?: 'image');
            return $query->order($order)->limit($limit)->select();
        } catch (\think\db\exception\DbException $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown table')) {
                return new \think\Collection([]);
            }
            throw $e;
        }
    }
}
