<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\ContentSlug;
use app\common\model\Content;
use think\facade\Cache;

/**
 * URL别名管理 — V2.9.34 ML-3
 * 各语言独立slug + 冲突检测 + 旧URL 301跳转
 */
class LangSlugService
{
    private const CACHE_TAG = 'lang_slug';

    public function getSlug(int $contentId, int $langSiteId): ?string
    {
        $slug = ContentSlug::where('content_id', $contentId)->where('lang_site_id', $langSiteId)->where('is_active', 1)->find();
        return $slug ? $slug->slug : null;
    }

    public function saveSlug(int $contentId, int $langSiteId, string $slug): array
    {
        // 冲突检测
        $exists = ContentSlug::where('lang_site_id', $langSiteId)->where('slug', $slug)->where('content_id', '<>', $contentId)->find();
        if ($exists) return ['success' => false, 'message' => 'slug已被其他内容使用'];

        // 旧slug标记为inactive（保留历史用于301跳转）
        ContentSlug::where('content_id', $contentId)->where('lang_site_id', $langSiteId)->update(['is_active' => 0]);

        // 创建新slug
        ContentSlug::create(['content_id' => $contentId, 'lang_site_id' => $langSiteId, 'slug' => $slug, 'is_active' => 1]);

        // 更新content表冗余字段
        Content::where('id', $contentId)->update(['slug' => $slug]);

        Cache::clear();
        return ['success' => true];
    }

    public function findBySlug(string $slug, int $langSiteId): ?int
    {
        $record = ContentSlug::where('lang_site_id', $langSiteId)->where('slug', $slug)->find();
        return $record ? (int)$record->content_id : null;
    }

    public function findOldSlug(string $slug, int $langSiteId): ?array
    {
        $record = ContentSlug::where('lang_site_id', $langSiteId)->where('slug', $slug)->where('is_active', 0)->find();
        if (!$record) return null;
        $active = ContentSlug::where('content_id', $record->content_id)->where('lang_site_id', $langSiteId)->where('is_active', 1)->find();
        return $active ? ['content_id' => (int)$record->content_id, 'new_slug' => $active->slug] : null;
    }

    public function generateSlug(string $title, int $langSiteId): string
    {
        $slug = $this->slugify($title);
        $base = $slug;
        $i = 1;
        while (ContentSlug::where('lang_site_id', $langSiteId)->where('slug', $slug)->find()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text) ?: 'content-' . time();
    }
}
