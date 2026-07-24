<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\LangSite;
use app\common\model\Content;
use think\facade\Cache;

class LangSiteService
{
    private const CACHE_TAG = 'lang_site';

    public function getList(array $params = []): array
    {
        $query = LangSite::order('is_default', 'desc')->order('id', 'asc');
        if (isset($params['status']) && $params['status'] !== '') $query->where('status', (int)$params['status']);
        $total = $query->count();
        $page = max(1, (int)($params['page'] ?? 1));
        $list = $query->page($page, 20)->select()->toArray();
        foreach ($list as &$item) {
            $item['health_status'] = $this->getHealthStatus((int)$item['id']);
            $item['content_stats'] = $this->getSiteStats((int)$item['id']);
        }
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    public function create(array $data): array
    {
        $site = new LangSite($data);
        $site->save();
        Cache::clear();
        return ['success' => true, 'id' => $site->id];
    }

    public function update(int $id, array $data): array
    {
        LangSite::where('id', $id)->update($data);
        Cache::clear();
        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $site = LangSite::find($id);
        if (!$site) return ['success' => false, 'message' => '站点不存在'];
        if ($site->is_default) return ['success' => false, 'message' => '默认站点不可删除'];
        $site->delete();
        Cache::clear();
        return ['success' => true];
    }

    public function toggleStatus(int $id): array
    {
        $site = LangSite::find($id);
        if (!$site) return ['success' => false];
        $site->status = $site->status ? 0 : 1;
        $site->save();
        Cache::clear();
        return ['success' => true, 'status' => $site->status];
    }

    public function getHealthStatus(int $siteId): array
    {
        return Cache::remember("health_{$siteId}", function() use ($siteId) {
            $total = Content::where('lang_site_id', $siteId)->count();
            $translated = Content::where('lang_site_id', $siteId)->where('is_auto_translated', 1)->count();
            $coverage = $total > 0 ? round($translated / $total * 100, 1) : 0;
            return ['translation_coverage' => $coverage, 'content_count' => $total, 'health_score' => min(100, $coverage)];
        }, 300);
    }

    public function getSiteStats(int $siteId): array
    {
        return Cache::remember("stats_{$siteId}", function() use ($siteId) {
            return ['total_content' => Content::where('lang_site_id', $siteId)->count(), 'published' => Content::where('lang_site_id', $siteId)->where('status', 1)->count()];
        }, 300);
    }

    public function getDefaultSite(): array
    {
        return Cache::remember('default_site', function() {
            $site = LangSite::where('is_default', 1)->where('status', 1)->find();
            if (!$site) $site = LangSite::where('status', 1)->order('id', 'asc')->find();
            return $site ? $site->toArray() : ['id' => 0, 'lang_code' => 'zh-CN'];
        }, 3600);
    }

    public function batchToggle(array $ids, int $status): array
    {
        LangSite::whereIn('id', $ids)->update(['status' => $status]);
        Cache::clear();
        return ['success' => true];
    }

    public function copyConfig(int $fromSiteId, array $toSiteIds): array
    {
        $from = LangSite::find($fromSiteId);
        if (!$from) return ['success' => false];
        foreach ($toSiteIds as $toId) {
            if ($toId == $fromSiteId) continue;
            LangSite::where('id', $toId)->update(['site_config' => $from->site_config, 'timezone' => $from->timezone, 'currency' => $from->currency]);
        }
        Cache::clear();
        return ['success' => true];
    }
}
