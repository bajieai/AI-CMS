<?php
declare(strict_types=1);

namespace app\common\service\theme;

use app\common\model\ThemeInfo;
use think\facade\Cache;

/**
 * 主题版本检测服务 - V3.1 Sprint 16
 *
 * 功能：
 * 1. 本地已安装主题 vs 远程最新版本比对
 * 2. 红点通知（非弹窗，返回count+badge）
 * 3. 支持单个主题检测和批量检测
 * 4. 24小时缓存避免频繁请求
 */
class ThemeUpdateService
{
    /**
     * 缓存键前缀
     */
    protected const CACHE_PREFIX = 'theme_update_check_';
    protected const CACHE_TTL = 86400; // 24小时

    /**
     * 批量检测所有已安装主题的更新
     *
     * @return array ['has_update'=>bool, 'count'=>int, 'themes'=>[...]]
     */
    public function checkAll(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $installed = ThemeInfo::where('is_installed', 1)->select()->toArray();
        if (empty($installed)) {
            return ['has_update' => false, 'count' => 0, 'themes' => []];
        }

        $remoteSource = new RemoteTemplateSource();
        $remoteResult = $remoteSource->fetchTemplateList();
        $remoteMap = [];
        foreach ($remoteResult['templates'] as $t) {
            $remoteMap[$t['code']] = $t;
        }

        $updates = [];
        foreach ($installed as $theme) {
            if (!isset($remoteMap[$theme['code']])) {
                continue;
            }
            $latest = $remoteMap[$theme['code']]['version'] ?? '0.0.0';
            if (version_compare($latest, $theme['version'] ?? '0.0.0', '>')) {
                $updates[] = [
                    'id'          => $theme['id'],
                    'code'        => $theme['code'],
                    'name'        => $theme['name'],
                    'type'        => $theme['type'],
                    'current'     => $theme['version'] ?? '0.0.0',
                    'latest'      => $latest,
                    'download_url'=> $remoteMap[$theme['code']]['download_url'] ?? '',
                ];
            }
        }

        $result = [
            'has_update' => !empty($updates),
            'count'      => count($updates),
            'themes'     => $updates,
            'checked_at' => time(),
        ];

        Cache::set($cacheKey, $result, self::CACHE_TTL);
        return $result;
    }

    /**
     * 检测单个主题是否有更新
     */
    public function checkOne(string $code): array
    {
        $theme = ThemeInfo::where('code', $code)->where('is_installed', 1)->find();
        if (!$theme) {
            return ['has_update' => false, 'current' => '0.0.0', 'latest' => '0.0.0'];
        }

        $cacheKey = self::CACHE_PREFIX . $code;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $remoteSource = new RemoteTemplateSource();
        $remoteResult = $remoteSource->fetchTemplateList();

        $latest = '0.0.0';
        $downloadUrl = '';
        foreach ($remoteResult['templates'] as $t) {
            if ($t['code'] === $code) {
                $latest = $t['version'] ?? '0.0.0';
                $downloadUrl = $t['download_url'] ?? '';
                break;
            }
        }

        $hasUpdate = version_compare($latest, $theme->version ?? '0.0.0', '>');
        $result = [
            'has_update'   => $hasUpdate,
            'current'      => $theme->version ?? '0.0.0',
            'latest'       => $latest,
            'download_url' => $downloadUrl,
        ];

        Cache::set($cacheKey, $result, self::CACHE_TTL);
        return $result;
    }

    /**
     * 清除更新检测缓存（安装/更新后调用）
     */
    public function clearCache(string $code = ''): void
    {
        if ($code) {
            Cache::delete(self::CACHE_PREFIX . $code);
        }
        Cache::delete(self::CACHE_PREFIX . 'all');
    }

    /**
     * 获取后台顶部导航的红点通知数据
     * 供JS轮询或页面渲染时调用
     */
    public function getBadge(): array
    {
        $result = $this->checkAll();
        return [
            'show'   => $result['has_update'],
            'count'  => $result['count'],
            'themes' => array_column($result['themes'], 'name'),
        ];
    }
}
