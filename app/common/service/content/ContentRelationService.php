<?php
declare(strict_types=1);
namespace app\common\service\content;

use app\common\model\ContentRelation;
use app\common\model\Content;
use think\facade\Cache;

/**
 * 内容关系管理服务 (V2.9.29 I-1)
 * 手动关联+AI自动关联+vis.js网络图数据
 */
class ContentRelationService
{
    private const CACHE_TAG = 'content_relation';

    /**
     * 添加关联关系
     */
    public function addRelation(int $sourceId, int $targetId, string $type = 'related', float $weight = 1.0, bool $isManual = true): bool
    {
        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) return false;

        $existing = ContentRelation::where('content_id', $sourceId)
            ->where('relation_id', $targetId)
            ->where('relation_type', $type)
            ->find();

        if ($existing) return false;

        ContentRelation::create([
            'content_id' => $sourceId,
            'relation_id' => $targetId,
            'relation_type' => $type,
            'relation_weight' => $weight,
            'is_manual' => $isManual ? 1 : 0,
            'sort' => 0,
            'create_time' => time(),
        ]);

        Cache::tag(self::CACHE_TAG)->clear();
        return true;
    }

    /**
     * 删除关联
     */
    public function removeRelation(int $sourceId, int $targetId, string $type = 'related'): bool
    {
        $result = ContentRelation::where('content_id', $sourceId)
            ->where('relation_id', $targetId)
            ->where('relation_type', $type)
            ->delete();

        Cache::tag(self::CACHE_TAG)->clear();
        return $result > 0;
    }

    /**
     * 获取内容的关联列表
     */
    public function getRelations(int $contentId, string $type = '', int $limit = 10): array
    {
        $cacheKey = 'relations_' . $contentId . '_' . $type . '_' . $limit;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function () use ($contentId, $type, $limit) {
            $query = ContentRelation::where('content_id', $contentId);
            if ($type) $query->where('relation_type', $type);
            $relations = $query->order('relation_weight', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            // 批量获取关联内容
            $contentIds = array_column($relations, 'relation_id');
            if (empty($contentIds)) return [];

            $contents = Content::whereIn('id', $contentIds)
                ->where('status', 1)
                ->column('id,title,cover,excerpt,create_time', 'id');

            $result = [];
            foreach ($relations as $rel) {
                if (isset($contents[$rel['relation_id']])) {
                    $result[] = array_merge($rel, ['content' => $contents[$rel['relation_id']]]);
                }
            }
            return $result;
        }, 3600);
    }

    /**
     * 获取网络图数据（vis.js格式）
     */
    public function getNetworkData(int $contentId, int $depth = 2): array
    {
        $nodes = [];
        $edges = [];
        $visited = [];

        $this->buildNetwork($contentId, $depth, $nodes, $edges, $visited);

        return [
            'nodes' => array_values($nodes),
            'edges' => array_values($edges),
        ];
    }

    private function buildNetwork(int $contentId, int $depth, array &$nodes, array &$edges, array &$visited): void
    {
        if ($depth <= 0 || isset($visited[$contentId])) return;
        $visited[$contentId] = true;

        $content = Content::find($contentId);
        if (!$content) return;

        if (!isset($nodes[$contentId])) {
            $color = $this->getNodeColor($content->type ?? 3);
            $nodes[$contentId] = [
                'id' => $contentId,
                'label' => mb_substr($content->title, 0, 20),
                'color' => $color,
                'shape' => 'dot',
                'size' => $depth === 2 ? 20 : 15,
            ];
        }

        $relations = ContentRelation::where('content_id', $contentId)
            ->limit(10)
            ->select();

        foreach ($relations as $rel) {
            $edgeId = $contentId . '-' . $rel['relation_id'];
            if (!isset($edges[$edgeId])) {
                $edges[$edgeId] = [
                    'from' => $contentId,
                    'to' => $rel['relation_id'],
                    'label' => $rel['relation_type'],
                    'arrows' => 'to',
                ];
            }
            $this->buildNetwork($rel['relation_id'], $depth - 1, $nodes, $edges, $visited);
        }
    }

    private function getNodeColor(int $type): string
    {
        $colors = [1 => '#FF6B6B', 2 => '#4ECDC4', 3 => '#45B7D1', 4 => '#96CEB4', 5 => '#FFEAA7', 6 => '#DDA0DD'];
        return $colors[$type] ?? '#95A5A6';
    }
}
