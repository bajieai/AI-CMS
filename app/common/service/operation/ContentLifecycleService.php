<?php
declare(strict_types=1);

namespace app\common\service\operation;

use app\common\model\Content;
use app\common\model\ContentArchive;
use think\facade\Cache;

class ContentLifecycleService
{
    private const CACHE_TAG = 'content_lifecycle';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_AUDIT = 'pending_audit';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_TOPPED = 'topped';
    public const STATUS_RECOMMENDED = 'recommended';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_DELETED = 'deleted';

    public function transition(int $contentId, string $toStatus): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'message' => '内容不存在'];
        $content->lifecycle_status = $toStatus;
        if ($toStatus === self::STATUS_PUBLISHED) $content->status = 1;
        elseif ($toStatus === self::STATUS_DELETED) $content->status = -1;
        $content->save();
        Cache::clear();
        return ['success' => true];
    }

    public function batchTransition(array $ids, string $toStatus): array
    {
        Content::whereIn('id', $ids)->update(['lifecycle_status' => $toStatus]);
        Cache::clear();
        return ['success' => true, 'count' => count($ids)];
    }

    public function archive(int $contentId, string $reason): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false];
        ContentArchive::create(['content_id' => $contentId, 'archived_by' => 0, 'archive_reason' => $reason, 'original_status' => $content->lifecycle_status ?? 'published', 'content_snapshot' => json_encode($content->toArray()), 'create_time' => time()]);
        $content->lifecycle_status = self::STATUS_ARCHIVED;
        $content->save();
        Cache::clear();
        return ['success' => true];
    }

    public function restore(int $contentId): array
    {
        $content = Content::find($contentId);
        if (!$content) return ['success' => false];
        $archive = ContentArchive::where('content_id', $contentId)->order('id', 'desc')->find();
        $restoreStatus = $archive ? $archive->original_status : self::STATUS_PUBLISHED;
        $content->lifecycle_status = $restoreStatus;
        $content->status = 1;
        $content->save();
        Cache::clear();
        return ['success' => true];
    }

    public function emptyTrash(): array
    {
        $deleted = Content::where('lifecycle_status', self::STATUS_DELETED)->delete();
        Cache::clear();
        return ['success' => true, 'count' => $deleted];
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            $statuses = [self::STATUS_DRAFT, self::STATUS_PENDING_AUDIT, self::STATUS_PUBLISHED, self::STATUS_SCHEDULED, self::STATUS_TOPPED, self::STATUS_RECOMMENDED, self::STATUS_EXPIRED, self::STATUS_ARCHIVED, self::STATUS_DELETED];
            $stats = [];
            foreach ($statuses as $status) {
                $stats[$status] = Content::where('lifecycle_status', $status)->count();
            }
            return $stats;
        }, 300);
    }
}
