<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\content;

use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentModel;
use app\common\model\ContentModelField;
use app\common\model\ContentRelation;
use think\facade\Cache;
use think\facade\Db;

/**
 * V2.9.27 S-3: 类型化内容发布服务
 * 处理内容模型扩展数据的保存、读取和关系管理
 */
class TypeContentService
{
    /**
     * 保存内容扩展数据
     * @param int $contentId 内容ID
     * @param int $modelId 模型ID
     * @param array $extData 扩展数据
     * @return bool
     */
    public static function saveExtData(int $contentId, int $modelId, array $extData): bool
    {
        if ($contentId <= 0 || empty($extData)) {
            return false;
        }

        $ext = ContentExt::where('content_id', $contentId)->find();
        if ($ext) {
            $existingData = $ext->data ?? [];
            $ext->data = array_merge($existingData, $extData);
            $ext->type = $modelId;
            $ext->save();
        } else {
            ContentExt::create([
                'content_id' => $contentId,
                'type' => $modelId,
                'data' => $extData,
            ]);
        }

        return true;
    }

    /**
     * 获取内容扩展数据
     * @param int $contentId 内容ID
     * @return array
     */
    public static function getExtData(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $ext = ContentExt::where('content_id', $contentId)->find();
        if (!$ext || empty($ext->data)) {
            return [];
        }

        return $ext->data;
    }

    /**
     * 保存内容关系 (S-3e)
     * @param int $contentId 主内容ID
     * @param array $relationIds 关联内容ID数组
     * @param string $relationType 关系类型
     * @return int 保存数量
     */
    public static function saveRelations(int $contentId, array $relationIds, string $relationType = 'related'): int
    {
        if ($contentId <= 0 || empty($relationIds)) {
            return 0;
        }

        // 先删除该类型下的旧关系
        ContentRelation::where('content_id', $contentId)
            ->where('relation_type', $relationType)
            ->delete();

        $count = 0;
        $sort = 0;
        foreach ($relationIds as $rid) {
            $rid = (int)$rid;
            if ($rid <= 0 || $rid === $contentId) {
                continue;
            }
            ContentRelation::create([
                'content_id' => $contentId,
                'relation_id' => $rid,
                'relation_type' => $relationType,
                'sort' => $sort++,
                'create_time' => time(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * 获取内容关联列表 (S-3e)
     * @param int $contentId 内容ID
     * @param string $relationType 关系类型
     * @param int $limit 限制数量
     * @return array
     */
    public static function getRelations(int $contentId, string $relationType = 'related', int $limit = 10): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $cacheKey = 'content_relations_' . $contentId . '_' . $relationType . '_' . $limit;
        $cached = Cache::tag('content_relation')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $relations = ContentRelation::where('content_id', $contentId)
            ->where('relation_type', $relationType)
            ->order('sort', 'asc')
            ->limit($limit)
            ->select();

        $result = [];
        foreach ($relations as $rel) {
            $content = Content::find($rel->relation_id);
            if ($content && $content->status >= 0) {
                $result[] = $content;
            }
        }

        Cache::tag('content_relation')->set($cacheKey, $result, 600);
        return $result;
    }

    /**
     * 获取内容的所有关系类型
     */
    public static function getRelationTypes(): array
    {
        return [
            'related' => '相关内容',
            'previous_next' => '上下篇',
            'recommended' => '推荐内容',
            'similar' => '相似内容',
        ];
    }

    /**
     * 获取模型关联的扩展字段数据（含字段定义）
     * 用于前台展示时合并字段定义和数据
     * @param int $contentId 内容ID
     * @param int $modelId 模型ID
     * @return array
     */
    public static function getModelFieldsWithData(int $contentId, int $modelId): array
    {
        if ($modelId <= 0) {
            return [];
        }

        $fields = DynamicFormRenderer::getFields($modelId);
        if (empty($fields)) {
            return [];
        }

        $extData = self::getExtData($contentId);
        $result = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $value = $extData[$fieldName] ?? null;
            $result[] = [
                'field_def' => $field,
                'value' => $value,
                'formatted' => FieldTypeRegistry::formatValue($field, $value),
            ];
        }

        return $result;
    }

    /**
     * 批量为内容分配模型 (S-8 迁移工具用)
     * @param int $modelId 目标模型ID
     * @param int $type 内容类型
     * @param int $limit 每次处理数量
     * @return array ['total' => int, 'success' => int, 'fail' => int]
     */
    public static function batchAssignModel(int $modelId, int $type, int $limit = 100): array
    {
        $total = Content::where('type', $type)
            ->where(function ($query) use ($modelId) {
                $query->where('model_id', 0)->whereOr('model_id', null);
            })
            ->count();

        $contents = Content::where('type', $type)
            ->where(function ($query) use ($modelId) {
                $query->where('model_id', 0)->whereOr('model_id', null);
            })
            ->limit($limit)
            ->select();

        $success = 0;
        $fail = 0;
        foreach ($contents as $content) {
            try {
                $content->model_id = $modelId;
                $content->save();
                $success++;
            } catch (\Throwable) {
                $fail++;
            }
        }

        return ['total' => (int)$total, 'success' => $success, 'fail' => $fail];
    }

    /**
     * 清除内容关系缓存
     */
    public static function clearRelationCache(int $contentId): void
    {
        Cache::tag('content_relation')->clear();
    }
}
