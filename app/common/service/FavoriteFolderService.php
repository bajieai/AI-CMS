<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\FavoriteFolder;
use app\common\model\Favorite;
use think\facade\Db;
use think\facade\Cache;

class FavoriteFolderService
{
    private const CACHE_TAG = 'favorite_folder';
    private const CACHE_TTL = 300;

    public function create(int $userId, string $name, string $description = '', bool $isPublic = false): FavoriteFolder
    {
        $folder = FavoriteFolder::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'is_public' => $isPublic ? 1 : 0,
            'sort' => 99,
        ]);
        $this->clearCache();
        return $folder;
    }

    public function update(int $folderId, array $data): bool
    {
        $folder = FavoriteFolder::find($folderId);
        if (!$folder) return false;
        $folder->save($data);
        $this->clearCache();
        return true;
    }

    public function delete(int $folderId): bool
    {
        Db::transaction(function () use ($folderId) {
            Favorite::where('folder_id', $folderId)->update(['folder_id' => 0]);
            FavoriteFolder::destroy($folderId);
        });
        $this->clearCache();
        return true;
    }

    public function getUserFolders(int $userId): array
    {
        return Cache::remember(
            'favorite_folder:user:' . $userId,
            function () use ($userId) {
                return FavoriteFolder::where('user_id', $userId)
                    ->order('sort', 'asc')
                    ->select()->toArray();
            },
            self::CACHE_TTL
        );
    }

    public function moveToFolder(int $favoriteId, int $folderId): bool
    {
        $favorite = Favorite::find($favoriteId);
        if (!$favorite) return false;
        $favorite->folder_id = $folderId;
        $favorite->save();
        $this->clearCache();
        return true;
    }

    public function getFolderTemplates(int $folderId): array
    {
        $favorites = Favorite::where('folder_id', $folderId)
            ->where('type', 'template')
            ->select()->toArray();
        $storeIds = array_column($favorites, 'target_id');
        if (empty($storeIds)) return [];
        return \app\common\model\TemplateStore::whereIn('id', $storeIds)
            ->select()->toArray();
    }

    private function clearCache(): void
    {
        Cache::clear();
    }
}
