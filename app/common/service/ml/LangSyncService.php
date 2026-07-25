<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\Content;
use app\common\service\ai\AiTranslateService;
use think\facade\Cache;

/**
 * 多语言内容同步 — V2.9.34 ML-4
 * 
 * 同步状态机: 已同步(synced) → 待翻译(pending) → 翻译中(translating) → 已更新(needs_update) → 同步冲突(conflict)
 * 冲突检测: 基于最后修改时间戳比较，主语言和翻译语言同时修改时标记冲突
 */
class LangSyncService
{
    private const CACHE_TAG = 'lang_sync';

    public const STATUS_SYNCED = 'synced';
    public const STATUS_PENDING = 'pending';
    public const STATUS_TRANSLATING = 'translating';
    public const STATUS_NEEDS_UPDATE = 'needs_update';
    public const STATUS_CONFLICT = 'conflict';

    public function getSyncStatus(int $contentId, int $langSiteId): array
    {
        $translated = Content::where('translation_of', $contentId)->where('lang_site_id', $langSiteId)->find();
        if (!$translated) return ['status' => self::STATUS_PENDING, 'message' => '未翻译'];

        $original = Content::find($contentId);
        if (!$original) return ['status' => self::STATUS_SYNCED, 'message' => '已同步'];

        // 冲突检测：主语言和翻译语言都被修改
        $originalModified = (int)$original->update_time;
        $translatedModified = (int)$translated->update_time;
        $lastSyncTime = (int)($translated->create_time ?? 0);

        if ($originalModified > $lastSyncTime && $translatedModified > $lastSyncTime) {
            return ['status' => self::STATUS_CONFLICT, 'message' => '主语言和翻译语言同时修改，存在冲突'];
        }
        if ($originalModified > $lastSyncTime) {
            return ['status' => self::STATUS_NEEDS_UPDATE, 'message' => '主语言已更新，需重新翻译'];
        }
        return ['status' => self::STATUS_SYNCED, 'message' => '已同步'];
    }

    public function batchTranslate(array $contentIds, int $targetLangSiteId): array
    {
        $translateService = new AiTranslateService();
        $targetSite = \app\common\model\LangSite::find($targetLangSiteId);
        if (!$targetSite) return ['success' => false, 'message' => '目标语言站点不存在'];

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        foreach ($contentIds as $contentId) {
            try {
                $original = Content::find($contentId);
                if (!$original) { $results['errors'][] = "内容{$contentId}不存在"; $results['failed']++; continue; }

                // 检查是否已有翻译
                $existing = Content::where('translation_of', $contentId)->where('lang_site_id', $targetLangSiteId)->find();
                if ($existing) { $results['errors'][] = "内容{$contentId}已有翻译"; continue; }

                // AI翻译
                $translatedTitle = $translateService->translate($original->title, $targetSite->lang_code);
                $translatedContent = $translateService->translate($original->content, $targetSite->lang_code);
                $translatedSummary = $original->summary ? $translateService->translate($original->summary, $targetSite->lang_code) : '';

                // 创建翻译内容
                Content::create([
                    'title' => $translatedTitle,
                    'content' => $translatedContent,
                    'summary' => $translatedSummary,
                    'lang' => $targetSite->lang_code,
                    'lang_site_id' => $targetLangSiteId,
                    'translation_of' => $contentId,
                    'is_auto_translated' => 1,
                    'status' => 0, // 草稿状态，需人工审核
                    'content_model' => $original->content_model,
                    'category_id' => $original->category_id,
                ]);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors'][] = "内容{$contentId}翻译失败: " . $e->getMessage();
                $results['failed']++;
            }
        }
        Cache::clear();
        return ['success' => true, 'results' => $results];
    }

    public function getSyncReport(): array
    {
        return Cache::remember('sync_report', function() {
            $sites = \app\common\model\LangSite::where('status', 1)->select();
            $report = [];
            foreach ($sites as $site) {
                $total = Content::where('lang_site_id', $site->id)->count();
                $translated = Content::where('lang_site_id', $site->id)->where('is_auto_translated', 1)->count();
                $pending = Content::where('lang_site_id', 0)->count(); // 未分配站点的内容
                $report[] = ['site_id' => $site->id, 'lang_code' => $site->lang_code, 'total' => $total, 'translated' => $translated, 'coverage' => $total > 0 ? round($translated / $total * 100, 1) : 0];
            }
            return $report;
        }, 300);
    }

    public function markAsNeedsUpdate(int $contentId): void
    {
        Content::where('translation_of', $contentId)->update(['is_auto_translated' => 0]);
        Cache::clear();
    }

    public function resolveConflict(int $contentId, int $langSiteId, string $resolution): array
    {
        $translated = Content::where('translation_of', $contentId)->where('lang_site_id', $langSiteId)->find();
        if (!$translated) return ['success' => false, 'message' => '翻译内容不存在'];

        if ($resolution === 'keep_original') {
            // 用主语言覆盖翻译
            $original = Content::find($contentId);
            $translateService = new AiTranslateService();
            $translated->title = $translateService->translate($original->title, $translated->lang);
            $translated->save();
        }
        // 'keep_translation' = 保持翻译版本不变
        $translated->create_time = time(); // 更新同步时间
        $translated->save();
        Cache::clear();
        return ['success' => true];
    }
}
