<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;

/**
 * 多语言标签/分类服务 - V2.9.40 I18N-V3-4
 *
 * 多语言标签映射、分类翻译、标签统一管理
 */
class MultilingualTagService
{
    private const CACHE_TAG = 'multilingual_tag';
    private const CACHE_TTL = 3600;

    /**
     * 创建多语言标签映射组
     */
    public function createTagGroup(string $name, string $originalLang = 'zh'): int
    {
        $id = Db::name('multilingual_tag')->insertGetId([
            'name'          => $name,
            'original_lang' => $originalLang,
            'translations'  => json_encode([$originalLang => $name]),
            'status'        => 1,
            'usage_count'   => 0,
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 添加标签翻译
     */
    public function addTranslation(int $tagId, string $lang, string $translation): bool
    {
        $tag = Db::name('multilingual_tag')->find($tagId);
        if (!$tag) return false;

        $translations = json_decode($tag['translations'] ?? '{}', true);
        $translations[$lang] = $translation;

        Db::name('multilingual_tag')->where('id', $tagId)->update([
            'translations' => json_encode($translations),
            'updated_at'   => time(),
        ]);

        Cache::clear();
        return true;
    }

    /**
     * 获取标签翻译
     */
    public function getTranslation(int $tagId, string $lang): string
    {
        $cacheKey = 'tag_' . $tagId . '_' . $lang;

        return Cache::remember($cacheKey, function () use ($tagId, $lang) {
            $tag = Db::name('multilingual_tag')->find($tagId);
            if (!$tag) return '';

            $translations = json_decode($tag['translations'] ?? '{}', true);

            // 三级回退：目标语言 → 原始语言 → 空字符串
            return $translations[$lang] ?? $translations[$tag['original_lang']] ?? '';
        }, self::CACHE_TTL);
    }

    /**
     * 批量获取标签翻译（用于页面渲染）
     */
    public function batchGetTranslations(array $tagIds, string $lang): array
    {
        $result = [];
        foreach ($tagIds as $id) {
            $result[$id] = $this->getTranslation((int) $id, $lang);
        }
        return $result;
    }

    /**
     * 查找标签（按任意语言的翻译搜索）
     */
    public function searchTag(string $keyword, string $lang = ''): array
    {
        $query = Db::name('multilingual_tag')->where('status', 1);

        if (!empty($lang)) {
            $query->where(function ($q) use ($keyword, $lang) {
                $q->whereOr('name', 'like', '%' . $keyword . '%');
                // JSON字段中搜索翻译
                $q->whereOr("JSON_EXTRACT(translations, '$.{$lang}')", 'like', '%' . $keyword . '%');
            });
        } else {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        return $query->order('usage_count', 'desc')->limit(50)->select()->toArray();
    }

    /**
     * 增加标签使用计数
     */
    public function incrementUsage(int $tagId): void
    {
        Db::name('multilingual_tag')->where('id', $tagId)->inc('usage_count')->update();
    }

    /**
     * 获取标签列表
     */
    public function getList(int $page = 1, int $limit = 50): array
    {
        return Db::name('multilingual_tag')
            ->where('status', 1)
            ->order('usage_count', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 删除标签
     */
    public function delete(int $tagId): bool
    {
        Db::name('multilingual_tag')->where('id', $tagId)->update([
            'status' => 0,
            'updated_at' => time(),
        ]);
        Cache::clear();
        return true;
    }

    /**
     * AI批量翻译标签
     */
    public function aiBatchTranslate(int $tagId, array $targetLangs): array
    {
        $tag = Db::name('multilingual_tag')->find($tagId);
        if (!$tag) return [];

        $originalLang = $tag['original_lang'];
        $originalName = $tag['name'];
        $results = [];

        try {
            $aiService = new \app\common\service\AiService();
            foreach ($targetLangs as $lang) {
                $prompt = "将以下{$originalLang}标签翻译为{$lang}，保持简洁准确：{$originalName}";
                $translation = $aiService->generate($prompt, ['max_tokens' => 50]) ?? '';
                if (!empty($translation)) {
                    $this->addTranslation($tagId, $lang, trim($translation));
                    $results[$lang] = trim($translation);
                }
            }
        } catch (\Exception $e) {
            // AI翻译失败时回退
        }

        return $results;
    }
}
