<?php
declare(strict_types=1);

namespace app\common\service\content;

use think\facade\Db;
use think\facade\Cache;

/**
 * 内容关系服务 (V2.9.36 CM-3)
 *
 * 提供模型关系定义管理、内容关联CRUD、批量操作、搜索等
 * 缓存标签 content_relation
 */
class ContentRelationService
{
    private const CACHE_TAG = 'content_relation';
    private const TABLE     = 'content_relation';

    /**
     * 获取模型定义的关系列表
     */
    public function getRelations(int $modelId): array
    {
        try {
            return Cache::remember(
                'relations_model_' . $modelId,
                function () use ($modelId) {
                    return Db::name(self::TABLE)
                        ->whereOr([
                            ['source_model_id', '=', $modelId],
                            ['target_model_id', '=', $modelId],
                        ])
                        ->group('relation_name')
                        ->order('sort_order', 'asc')
                        ->select()
                        ->toArray();
                },
                600
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取关系定义详情
     */
    public function getRelationById(int $id): ?array
    {
        try {
            $row = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$row) {
                return null;
            }
            $row['relation_data'] = $this->decodeJson($row['relation_data'] ?? null);
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 创建关系定义
     */
    public function createRelation(array $data): array
    {
        try {
            if (empty($data['relation_name']) || empty($data['relation_label'])) {
                return ['code' => 1, 'msg' => '关系标识和标签不能为空', 'data' => null];
            }

            $insert = [
                'relation_name'      => trim($data['relation_name']),
                'relation_label'     => trim($data['relation_label']),
                'relation_type'      => $data['relation_type'] ?? 'related',
                'source_model_id'    => (int)($data['source_model_id'] ?? 0),
                'target_model_id'    => (int)($data['target_model_id'] ?? 0),
                'source_content_id'  => (int)($data['source_content_id'] ?? 0),
                'target_content_id'  => (int)($data['target_content_id'] ?? 0),
                'sort_order'         => (int)($data['sort_order'] ?? 0),
                'relation_data'      => json_encode($data['relation_data'] ?? [], JSON_UNESCAPED_UNICODE),
                'create_time'        => date('Y-m-d H:i:s'),
            ];

            $id = Db::name(self::TABLE)->insertGetId($insert);
            Cache::clear();

            return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '创建关系失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 删除关系定义
     */
    public function deleteRelation(int $id): array
    {
        try {
            $row = Db::name(self::TABLE)->where('id', $id)->find();
            if (!$row) {
                return ['code' => 1, 'msg' => '关系不存在', 'data' => null];
            }

            Db::name(self::TABLE)->where('id', $id)->delete();
            Cache::clear();

            return ['code' => 0, 'msg' => '删除成功', 'data' => null];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '删除关系失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 添加内容关联
     */
    public function addRelation(string $relationName, int $sourceContentId, int $targetContentId, array $extra = []): array
    {
        try {
            if ($sourceContentId <= 0 || $targetContentId <= 0 || $sourceContentId === $targetContentId) {
                return ['code' => 1, 'msg' => '参数无效', 'data' => null];
            }

            // 唯一性检查
            $exists = Db::name(self::TABLE)
                ->where('relation_name', $relationName)
                ->where('source_content_id', $sourceContentId)
                ->where('target_content_id', $targetContentId)
                ->find();
            if ($exists) {
                return ['code' => 1, 'msg' => '关联已存在', 'data' => null];
            }

            $insert = [
                'relation_name'     => $relationName,
                'relation_label'    => $extra['label'] ?? $relationName,
                'relation_type'     => $extra['type'] ?? 'related',
                'source_model_id'   => (int)($extra['source_model_id'] ?? 0),
                'target_model_id'   => (int)($extra['target_model_id'] ?? 0),
                'source_content_id' => $sourceContentId,
                'target_content_id' => $targetContentId,
                'sort_order'        => (int)($extra['sort_order'] ?? 0),
                'relation_data'     => json_encode($extra['data'] ?? [], JSON_UNESCAPED_UNICODE),
                'create_time'       => date('Y-m-d H:i:s'),
            ];

            $id = Db::name(self::TABLE)->insertGetId($insert);
            Cache::clear();

            return ['code' => 0, 'msg' => '添加关联成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '添加关联失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 移除关联
     */
    public function removeRelation(string $relationName, int $sourceContentId, int $targetContentId): array
    {
        try {
            $count = Db::name(self::TABLE)
                ->where('relation_name', $relationName)
                ->where('source_content_id', $sourceContentId)
                ->where('target_content_id', $targetContentId)
                ->delete();

            Cache::clear();

            return ['code' => 0, 'msg' => '移除关联成功', 'data' => ['affected' => $count]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '移除关联失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 获取关联内容列表
     */
    public function getRelatedContents(int $contentId, string $relationName, int $limit = 10): array
    {
        try {
            $cacheKey = 'related_' . $contentId . '_' . $relationName . '_' . $limit;
            return Cache::remember(
                $cacheKey,
                function () use ($contentId, $relationName, $limit) {
                    $relations = Db::name(self::TABLE)
                        ->where('relation_name', $relationName)
                        ->where(function ($q) use ($contentId) {
                            $q->where('source_content_id', $contentId)
                              ->whereOr('target_content_id', $contentId);
                        })
                        ->order('sort_order', 'asc')
                        ->limit($limit)
                        ->select()
                        ->toArray();

                    if (empty($relations)) {
                        return [];
                    }

                    // 获取关联内容详情
                    $contentIds = [];
                    foreach ($relations as $rel) {
                        $id = (int)$rel['source_content_id'] === $contentId
                            ? (int)$rel['target_content_id']
                            : (int)$rel['source_content_id'];
                        $contentIds[] = $id;
                    }
                    $contentIds = array_unique($contentIds);

                    $contents = Db::name('content')
                        ->whereIn('id', $contentIds)
                        ->where('status', 1)
                        ->column('id,title,cover,excerpt,create_time', 'id');

                    $result = [];
                    foreach ($relations as $rel) {
                        $targetId = (int)$rel['source_content_id'] === $contentId
                            ? (int)$rel['target_content_id']
                            : (int)$rel['source_content_id'];
                        if (isset($contents[$targetId])) {
                            $result[] = [
                                'relation' => $rel,
                                'content'  => $contents[$targetId],
                            ];
                        }
                    }
                    return $result;
                },
                600
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取关联数量
     */
    public function getRelationCount(int $contentId, string $relationName = ''): int
    {
        try {
            $query = Db::name(self::TABLE)
                ->where(function ($q) use ($contentId) {
                    $q->where('source_content_id', $contentId)
                      ->whereOr('target_content_id', $contentId);
                });
            if ($relationName !== '') {
                $query->where('relation_name', $relationName);
            }
            return (int)$query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 清理内容关联
     * P1审核修复：检查多对多关系，对于many_to_many类型只删除当前源→目标记录
     */
    public function cleanRelations(int $contentId): array
    {
        try {
            Db::startTrans();

            // 获取该内容所有关联记录
            $relations = Db::name(self::TABLE)
                ->where(function ($q) use ($contentId) {
                    $q->where('source_content_id', $contentId)
                      ->whereOr('target_content_id', $contentId);
                })
                ->select()
                ->toArray();

            $deletedCount = 0;
            foreach ($relations as $rel) {
                $relationType = $rel['relation_type'] ?? 'related';

                if ($relationType === 'many_to_many') {
                    // 对于多对多类型：只删除当前源→目标的记录
                    // 保留其他内容对同一目标的引用
                    Db::name(self::TABLE)->where('id', $rel['id'])->delete();
                    $deletedCount++;
                } else {
                    // 对于one_to_one/one_to_many等单向关系：
                    // 删除source或target为该contentId的记录
                    Db::name(self::TABLE)->where('id', $rel['id'])->delete();
                    $deletedCount++;
                }
            }

            Db::commit();
            Cache::clear();

            return ['code' => 0, 'msg' => '清理关联成功', 'data' => ['deleted' => $deletedCount]];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '清理关联失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 批量添加关联
     */
    public function batchAddRelations(string $relationName, int $sourceContentId, array $targetIds): array
    {
        try {
            if (empty($targetIds)) {
                return ['code' => 1, 'msg' => '目标ID列表为空', 'data' => null];
            }

            $successCount = 0;
            $failCount    = 0;
            $skipped      = 0;

            Db::startTrans();
            foreach ($targetIds as $targetId) {
                $targetId = (int)$targetId;
                if ($targetId <= 0 || $targetId === $sourceContentId) {
                    $failCount++;
                    continue;
                }

                $exists = Db::name(self::TABLE)
                    ->where('relation_name', $relationName)
                    ->where('source_content_id', $sourceContentId)
                    ->where('target_content_id', $targetId)
                    ->find();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                Db::name(self::TABLE)->insert([
                    'relation_name'     => $relationName,
                    'relation_label'   => $relationName,
                    'relation_type'     => 'related',
                    'source_model_id'   => 0,
                    'target_model_id'   => 0,
                    'source_content_id' => $sourceContentId,
                    'target_content_id' => $targetId,
                    'sort_order'        => 0,
                    'relation_data'     => '{}',
                    'create_time'       => date('Y-m-d H:i:s'),
                ]);
                $successCount++;
            }
            Db::commit();
            Cache::clear();

            return ['code' => 0, 'msg' => '批量添加完成', 'data' => [
                'success' => $successCount,
                'failed'  => $failCount,
                'skipped' => $skipped,
            ]];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '批量添加关联失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 搜索可关联的内容
     */
    public function searchContentsForRelation(string $keyword, int $modelId, int $limit = 20): array
    {
        try {
            $query = Db::name('content')
                ->where('status', 1)
                ->whereLike('title', "%{$keyword}%");

            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }

            return $query->field('id,title,cover,excerpt,create_time,model_id')
                ->order('id', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }

    /**
     * 解码JSON
     */
    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
