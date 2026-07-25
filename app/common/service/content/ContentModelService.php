<?php
declare(strict_types=1);

namespace app\common\service\content;

use think\facade\Db;
use think\facade\Cache;

/**
 * 内容模型管理服务 (V2.9.36 CM-2)
 *
 * 提供模型CRUD、启用/禁用、表单设计器布局存储等核心逻辑
 * 所有查询使用 Cache::tag('content_model') 缓存
 */
class ContentModelService
{
    private const CACHE_TAG  = 'content_model';
    private const CACHE_TTL  = 1800; // 30分钟
    private const TABLE      = 'content_model';

    /**
     * 模型分页列表
     */
    public function getModelList(int $page = 1, int $pageSize = 20, array $filter = []): array
    {
        try {
            $query = Db::name(self::TABLE)->where('is_deleted', 0);

            if (!empty($filter['keyword'])) {
                $kw = trim($filter['keyword']);
                $query->where(function ($q) use ($kw) {
                    $q->whereLike('model_name', "%{$kw}%")
                      ->whereOr('model_identifier', 'like', "%{$kw}%");
                });
            }
            if (isset($filter['is_enabled']) && $filter['is_enabled'] !== '') {
                $query->where('is_enabled', (int)$filter['is_enabled']);
            }
            if (isset($filter['is_system']) && $filter['is_system'] !== '') {
                $query->where('is_system', (int)$filter['is_system']);
            }
            if (!empty($filter['group_id'])) {
                $query->where('group_id', (int)$filter['group_id']);
            }

            $total = $query->count();
            $list  = $query->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();

            // 解码JSON字段
            foreach ($list as &$item) {
                $item['model_config']    = $this->decodeJson($item['model_config'] ?? null);
                $item['template_config'] = $this->decodeJson($item['template_config'] ?? null);
            }

            return ['code' => 0, 'msg' => 'ok', 'data' => [
                'list'  => $list,
                'total' => $total,
                'page'  => $page,
                'page_size' => $pageSize,
            ]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '获取模型列表失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 获取模型详情
     */
    public function getModelById(int $id): ?array
    {
        try {
            $row = Db::name(self::TABLE)->where('id', $id)->where('is_deleted', 0)->find();
            if (!$row) {
                return null;
            }
            $row['model_config']    = $this->decodeJson($row['model_config'] ?? null);
            $row['template_config'] = $this->decodeJson($row['template_config'] ?? null);
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 创建模型
     */
    public function createModel(array $data): array
    {
        try {
            // 标识唯一性检查
            $exists = Db::name(self::TABLE)
                ->where('model_identifier', $data['model_identifier'] ?? '')
                ->where('is_deleted', 0)
                ->find();
            if ($exists) {
                return ['code' => 1, 'msg' => '模型标识已存在', 'data' => null];
            }

            $insert = [
                'model_name'        => trim($data['model_name'] ?? ''),
                'model_identifier'  => trim($data['model_identifier'] ?? ''),
                'model_description' => $data['model_description'] ?? '',
                'model_icon'        => $data['model_icon'] ?? 'bi bi-file-text',
                'model_config'      => json_encode($data['model_config'] ?? [], JSON_UNESCAPED_UNICODE),
                'template_config'   => json_encode($data['template_config'] ?? [], JSON_UNESCAPED_UNICODE),
                'url_rule'          => $data['url_rule'] ?? '',
                'group_id'          => (int)($data['group_id'] ?? 0),
                'sort_order'        => (int)($data['sort_order'] ?? 0),
                'is_system'         => 0,
                'is_enabled'        => isset($data['is_enabled']) ? (int)$data['is_enabled'] : 1,
                'is_deleted'        => 0,
                'create_time'       => date('Y-m-d H:i:s'),
                'update_time'       => date('Y-m-d H:i:s'),
            ];

            if (empty($insert['model_name']) || empty($insert['model_identifier'])) {
                return ['code' => 1, 'msg' => '模型名称和标识不能为空', 'data' => null];
            }

            $id = Db::name(self::TABLE)->insertGetId($insert);
            Cache::clear();

            return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '创建模型失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 更新模型
     */
    public function updateModel(int $id, array $data): array
    {
        try {
            $model = Db::name(self::TABLE)->where('id', $id)->where('is_deleted', 0)->find();
            if (!$model) {
                return ['code' => 1, 'msg' => '模型不存在', 'data' => null];
            }

            $update = [];
            $strFields = ['model_name', 'model_description', 'model_icon', 'url_rule'];
            foreach ($strFields as $f) {
                if (isset($data[$f])) {
                    $update[$f] = $data[$f];
                }
            }
            if (isset($data['model_identifier'])) {
                // 系统模型不允许改标识
                if ((int)$model['is_system'] === 1) {
                    return ['code' => 1, 'msg' => '系统预设模型不允许修改标识', 'data' => null];
                }
                $update['model_identifier'] = $data['model_identifier'];
            }
            if (isset($data['model_config'])) {
                $update['model_config'] = json_encode($data['model_config'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['template_config'])) {
                $update['template_config'] = json_encode($data['template_config'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['group_id'])) {
                $update['group_id'] = (int)$data['group_id'];
            }
            if (isset($data['sort_order'])) {
                $update['sort_order'] = (int)$data['sort_order'];
            }
            if (isset($data['is_enabled'])) {
                $update['is_enabled'] = (int)$data['is_enabled'];
            }
            $update['update_time'] = date('Y-m-d H:i:s');

            if (empty($update)) {
                return ['code' => 0, 'msg' => '无更新数据', 'data' => null];
            }

            Db::name(self::TABLE)->where('id', $id)->update($update);
            Cache::clear();

            return ['code' => 0, 'msg' => '更新成功', 'data' => ['id' => $id]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '更新模型失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 删除模型（软删除 is_deleted=1）
     */
    public function deleteModel(int $id): array
    {
        try {
            $model = Db::name(self::TABLE)->where('id', $id)->where('is_deleted', 0)->find();
            if (!$model) {
                return ['code' => 1, 'msg' => '模型不存在', 'data' => null];
            }
            if ((int)$model['is_system'] === 1) {
                return ['code' => 1, 'msg' => '系统预设模型不可删除', 'data' => null];
            }

            Db::name(self::TABLE)->where('id', $id)->update([
                'is_deleted'  => 1,
                'is_enabled'  => 0,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Cache::clear();

            return ['code' => 0, 'msg' => '删除成功', 'data' => null];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '删除模型失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 启用/禁用模型
     */
    public function toggleModel(int $id): array
    {
        try {
            $model = Db::name(self::TABLE)->where('id', $id)->where('is_deleted', 0)->find();
            if (!$model) {
                return ['code' => 1, 'msg' => '模型不存在', 'data' => null];
            }

            $newStatus = (int)$model['is_enabled'] === 1 ? 0 : 1;
            Db::name(self::TABLE)->where('id', $id)->update([
                'is_enabled'  => $newStatus,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            Cache::clear();

            return ['code' => 0, 'msg' => $newStatus ? '已启用' : '已禁用', 'data' => ['is_enabled' => $newStatus]];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '操作失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 获取启用的模型列表（缓存30分钟）
     */
    public function getEnabledModels(): array
    {
        return Cache::remember(
            'enabled_models',
            function () {
                return Db::name(self::TABLE)
                    ->where('is_enabled', 1)
                    ->where('is_deleted', 0)
                    ->order('sort_order', 'asc')
                    ->order('id', 'asc')
                    ->select()
                    ->toArray();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 根据标识获取模型
     */
    public function getByIdentifier(string $identifier): ?array
    {
        try {
            $row = Db::name(self::TABLE)
                ->where('model_identifier', $identifier)
                ->where('is_deleted', 0)
                ->find();
            if (!$row) {
                return null;
            }
            $row['model_config']    = $this->decodeJson($row['model_config'] ?? null);
            $row['template_config'] = $this->decodeJson($row['template_config'] ?? null);
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 保存表单设计器布局（存入model_config JSON字段）
     */
    public function saveDesign(int $modelId, array $layout): array
    {
        try {
            $model = Db::name(self::TABLE)->where('id', $modelId)->where('is_deleted', 0)->find();
            if (!$model) {
                return ['code' => 1, 'msg' => '模型不存在', 'data' => null];
            }

            $config = $this->decodeJson($model['model_config'] ?? null);
            $config['form_design'] = $layout;

            Db::name(self::TABLE)->where('id', $modelId)->update([
                'model_config' => json_encode($config, JSON_UNESCAPED_UNICODE),
                'update_time'  => date('Y-m-d H:i:s'),
            ]);
            Cache::clear();

            return ['code' => 0, 'msg' => '保存成功', 'data' => null];
        } catch (\Throwable $e) {
            return ['code' => 1, 'msg' => '保存设计布局失败: ' . $e->getMessage(), 'data' => null];
        }
    }

    /**
     * 获取表单设计器布局
     */
    public function getDesign(int $modelId): array
    {
        $model = $this->getModelById($modelId);
        if (!$model) {
            return [];
        }
        $config = $model['model_config'] ?? [];
        if (!is_array($config)) {
            $config = $this->decodeJson($config);
        }
        return $config['form_design'] ?? [];
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }

    /**
     * 解码JSON字段
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
