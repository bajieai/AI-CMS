<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use app\common\model\PublishLog;
use app\common\service\publish\PublishPlatformInterface;

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
     * 重试失败的发布
     */
    public static function retryPublish(int $logId): array
    {
        $log = PublishLog::find($logId);
        if (!$log || $log->status !== 2) {
            throw new \Exception('记录不存在或非失败状态');
        }

        $log->status = 0;
        $log->error_msg = '';
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
}
