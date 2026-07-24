<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiEditorSnapshot;

/**
 * AI编辑器版本快照服务 — V2.9.28 A-7
 * Q6: 全量快照，50版本上限，content_hash字段
 */
class AiEditorSnapshotService
{
    /**
     * 创建快照
     */
    public function createSnapshot(int $contentId, int $userId, string $content, string $operationType = '', string $operationDesc = ''): array
    {
        return AiEditorSnapshot::createSnapshot($contentId, $userId, $content, $operationType, $operationDesc);
    }

    /**
     * 获取版本列表
     */
    public function getVersions(int $contentId): array
    {
        return AiEditorSnapshot::getVersions($contentId);
    }

    /**
     * 获取指定版本
     */
    public function getVersion(int $contentId, int $version): ?array
    {
        $snapshot = AiEditorSnapshot::where('content_id', $contentId)
            ->where('version', $version)
            ->find();
        return $snapshot ? $snapshot->toArray() : null;
    }

    /**
     * 版本对比
     */
    public function diff(int $contentId, int $version1, int $version2): array
    {
        $v1 = $this->getVersion($contentId, $version1);
        $v2 = $this->getVersion($contentId, $version2);

        if (!$v1 || !$v2) {
            return ['success' => false, 'message' => '版本不存在'];
        }

        return [
            'success' => true,
            'v1' => $v1,
            'v2' => $v2,
            'diff' => $this->computeDiff($v1['content'], $v2['content']),
        ];
    }

    /**
     * 回滚到指定版本
     */
    public function rollback(int $contentId, int $version, int $userId): array
    {
        $snapshot = $this->getVersion($contentId, $version);
        if (!$snapshot) {
            return ['success' => false, 'message' => '版本不存在'];
        }

        // 创建回滚操作快照
        $this->createSnapshot(
            $contentId, $userId,
            $snapshot['content'],
            'rollback',
            "回滚到版本 {$version}"
        );

        return [
            'success' => true,
            'message' => "已回滚到版本 {$version}",
            'content' => $snapshot['content'],
        ];
    }

    /**
     * 计算文本差异（简化版行级diff）
     */
    private function computeDiff(string $text1, string $text2): array
    {
        $lines1 = explode("\n", $text1);
        $lines2 = explode("\n", $text2);

        $diff = [];
        $maxLen = max(count($lines1), count($lines2));

        for ($i = 0; $i < $maxLen; $i++) {
            $l1 = $lines1[$i] ?? '';
            $l2 = $lines2[$i] ?? '';

            if ($l1 === $l2) {
                $diff[] = ['type' => 'same', 'content' => $l1];
            } else {
                if ($l1 !== '') $diff[] = ['type' => 'removed', 'content' => $l1];
                if ($l2 !== '') $diff[] = ['type' => 'added', 'content' => $l2];
            }
        }

        return $diff;
    }
}
