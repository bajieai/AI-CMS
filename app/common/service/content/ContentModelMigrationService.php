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
use think\facade\Db;
use think\facade\Log;

/**
 * V2.9.27 S-8: 内容模型迁移工具
 */
class ContentModelMigrationService
{
    /**
     * 批量分配模型
     */
    public static function batchAssignModel(int $modelId, int $type, int $batchSize = 200): array
    {
        $result = ['total' => 0, 'success' => 0, 'fail' => 0, 'errors' => []];

        $model = ContentModel::find($modelId);
        if (!$model) {
            $result['errors'][] = '模型不存在: ' . $modelId;
            return $result;
        }

        $total = Content::where('type', $type)
            ->where(function ($query) {
                $query->where('model_id', 0)->whereOr('model_id', null);
            })
            ->count();
        $result['total'] = (int)$total;

        $offset = 0;
        while (true) {
            $contents = Content::where('type', $type)
                ->where(function ($query) {
                    $query->where('model_id', 0)->whereOr('model_id', null);
                })
                ->limit($batchSize)
                ->select();

            if ($contents->isEmpty()) {
                break;
            }

            foreach ($contents as $content) {
                try {
                    $content->model_id = $modelId;
                    $content->save();
                    $result['success']++;
                } catch (\Throwable $e) {
                    $result['fail']++;
                    $result['errors'][] = "内容ID {$content->id}: " . $e->getMessage();
                }
            }

            $offset += $batchSize;
            if (count($contents) < $batchSize) {
                break;
            }
        }

        self::logMigration($modelId, 'batch_assign', $result);
        return $result;
    }

    /**
     * 从类型导入模型
     */
    public static function importFromType(): array
    {
        $result = ['total' => 0, 'success' => 0, 'fail' => 0, 'errors' => []];

        $types = [1, 2, 3, 4, 5, 6];
        foreach ($types as $type) {
            $model = ContentModel::getDefaultByType($type);
            if (!$model) {
                continue;
            }

            $sub = self::batchAssignModel($model->id, $type);
            $result['total'] += $sub['total'];
            $result['success'] += $sub['success'];
            $result['fail'] += $sub['fail'];
            $result['errors'] = array_merge($result['errors'], $sub['errors']);
        }

        return $result;
    }

    /**
     * 初始化模型字段默认值
     */
    public static function initFields(int $modelId): array
    {
        $result = ['total' => 0, 'success' => 0, 'fail' => 0, 'errors' => []];

        $fields = DynamicFormRenderer::getFields($modelId);
        if (empty($fields)) {
            return $result;
        }

        $contents = Content::where('model_id', $modelId)->select();
        $result['total'] = $contents->count();

        foreach ($contents as $content) {
            try {
                $ext = ContentExt::where('content_id', $content->id)->find();
                if ($ext && !empty($ext->data)) {
                    $result['success']++;
                    continue;
                }

                $defaultData = [];
                foreach ($fields as $field) {
                    $defaultData[$field['name']] = $field['default_value'] ?? '';
                }

                if ($ext) {
                    $ext->data = $defaultData;
                    $ext->type = $modelId;
                    $ext->save();
                } else {
                    ContentExt::create([
                        'content_id' => $content->id,
                        'type' => $modelId,
                        'data' => $defaultData,
                    ]);
                }
                $result['success']++;
            } catch (\Throwable $e) {
                $result['fail']++;
                $result['errors'][] = "内容ID {$content->id}: " . $e->getMessage();
            }
        }

        self::logMigration($modelId, 'init_fields', $result);
        return $result;
    }

    /**
     * 获取迁移日志
     */
    public static function getMigrationLogs(int $limit = 20): array
    {
        return Db::name('content_model_migration_log')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 记录迁移日志
     */
    private static function logMigration(int $modelId, string $type, array $result): void
    {
        try {
            Db::name('content_model_migration_log')->insert([
                'model_id' => $modelId,
                'migration_type' => $type,
                'total_count' => $result['total'],
                'success_count' => $result['success'],
                'fail_count' => $result['fail'],
                'error_detail' => json_encode(array_slice($result['errors'], 0, 20), JSON_UNESCAPED_UNICODE),
                'operator' => session('admin.username') ?? 'system',
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            Log::error('迁移日志写入失败: ' . $e->getMessage());
        }
    }
}
