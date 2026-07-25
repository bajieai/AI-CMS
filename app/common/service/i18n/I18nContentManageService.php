<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;

/**
 * 多语言内容管理服务 - V2.9.40 I18N-V3-3
 *
 * 多语言内容关联、翻译版本管理、内容同步
 */
class I18nContentManageService
{
    private const CACHE_TAG = 'i18n_content';
    private const CACHE_TTL = 600;

    /**
     * 创建多语言内容关联组
     */
    public function createGroup(string $name, string $description = ''): int
    {
        $id = Db::name('i18n_content_group')->insertGetId([
            'name'        => $name,
            'description' => $description,
            'status'      => 1,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);
        Cache::clear();
        return (int) $id;
    }

    /**
     * 将内容关联到翻译组
     */
    public function linkContent(int $groupId, int $contentId, string $lang): bool
    {
        // 检查内容是否已在组中
        $exists = Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->where('lang', $lang)
            ->find();

        if ($exists) {
            // 更新关联
            Db::name('i18n_content_link')->where('id', $exists['id'])->update([
                'content_id'  => $contentId,
                'updated_at'  => time(),
            ]);
        } else {
            Db::name('i18n_content_link')->insert([
                'group_id'    => $groupId,
                'content_id'  => $contentId,
                'lang'        => $lang,
                'is_original' => 0,
                'created_at'  => time(),
                'updated_at'  => time(),
            ]);
        }

        Cache::clear();
        return true;
    }

    /**
     * 设置原始语言版本
     */
    public function setOriginal(int $groupId, string $lang): bool
    {
        Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->update(['is_original' => 0]);

        Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->where('lang', $lang)
            ->update(['is_original' => 1]);

        Cache::clear();
        return true;
    }

    /**
     * 获取翻译组所有语言版本
     */
    public function getGroupVersions(int $groupId): array
    {
        return Cache::remember('group_versions_' . $groupId, function () use ($groupId) {
            $links = Db::name('i18n_content_link')
                ->where('group_id', $groupId)
                ->select()
                ->toArray();

            $versions = [];
            foreach ($links as $link) {
                $content = Db::name('content')->find($link['content_id']);
                $versions[$link['lang']] = [
                    'content_id'  => $link['content_id'],
                    'lang'        => $link['lang'],
                    'is_original' => $link['is_original'],
                    'title'       => $content ? $content['title'] : '',
                    'status'      => $content ? $content['status'] : 0,
                ];
            }

            return $versions;
        }, self::CACHE_TTL);
    }

    /**
     * 同步内容更新到翻译版本
     */
    public function syncToTranslations(int $groupId, array $fields = []): array
    {
        $links = Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->where('is_original', 1)
            ->find();

        if (!$links) return ['success' => false, 'msg' => '无原始版本'];

        $originalContent = Db::name('content')->find($links['content_id']);
        if (!$originalContent) return ['success' => false, 'msg' => '原始内容不存在'];

        $translationLinks = Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->where('is_original', 0)
            ->select()
            ->toArray();

        $synced = [];
        foreach ($translationLinks as $tl) {
            // 标记翻译版本需要更新（不直接覆盖，需人工审核）
            Db::name('i18n_content_link')->where('id', $tl['id'])->update([
                'needs_update' => 1,
                'update_fields' => json_encode($fields ?: ['title', 'content', 'description']),
                'updated_at'    => time(),
            ]);
            $synced[] = $tl['lang'];
        }

        return ['success' => true, 'synced_langs' => $synced];
    }

    /**
     * 获取需要更新的翻译列表
     */
    public function getPendingUpdates(int $groupId): array
    {
        return Db::name('i18n_content_link')
            ->where('group_id', $groupId)
            ->where('needs_update', 1)
            ->select()
            ->toArray();
    }

    /**
     * 获取内容的多语言版本ID
     */
    public function getContentLangVersions(int $contentId): array
    {
        $link = Db::name('i18n_content_link')
            ->where('content_id', $contentId)
            ->find();

        if (!$link) return [];

        return Db::name('i18n_content_link')
            ->where('group_id', $link['group_id'])
            ->column('content_id', 'lang');
    }

    /**
     * 获取指定语言的版本内容ID
     */
    public function getTranslatedContentId(int $contentId, string $targetLang): int
    {
        $link = Db::name('i18n_content_link')
            ->where('content_id', $contentId)
            ->find();

        if (!$link) return 0;

        $target = Db::name('i18n_content_link')
            ->where('group_id', $link['group_id'])
            ->where('lang', $targetLang)
            ->find();

        return $target ? (int) $target['content_id'] : 0;
    }

    /**
     * 获取列表
     */
    public function getGroupList(int $page = 1, int $limit = 20): array
    {
        return Db::name('i18n_content_group')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 删除翻译组
     */
    public function deleteGroup(int $groupId): bool
    {
        Db::name('i18n_content_link')->where('group_id', $groupId)->delete();
        Db::name('i18n_content_group')->where('id', $groupId)->delete();
        Cache::clear();
        return true;
    }
}
