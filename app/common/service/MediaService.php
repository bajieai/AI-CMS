<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Media;
use think\facade\Config;
use think\facade\Log;

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
     * V2.6: 支持通过StorageService删除远程文件
     */
    public function delete(int $id): bool
    {
        $media = Media::find($id);
        if (empty($media)) {
            return false;
        }

        // V2.6: 根据存储驱动选择删除方式
        $driver = $media->storage_driver ?? 'local';
        $filePath = $media->storage_path ?: ltrim($media->filepath, '/');

        if ($driver === 'local') {
            $localPath = public_path() . ltrim($filePath, '/');
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        } else {
            try {
                StorageService::driver($driver)->delete($filePath);
            } catch (\Exception $e) {
                // 远程删除失败不阻止记录删除，记录日志
                Log::warning("媒体远程删除失败 [id:{$id}]: " . $e->getMessage());
            }
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
