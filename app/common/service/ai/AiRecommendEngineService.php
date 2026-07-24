<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI推荐引擎服务 — V2.9.40 Sprint AI-DEEP2-3
 *
 * 混合推荐策略：协同过滤40% + 内容推荐30% + 热门推荐20% + 新鲜推荐10%
 * 支持冷启动、已读过滤、多策略融合排序
 */
class AiRecommendEngineService
{
    private const CACHE_TAG  = 'ai_recommend_engine';
    private const CACHE_TTL  = 300; // 5分钟缓存
    private const TABLE_CONTENT = 'content';

    /** 推荐策略权重 */
    private const WEIGHTS = [
        'collaborative' => 0.40, // 协同过滤 40%
        'content'       => 0.30, // 内容推荐 30%
        'hot'           => 0.20, // 热门推荐 20%
        'fresh'         => 0.10, // 新鲜推荐 10%
    ];

    /** 冷启动推荐数量 */
    private const COLD_START_COUNT = 10;

    /**
     * 混合推荐主入口
     *
     * @param int $userId 用户ID
     * @param int $count  推荐数量
     * @return array 推荐内容列表
     */
    public function recommend(int $userId, int $count = 10): array
    {
        $cacheKey = 'ai_rec_' . $userId . '_' . $count;

        return Cache::remember($cacheKey, function () use ($userId, $count) {
            // 冷启动：新用户或无行为数据
            if ($userId <= 0 || !$this->hasUserBehavior($userId)) {
                return $this->coldStart($count);
            }

            // 各策略获取推荐
            $collaborative = $this->collaborativeFiltering($userId, $count * 2);
            $content       = $this->contentBasedFilter($userId, $count * 2);
            $hot           = $this->hotItems($count);
            $fresh         = $this->freshItems($count);

            // 融合排序
            $merged = $this->mergeAndRank([
                'collaborative' => $collaborative,
                'content'       => $content,
                'hot'           => $hot,
                'fresh'         => $fresh,
            ], $count * 2);

            // 过滤已读
            $filtered = $this->filterRead($userId, $merged);

            // 截取最终数量
            return array_slice($filtered, 0, $count);
        }, self::CACHE_TTL);
    }

    /**
     * 协同过滤 — 基于用户行为相似性
     *
     * 找到与当前用户行为相似的用户，推荐他们看过但当前用户未看的内容
     *
     * @param int $userId 用户ID
     * @param int $count  推荐数量
     * @return array
     */
    public function collaborativeFiltering(int $userId, int $count = 10): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            // 1. 获取当前用户浏览过的内容ID
            $userItems = Db::name('visit_log')
                ->where('user_id', $userId)
                ->where('content_id', '>', 0)
                ->column('content_id');
            $userItems = array_unique($userItems);

            if (empty($userItems)) {
                return [];
            }

            // 2. 找到浏览过相同内容的相似用户
            $similarUsers = Db::name('visit_log')
                ->whereIn('content_id', $userItems)
                ->where('user_id', '<>', $userId)
                ->group('user_id')
                ->fieldRaw('user_id, COUNT(*) as overlap_count')
                ->order('overlap_count', 'desc')
                ->limit(50)
                ->select()
                ->column('user_id');

            if (empty($similarUsers)) {
                return [];
            }

            // 3. 获取相似用户浏览过但当前用户未浏览的内容
            $recommendedIds = Db::name('visit_log')
                ->whereIn('user_id', $similarUsers)
                ->whereNotIn('content_id', $userItems)
                ->where('content_id', '>', 0)
                ->group('content_id')
                ->fieldRaw('content_id, COUNT(*) as recommend_score')
                ->order('recommend_score', 'desc')
                ->limit($count)
                ->select()
                ->toArray();

            if (empty($recommendedIds)) {
                return [];
            }

            // 4. 获取内容详情
            $contentIds = array_column($recommendedIds, 'content_id');
            $scoreMap = array_column($recommendedIds, 'recommend_score', 'content_id');

            $items = Db::name(self::TABLE_CONTENT)
                ->whereIn('id', $contentIds)
                ->where('status', 1)
                ->field('id, title, summary, cover_image, cate_id, views, create_time')
                ->select()
                ->toArray();

            // 注入协同过滤得分
            foreach ($items as &$item) {
                $item['rec_score'] = (float) ($scoreMap[$item['id']] ?? 0);
                $item['rec_source'] = 'collaborative';
            }

            // 按得分排序
            usort($items, fn($a, $b) => $b['rec_score'] <=> $a['rec_score']);

            return $items;

        } catch (\Throwable $e) {
            Log::warning("[AiRecommendEngine] 协同过滤失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 基于内容推荐 — 根据用户历史偏好分类/标签
     *
     * @param int $userId 用户ID
     * @param int $count  推荐数量
     * @return array
     */
    public function contentBasedFilter(int $userId, int $count = 10): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            // 1. 获取用户浏览历史中的分类和标签
            $visitedContent = Db::name('visit_log')
                ->where('user_id', $userId)
                ->where('content_id', '>', 0)
                ->limit(100)
                ->column('content_id');

            if (empty($visitedContent)) {
                return [];
            }

            $visitedItems = Db::name(self::TABLE_CONTENT)
                ->whereIn('id', $visitedContent)
                ->field('id, cate_id, tags')
                ->select()
                ->toArray();

            // 2. 统计分类偏好
            $catePrefs = [];
            $tagPrefs = [];
            foreach ($visitedItems as $item) {
                if (!empty($item['cate_id'])) {
                    $catePrefs[$item['cate_id']] = ($catePrefs[$item['cate_id']] ?? 0) + 1;
                }
                if (!empty($item['tags'])) {
                    $tags = is_array($item['tags']) ? $item['tags'] : explode(',', (string) $item['tags']);
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            $tagPrefs[$tag] = ($tagPrefs[$tag] ?? 0) + 1;
                        }
                    }
                }
            }

            // 按偏好降序排列
            arsort($catePrefs);
            arsort($tagPrefs);

            $topCates = array_slice(array_keys($catePrefs), 0, 5);
            $topTags = array_slice(array_keys($tagPrefs), 0, 10);

            if (empty($topCates) && empty($topTags)) {
                return [];
            }

            // 3. 查找同分类/同标签的内容（排除已浏览）
            $query = Db::name(self::TABLE_CONTENT)
                ->where('status', 1)
                ->whereNotIn('id', $visitedContent);

            if (!empty($topCates)) {
                $query->whereIn('cate_id', $topCates);
            }

            if (!empty($topTags)) {
                foreach ($topTags as $tag) {
                    $query->whereOr('tags', 'like', '%' . $tag . '%');
                }
            }

            $items = $query
                ->field('id, title, summary, cover_image, cate_id, views, create_time')
                ->order('views', 'desc')
                ->limit($count)
                ->select()
                ->toArray();

            // 计算内容匹配分
            foreach ($items as &$item) {
                $score = 0.0;
                if (in_array($item['cate_id'] ?? 0, $topCates)) {
                    $score += $catePrefs[$item['cate_id']] ?? 0;
                }
                if (!empty($item['tags'])) {
                    $tags = is_array($item['tags']) ? $item['tags'] : explode(',', (string) $item['tags']);
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (isset($tagPrefs[$tag])) {
                            $score += $tagPrefs[$tag];
                        }
                    }
                }
                $item['rec_score'] = (float) $score;
                $item['rec_source'] = 'content';
            }

            usort($items, fn($a, $b) => $b['rec_score'] <=> $a['rec_score']);

            return $items;

        } catch (\Throwable $e) {
            Log::warning("[AiRecommendEngine] 内容推荐失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 热门推荐 — 按浏览量/互动量排序
     *
     * @param int $count 推荐数量
     * @return array
     */
    public function hotItems(int $count = 10): array
    {
        try {
            // 近7天热门内容
            $items = Db::name(self::TABLE_CONTENT)
                ->where('status', 1)
                ->whereTime('create_time', '-7 days')
                ->field('id, title, summary, cover_image, cate_id, views, create_time')
                ->order('views', 'desc')
                ->limit($count)
                ->select()
                ->toArray();

            // 热度分 = 浏览量 + 时间衰减因子
            $now = time();
            foreach ($items as &$item) {
                $ageHours = ($now - strtotime($item['create_time'])) / 3600;
                $timeDecay = 1 / (1 + $ageHours / 24); // 天级别衰减
                $item['rec_score'] = round(($item['views'] ?? 0) * $timeDecay, 2);
                $item['rec_source'] = 'hot';
            }

            usort($items, fn($a, $b) => $b['rec_score'] <=> $a['rec_score']);

            return $items;

        } catch (\Throwable $e) {
            Log::warning("[AiRecommendEngine] 热门推荐失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 新鲜推荐 — 最新发布内容
     *
     * @param int $count 推荐数量
     * @return array
     */
    public function freshItems(int $count = 10): array
    {
        try {
            $items = Db::name(self::TABLE_CONTENT)
                ->where('status', 1)
                ->field('id, title, summary, cover_image, cate_id, views, create_time')
                ->order('id', 'desc')
                ->limit($count)
                ->select()
                ->toArray();

            $now = time();
            foreach ($items as &$item) {
                $ageHours = ($now - strtotime($item['create_time'])) / 3600;
                // 越新分数越高
                $item['rec_score'] = round(max(0, 100 - $ageHours), 2);
                $item['rec_source'] = 'fresh';
            }

            return $items;

        } catch (\Throwable $e) {
            Log::warning("[AiRecommendEngine] 新鲜推荐失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 多策略融合排序
     *
     * @param array $sources 各策略推荐结果 ['collaborative'=>[], 'content'=>[], 'hot'=>[], 'fresh'=>[]]
     * @param int   $count    最终数量
     * @return array
     */
    public function mergeAndRank(array $sources, int $count = 10): array
    {
        $merged = [];

        // 归一化各策略得分并加权
        foreach (self::WEIGHTS as $strategy => $weight) {
            $items = $sources[$strategy] ?? [];
            if (empty($items)) {
                continue;
            }

            // 归一化得分到 0-1
            $maxScore = max(array_column($items, 'rec_score') ?: [1]);
            $maxScore = $maxScore > 0 ? $maxScore : 1;

            foreach ($items as $item) {
                $normalizedScore = ($item['rec_score'] ?? 0) / $maxScore;
                $weightedScore = $normalizedScore * $weight;

                $contentId = $item['id'];
                if (isset($merged[$contentId])) {
                    // 多策略命中：累加得分
                    $merged[$contentId]['final_score'] += $weightedScore;
                    $merged[$contentId]['sources'][] = $strategy;
                } else {
                    $item['final_score'] = $weightedScore;
                    $item['sources'] = [$strategy];
                    $merged[$contentId] = $item;
                }
            }
        }

        // 按最终得分排序
        $result = array_values($merged);
        usort($result, fn($a, $b) => ($b['final_score'] ?? 0) <=> ($a['final_score'] ?? 0));

        return array_slice($result, 0, $count);
    }

    /**
     * 过滤已读内容
     *
     * @param int   $userId 用户ID
     * @param array $items  推荐内容列表
     * @return array
     */
    public function filterRead(int $userId, array $items): array
    {
        if ($userId <= 0 || empty($items)) {
            return $items;
        }

        try {
            $contentIds = array_column($items, 'id');
            $readIds = Db::name('visit_log')
                ->where('user_id', $userId)
                ->whereIn('content_id', $contentIds)
                ->column('content_id');
            $readIds = array_flip($readIds);

            return array_filter($items, fn($item) => !isset($readIds[$item['id']]));
        } catch (\Throwable $e) {
            return $items;
        }
    }

    /**
     * 冷启动推荐 — 无用户行为数据时使用
     *
     * 策略：热门内容 + 最新内容各50%
     *
     * @param int $count 推荐数量
     * @return array
     */
    public function coldStart(int $count = 10): array
    {
        $halfCount = (int) ceil($count / 2);

        $hot = $this->hotItems($halfCount);
        $fresh = $this->freshItems($count - count($hot));

        $merged = array_merge($hot, $fresh);

        // 打乱顺序，避免热门永远在前
        shuffle($merged);

        return array_slice($merged, 0, $count);
    }

    /**
     * 检查用户是否有行为数据
     */
    protected function hasUserBehavior(int $userId): bool
    {
        try {
            return Db::name('visit_log')
                ->where('user_id', $userId)
                ->where('content_id', '>', 0)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
