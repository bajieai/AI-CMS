<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI编辑器版本快照模型 — V2.9.28 A-7
 * Q6: 全量快照，50版本上限，content_hash字段
 */
class AiEditorSnapshot extends Model
{
    protected $name = 'ai_editor_snapshot';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    const MAX_VERSIONS = 50; // 每篇内容最多保留50个版本

    /**
     * 创建快照
     */
    public static function createSnapshot(int $contentId, int $userId, string $content, string $operationType = '', string $operationDesc = ''): array
    {
        // 获取当前最大版本号
        $maxVersion = self::where('content_id', $contentId)->max('version') ?? 0;
        $newVersion = $maxVersion + 1;

        $snapshot = self::create([
            'content_id' => $contentId,
            'user_id' => $userId,
            'version' => $newVersion,
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'operation_type' => $operationType,
            'operation_desc' => $operationDesc,
            'create_time' => time(),
        ]);

        // 清理超过上限的旧版本
        $total = self::where('content_id', $contentId)->count();
        if ($total > self::MAX_VERSIONS) {
            $oldVersions = self::where('content_id', $contentId)
                ->order('version', 'asc')
                ->limit($total - self::MAX_VERSIONS)
                ->column('id');
            if (!empty($oldVersions)) {
                self::whereIn('id', $oldVersions)->delete();
            }
        }

        return ['success' => true, 'version' => $newVersion, 'snapshot_id' => $snapshot->id];
    }

    /**
     * 获取版本列表
     */
    public static function getVersions(int $contentId, int $limit = 50): array
    {
        return self::where('content_id', $contentId)
            ->order('version', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
