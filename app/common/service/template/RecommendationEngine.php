<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateStore;
use app\common\model\TemplateStoreCategory;
use app\common\model\TemplateRecommendRule;
use app\common\model\TemplateRecommendStats;
use app\common\model\TemplateInstallLog;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI模板智能推荐引擎 — V2.9.26 P-1
 *
 * 推荐策略优先级（高→低）：
 * 1. 手动置顶 (manual) — 运营手动指定，最高优先
 * 2. 节日特推 (festival) — 时间窗口内的节日活动
 * 3. 新品首发 (new_release) — 近7天上架的新模板
 * 4. AI推荐 (ai) — 基于规则的协同过滤（安装历史+评分+分类偏好）
 * 5. 分类热门 (category) — 按分类的安装量排序
 *
 * 冷启动策略：新模板无安装历史时，按创建时间倒序展示
 * 兜底策略：推荐结果为空时，按总安装量倒序返回
 */
class RecommendationEngine
{
    public const CACHE_TAG = 'template_recommend';
    public const CACHE_TTL = 300;

    /**
     * 获取推荐列表
     *
     * @param string $position 推荐位: home/sidebar/detail/search
     * @param int $userId 用户ID
     * @param int $categoryId 分类ID
     * @param int $limit 返回数量
     * @return array
     */
    public function getRecommendations(
        string $position = 'home',
        int $userId = 0,
        int $categoryId = 0,
        int $limit = 10
    ): array {
        $cacheKey = 'reco_' . $position . '_' . $userId . '_' . $categoryId . '_' . $limit;

        return Cache::remember($cacheKey, function () use ($position, $userId, $categoryId, $limit) {
            $rules = TemplateRecommendRule::getActiveRules(50);

            $manualRules = array_filter($rules, fn($r) => $r['rule_type'] === 'manual');
            $festivalRules = array_filter($rules, fn($r) => $r['rule_type'] === 'festival');

            $result = [];
            $usedIds = [];

            // 策略1: 手动置顶
            foreach ($manualRules as $rule) {
                $templateIds = $rule['template_ids'] ?? [];
                if (!empty($templateIds)) {
                    $templates = $this->fetchTemplates($templateIds, $usedIds);
                    foreach ($templates as $tpl) {
                        $tpl['recommend_rule'] = $rule['name'];
                        $tpl['recommend_type'] = 'manual';
                        $tpl['rule_id'] = $rule['id'];
                        $result[] = $tpl;
                        $usedIds[] = $tpl['id'];
                    }
                }
            }

            // 策略2: 节日特推
            foreach ($festivalRules as $rule) {
                $templateIds = $rule['template_ids'] ?? [];
                if (!empty($templateIds)) {
                    $templates = $this->fetchTemplates($templateIds, $usedIds);
                    foreach ($templates as $tpl) {
                        $tpl['recommend_rule'] = $rule['name'];
                        $tpl['recommend_type'] = 'festival';
                        $tpl['rule_id'] = $rule['id'];
                        $result[] = $tpl;
                        $usedIds[] = $tpl['id'];
                    }
                }
            }

            // 策略3: 新品首发
            if (count($result) < $limit) {
                $newTemplates = $this->getNewReleases($limit - count($result), $usedIds);
                foreach ($newTemplates as $tpl) {
                    $tpl['recommend_type'] = 'new_release';
                    $tpl['rule_id'] = 0;
                    $result[] = $tpl;
                    $usedIds[] = $tpl['id'];
                }
            }

            // 策略4: AI推荐
            if (count($result) < $limit) {
                $aiTemplates = $this->getAiRecommendations($userId, $categoryId, $limit - count($result), $usedIds);
                foreach ($aiTemplates as $tpl) {
                    $tpl['recommend_type'] = 'ai';
                    $tpl['rule_id'] = 0;
                    $result[] = $tpl;
                    $usedIds[] = $tpl['id'];
                }
            }

            // 策略5: 分类热门
            if (count($result) < $limit && $categoryId > 0) {
                $hotTemplates = $this->getCategoryHot($categoryId, $limit - count($result), $usedIds);
                foreach ($hotTemplates as $tpl) {
                    $tpl['recommend_type'] = 'category';
                    $tpl['rule_id'] = 0;
                    $result[] = $tpl;
                    $usedIds[] = $tpl['id'];
                }
            }

            // 兜底策略
            if (count($result) < $limit) {
                $fallback = $this->getFallback($limit - count($result), $usedIds);
                foreach ($fallback as $tpl) {
                    $tpl['recommend_type'] = 'fallback';
                    $tpl['rule_id'] = 0;
                    $result[] = $tpl;
                    $usedIds[] = $tpl['id'];
                }
            }

            return array_slice($result, 0, $limit);
        }, self::CACHE_TTL);
    }

    /**
     * 获取新品首发（近7天）
     */
    protected function getNewReleases(int $limit, array $excludeIds = []): array
    {
        $query = TemplateStore::where('status', 1)
            ->where('is_published', 1)
            ->whereTime('created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')));

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * AI推荐（基于规则的协同过滤）
     */
    protected function getAiRecommendations(int $userId, int $categoryId, int $limit, array $excludeIds = []): array
    {
        if ($userId <= 0 && $categoryId <= 0) {
            return $this->getFallback($limit, $excludeIds);
        }

        $preferredCategories = [];
        if ($userId > 0) {
            $installLogs = TemplateInstallLog::where('user_id', $userId)
                ->field('template_id')
                ->limit(50)
                ->select()
                ->toArray();

            if (!empty($installLogs)) {
                $templateIds = array_column($installLogs, 'template_id');
                $cats = TemplateStore::whereIn('id', $templateIds)
                    ->where('category_id', '>', 0)
                    ->field('category_id, COUNT(*) as cnt')
                    ->group('category_id')
                    ->order('cnt', 'desc')
                    ->limit(3)
                    ->select()
                    ->toArray();
                $preferredCategories = array_column($cats, 'category_id');
            }
        }

        if ($categoryId > 0) {
            $preferredCategories = array_unique(array_merge([$categoryId], $preferredCategories));
        }

        if (empty($preferredCategories)) {
            return $this->getFallback($limit, $excludeIds);
        }

        $query = TemplateStore::where('status', 1)
            ->where('is_published', 1)
            ->whereIn('category_id', $preferredCategories);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderRaw('(install_count + rating * 20) DESC')
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 分类热门
     */
    protected function getCategoryHot(int $categoryId, int $limit, array $excludeIds = []): array
    {
        $query = TemplateStore::where('status', 1)
            ->where('is_published', 1)
            ->where('category_id', $categoryId);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->order('install_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 兜底策略：按安装量倒序
     */
    protected function getFallback(int $limit, array $excludeIds = []): array
    {
        $query = TemplateStore::where('status', 1)->where('is_published', 1);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->order('install_count', 'desc')
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 批量获取模板（排除已用的）
     */
    protected function fetchTemplates(array $templateIds, array $excludeIds = []): array
    {
        $ids = array_diff($templateIds, $excludeIds);
        if (empty($ids)) {
            return [];
        }
        return TemplateStore::whereIn('id', $ids)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 清除推荐缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
