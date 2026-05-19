<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use app\common\model\PublishLog;
use app\common\service\publish\PublishPlatformInterface;
use think\facade\Config;
use think\facade\Log;

/**
 * 多平台发布服务 - V2.5
 * 门面模式：通过接口调度各平台适配器
 */
class PublishPlatformService
{
    /**
     * 已注册的平台适配器
     * @var array<string, PublishPlatformInterface>
     */
    protected static array $adapters = [];

    /**
     * 注册平台适配器
     */
    public static function registerAdapter(PublishPlatformInterface $adapter): void
    {
        self::$adapters[$adapter->getName()] = $adapter;
    }

    /**
     * 获取平台适配器
     */
    public static function getAdapter(string $platformName): ?PublishPlatformInterface
    {
        if (empty(self::$adapters)) {
            self::bootAdapters();
        }
        return self::$adapters[$platformName] ?? null;
    }

    /**
     * 初始化内置适配器
     */
    protected static function bootAdapters(): void
    {
        self::registerAdapter(new \app\common\service\publish\WechatMpPlatform());
        self::registerAdapter(new \app\common\service\publish\ToutiaoPlatform());
        self::registerAdapter(new \app\common\service\publish\ZhihuPlatform());
    }

    /**
     * 发布内容到指定平台
     */
    public static function publish(int $contentId, int $platformId): array
    {
        $content = Content::find($contentId);
        if (!$content) throw new \Exception('内容不存在');

        $platform = PublishPlatform::find($platformId);
        if (!$platform || !$platform->is_enabled) throw new \Exception('平台未配置或已禁用');

        $adapter = self::getAdapter($platform->name);
        if (!$adapter) throw new \Exception("不支持的平台: {$platform->name}");

        // 创建发布记录
        $log = PublishLog::create([
            'content_id' => $contentId,
            'platform_id' => $platformId,
            'platform' => $platform->name,
            'action' => 'publish',
            'status' => 0,
        ]);

        try {
            $result = $adapter->publish($content, $platform);

            $log->status = 1;
            $log->platform_content_id = $result['media_id'] ?? $result['article_id'] ?? '';
            $log->publish_time = time();
            $log->save();

            return ['success' => true, 'log_id' => $log->id];
        } catch (\Exception $e) {
            $log->status = 2;
            $log->error_msg = mb_substr($e->getMessage(), 0, 500);
            $log->save();

            return ['success' => false, 'log_id' => $log->id, 'error' => $e->getMessage()];
        }
    }

    /**
     * 批量发布到多平台
     */
    public static function publishToPlatforms(int $contentId, array $platformIds): array
    {
        $results = [];
        foreach ($platformIds as $platformId) {
            $results[$platformId] = self::publish($contentId, (int) $platformId);
        }
        return $results;
    }

    /**
     * 重试失败的发布（V2.9.4增强：记录重试次数）
     */
    public static function retryPublish(int $logId): array
    {
        $log = PublishLog::find($logId);
        if (!$log || $log->status !== 2) {
            throw new \Exception('记录不存在或非失败状态');
        }

        $log->status = 0;
        $log->error_msg = '';
        $log->retry_count = ($log->retry_count ?? 0) + 1;
        $log->action = 'retry';
        $log->save();

        return self::publish($log->content_id, $log->platform_id);
    }

    /**
     * 获取发布记录列表
     */
    public static function getPublishLogs(int $contentId = 0, int $page = 1, int $limit = 20): array
    {
        $query = PublishLog::with(['platform'])->order('id', 'desc');
        if ($contentId > 0) $query->where('content_id', $contentId);
        return $query->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取平台列表
     */
    public static function getPlatforms(): array
    {
        return PublishPlatform::order('id', 'asc')->select()->toArray();
    }

    /**
     * 更新平台配置
     */
    public static function updatePlatform(int $id, array $data): bool
    {
        $platform = PublishPlatform::find($id);
        if (!$platform) throw new \Exception('平台不存在');

        $platform->save($data);
        return true;
    }

    /**
     * 获取所有已注册适配器的配置字段定义
     */
    public static function getAdapterConfigFields(): array
    {
        if (empty(self::$adapters)) {
            self::bootAdapters();
        }

        $fields = [];
        foreach (self::$adapters as $name => $adapter) {
            $fields[$name] = $adapter->getConfigFields();
        }
        return $fields;
    }

    /**
     * V2.9.3 M28: 内容保存后自动发布到已启用的平台
     * 非阻塞：失败仅记录日志，不影响主流程
     */
    public static function autoPublishToPlatforms(int $contentId): void
    {
        try {
            $enabled = Config::get('publish.auto_sync_enabled', 0);
            if (!$enabled) {
                return;
            }

            $platforms = PublishPlatform::where('is_enabled', 1)->select();
            if ($platforms->isEmpty()) {
                return;
            }

            foreach ($platforms as $platform) {
                try {
                    $adapter = self::getAdapter($platform->name);
                    if (!$adapter) {
                        continue;
                    }
                    // 验证配置完整性
                    if (!$adapter->validateConfig($platform)) {
                        continue;
                    }
                    // 异步发布（不阻塞）
                    self::publish($contentId, $platform->id);
                } catch (\Throwable $e) {
                    Log::warning("[AutoPublish] 平台 {$platform->name} 同步失败 content_id={$contentId}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[AutoPublish] 自动分发失败 content_id={$contentId}: " . $e->getMessage());
        }
    }

    /**
     * V2.9.4: 获取发布摘要统计
     */
    public static function getPublishSummary(): array
    {
        $stats = [];

        // 按平台统计
        $platformStats = PublishLog::field('platform, status, COUNT(*) as cnt')
            ->where('platform', '<>', '')
            ->group('platform, status')
            ->select();

        $platforms = [];
        foreach ($platformStats as $stat) {
            $name = $stat->platform;
            if (!isset($platforms[$name])) {
                $platforms[$name] = ['total' => 0, 'success' => 0, 'failed' => 0, 'pending' => 0, 'success_rate' => '0%'];
            }
            $platforms[$name]['total'] += $stat->cnt;
            if ($stat->status == 1) $platforms[$name]['success'] = $stat->cnt;
            if ($stat->status == 2) $platforms[$name]['failed'] = $stat->cnt;
            if ($stat->status == 0) $platforms[$name]['pending'] = $stat->cnt;
        }
        foreach ($platforms as &$p) {
            $p['success_rate'] = $p['total'] > 0 ? round($p['success'] / $p['total'] * 100, 1) . '%' : '0%';
        }

        // 兼容旧数据（无platform字段的）
        $legacyStats = PublishLog::field('status, COUNT(*) as cnt')
            ->where(function ($q) {
                $q->whereNull('platform')->whereOr('platform', '');
            })
            ->group('status')
            ->select();

        $legacyTotal = 0;
        foreach ($legacyStats as $stat) {
            $legacyTotal += $stat->cnt;
        }

        // 全局统计
        $total = PublishLog::count();
        $success = PublishLog::where('status', 1)->count();
        $failed = PublishLog::where('status', 2)->count();
        $pending = PublishLog::where('status', 0)->count();

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round($success / $total * 100, 1) . '%' : '0%',
            'platforms' => $platforms,
            'legacy_count' => $legacyTotal,
        ];
    }

    /**
     * V2.9.4: 获取发布日志列表（支持筛选+分页）
     */
    public static function getPublishLogList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = PublishLog::with(['content' => function ($q) {
            $q->field('id,title');
        }])->order('id', 'desc');

        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['content_id'])) {
            $query->where('content_id', (int) $filters['content_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('create_time', '>=', strtotime($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $query->where('create_time', '<=', strtotime($filters['date_to'] . ' 23:59:59'));
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select();

        return [
            'list' => $list->toArray(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * V2.9.3 M28: 刷新所有平台的Token
     * 目前主要支持头条号OAuth Token刷新
     */
    public static function refreshAllTokens(): array
    {
        if (empty(self::$adapters)) {
            self::bootAdapters();
        }

        $platforms = PublishPlatform::where('is_enabled', 1)->select();
        $results = [];

        foreach ($platforms as $platform) {
            $adapter = self::$adapters[$platform->name] ?? null;
            if (!$adapter) {
                continue;
            }

            try {
                // 头条号有独立的Token刷新逻辑
                if ($platform->name === 'toutiao' && method_exists($adapter, 'getValidAccessToken')) {
                    $token = $adapter->getValidAccessToken($platform);
                    $results[$platform->name] = ['success' => true, 'token_preview' => substr($token, 0, 8) . '...'];
                } else {
                    $results[$platform->name] = ['success' => true, 'msg' => '无需刷新'];
                }
            } catch (\Throwable $e) {
                $results[$platform->name] = ['success' => false, 'error' => $e->getMessage()];
                Log::warning("[TokenRefresh] {$platform->name} 刷新失败: " . $e->getMessage());
            }
        }

        return $results;
    }
}
