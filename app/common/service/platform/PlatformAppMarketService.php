<?php
declare(strict_types=1);

namespace app\common\service\platform;

use app\common\model\PlatformApp;
use think\facade\Cache;

/**
 * 开放平台应用市场服务
 * V2.9.38 OPEN-PLAT-4
 * 参照PluginMarketService模式
 */
class PlatformAppMarketService
{
    protected const CACHE_TAG = 'app_market';
    protected const CACHE_TTL = 1800;

    public function getMarketList(array $params = []): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(50, max(1, (int)($params['limit'] ?? 12)));
        $query = PlatformApp::where('status', PlatformApp::STATUS_PUBLISHED);
        if (!empty($params['category'])) $query->where('category', $params['category']);
        if (!empty($params['app_type'])) $query->where('app_type', $params['app_type']);
        if (!empty($params['keyword'])) $query->where('app_name', 'like', '%' . $params['keyword'] . '%');
        $sort = $params['sort'] ?? 'popular';
        switch ($sort) {
            case 'rating': $query->order('avg_rating', 'desc'); break;
            case 'newest': $query->order('id', 'desc'); break;
            case 'popular': default: $query->order('install_count', 'desc'); break;
        }
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    public function getMarketDetail(int $id): ?array
    {
        return Cache::remember('app_market_' . $id, function() use ($id) {
            $app = PlatformApp::find($id);
            return $app ? $app->toArray() : null;
        }, self::CACHE_TTL);
    }

    public function submitApp(int $developerId, array $data): int
    {
        $app = new PlatformApp();
        $app->save([
            'app_name' => $data['app_name'] ?? '',
            'app_identifier' => $data['app_identifier'] ?? ('app_' . uniqid()),
            'app_type' => $data['app_type'] ?? PlatformApp::TYPE_WEB,
            'developer_id' => $developerId,
            'description' => $data['description'] ?? '',
            'app_config' => $data['app_config'] ?? null,
            'required_permissions' => $data['required_permissions'] ?? null,
            'version' => $data['version'] ?? '1.0.0',
            'download_url' => $data['download_url'] ?? '',
            'category' => $data['category'] ?? '',
            'tags' => $data['tags'] ?? '',
            'screenshots' => $data['screenshots'] ?? null,
            'status' => PlatformApp::STATUS_PENDING,
        ]);
        Cache::clear();
        return (int) $app->id;
    }

    public function auditApp(int $appId, bool $approved, string $remark = ''): bool
    {
        $app = PlatformApp::find($appId);
        if (!$app) return false;
        $app->save([
            'status' => $approved ? PlatformApp::STATUS_APPROVED : PlatformApp::STATUS_REJECTED,
            'audit_remark' => $remark,
            'audited_at' => date('Y-m-d H:i:s'),
        ]);
        Cache::clear();
        return true;
    }

    public function publishApp(int $appId): bool
    {
        $app = PlatformApp::find($appId);
        if (!$app || $app->status !== PlatformApp::STATUS_APPROVED) return false;
        $app->save(['status' => PlatformApp::STATUS_PUBLISHED]);
        Cache::clear();
        return true;
    }

    public function offlineApp(int $appId): bool
    {
        $app = PlatformApp::find($appId);
        if (!$app) return false;
        $app->save(['status' => PlatformApp::STATUS_OFFLINE]);
        Cache::clear();
        return true;
    }

    public function installApp(int $appId, int $memberId): bool
    {
        PlatformApp::where('id', $appId)->inc('install_count')->update();
        Cache::clear();
        return true;
    }

    public function uninstallApp(int $appId, int $memberId): bool
    {
        PlatformApp::where('id', $appId)->dec('install_count')->update();
        return true;
    }

    public function upgradeApp(int $appId, string $newVersion): bool
    {
        $app = PlatformApp::find($appId);
        if (!$app) return false;
        $app->save(['version' => $newVersion]);
        return true;
    }

    public function rateApp(int $appId, float $rating): bool
    {
        $app = PlatformApp::find($appId);
        if (!$app) return false;
        $currentRating = (float) $app->avg_rating;
        $installCount = (int) $app->install_count;
        $newRating = $installCount > 0 ? (($currentRating * $installCount) + $rating) / ($installCount + 1) : $rating;
        $app->save(['avg_rating' => round($newRating, 1)]);
        Cache::clear();
        return true;
    }

    /**
     * 获取待审核应用
     */
    public function getPendingApps(int $page = 1, int $limit = 20): array
    {
        $query = PlatformApp::where('status', PlatformApp::STATUS_PENDING);
        $total = $query->count();
        $list = $query->order('id', 'asc')->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }
}
