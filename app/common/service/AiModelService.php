<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiModel;

/**
 * AI模型管理服务
 */
class AiModelService
{
    /**
     * 获取所有模型列表
     */
    public static function getList(): array
    {
        return AiModel::order('sort', 'asc')->select()->toArray();
    }

    /**
     * 获取已启用的模型列表
     */
    public static function getEnabledList(): array
    {
        return AiModel::where('is_enabled', 1)->order('sort', 'asc')->select()->toArray();
    }

    /**
     * 创建或更新模型
     */
    public static function save(array $data): AiModel
    {
        if (!empty($data['id'])) {
            $model = AiModel::find($data['id']);
            if (!$model) {
                throw new \Exception('模型不存在');
            }
        } else {
            $model = new AiModel();
        }

        // 如果设为默认，先取消其他默认
        if (!empty($data['is_default'])) {
            AiModel::where('is_default', 1)->update(['is_default' => 0]);
        }

        $model->save($data);
        return $model;
    }

    /**
     * 删除模型
     */
    public static function delete(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }
        if ($model->is_default) {
            throw new \Exception('不能删除默认模型');
        }
        return $model->delete();
    }

    /**
     * 设置默认模型
     */
    public static function setDefault(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model || !$model->is_enabled) {
            throw new \Exception('模型不存在或未启用');
        }

        AiModel::where('is_default', 1)->update(['is_default' => 0]);
        $model->is_default = 1;
        return $model->save();
    }

    /**
     * 切换启用状态
     */
    public static function toggleEnabled(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }
        if ($model->is_default && $model->is_enabled) {
            throw new \Exception('不能禁用默认模型');
        }
        $model->is_enabled = $model->is_enabled ? 0 : 1;
        return $model->save();
    }

    /**
     * 测试模型连接
     */
    public static function testConnection(int $id): array
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }

        $providerClass = "\\app\\common\\service\\ai\\" . ucfirst($model->provider) . "Provider";
        if (!class_exists($providerClass)) {
            return ['success' => false, 'message' => "Provider {$model->provider} 不存在"];
        }

        try {
            $provider = new $providerClass($model);
            $result = $provider->write('你好，请回复"连接成功"');
            return [
                'success' => true,
                'message' => '连接成功',
                'response' => mb_substr($result, 0, 100),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '连接失败: ' . $e->getMessage(),
            ];
        }
    }
}
