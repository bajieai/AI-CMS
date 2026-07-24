<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI标签推荐独立Service
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use app\common\model\Tag;
use think\facade\Cache;

/**
 * AI标签推荐独立Service - V2.9.32 FIX-4
 * 从ContentController中提取的标签推荐逻辑
 */
class AiTagService
{
    private const string CACHE_TAG = 'ai_tag';

    /**
     * 推荐标签
     */
    public function recommend(int $contentId, int $count = 5): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $text = $content->title . ' ' . ($content->description ?: '') . ' ' . mb_substr(strip_tags($content->content ?? ''), 0, 2000);
        $keywords = $this->extractKeywords($text, $count);

        // 关联已有标签
        $existingTags = $this->matchExistingTags($keywords);

        return [
            'success' => true,
            'tags' => $keywords,
            'existing_tags' => $existingTags,
            'count' => count($keywords),
            'message' => '标签推荐成功',
        ];
    }

    /**
     * 批量推荐标签
     */
    public function batchRecommend(array $contentIds, int $count = 5): array
    {
        $success = 0;
        $failed = 0;
        $details = [];

        foreach ($contentIds as $id) {
            $result = $this->recommend((int) $id, $count);
            if ($result['success']) {
                $success++;
                $details[] = [
                    'content_id' => $id,
                    'status' => 'success',
                    'tags' => $result['tags'],
                ];
            } else {
                $failed++;
                $details[] = [
                    'content_id' => $id,
                    'status' => 'failed',
                    'message' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'total' => count($contentIds),
            'success_count' => $success,
            'failed_count' => $failed,
            'details' => $details,
        ];
    }

    /**
     * 标签热度统计
     */
    public function tagHotness(int $limit = 20): array
    {
        $cacheKey = "ai_tag_hotness_{$limit}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $tags = \think\facade\Db::table($prefix . 'tag')
                ->order('use_count', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            $result = [
                'list' => $tags,
                'total' => count($tags),
            ];

            Cache::set($cacheKey, $result, 3600);
            return $result;
        } catch (\Throwable $e) {
            return ['list' => [], 'total' => 0];
        }
    }

    /**
     * 从文本提取关键词（简易分词）
     */
    private function extractKeywords(string $text, int $count = 5): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s,，、]/u', ' ', $text);
        $words = preg_split('/[\s,，、]+/u', $text);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= 2);

        $freq = array_count_values($words);
        arsort($freq);

        return array_slice(array_keys($freq), 0, $count);
    }

    /**
     * 匹配已有标签
     */
    private function matchExistingTags(array $keywords): array
    {
        $matched = [];
        foreach ($keywords as $keyword) {
            $tag = Tag::where('name', $keyword)->find();
            if ($tag) {
                $matched[] = [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            }
        }
        return $matched;
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
