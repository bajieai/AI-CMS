<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI模型配置模型
 */
class AiModel extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_ai_models';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     */
    protected $type = [
        'status' => 'integer',
        'is_default' => 'integer',
        'max_tokens' => 'integer',
        'input_price' => 'float',
        'output_price' => 'float',
        'sort' => 'integer',
    ];

    /**
     * 获取默认模型
     */
    public static function getDefault(): ?AiModel
    {
        return self::where('is_default', '=', 1)
            ->where('status', '=', 1)
            ->find();
    }

    /**
     * 获取启用的模型列表
     */
    public static function getEnabledModels(): array
    {
        return self::where('status', '=', 1)
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取模型选择列表
     */
    public static function getSelectList(): array
    {
        $models = self::getEnabledModels();
        $result = [];
        
        foreach ($models as $model) {
            // Handle both array and object format
            $modelData = is_array($model) ? $model : $model->toArray();
            $result[] = [
                'id' => $modelData['id'] ?? '',
                'name' => $modelData['name'] ?? '',
                'model' => $modelData['model'] ?? '',
                'provider' => $modelData['provider'] ?? '',
            ];
        }
        
        return $result;
    }

    /**
     * 根据标识查找
     */
    public static function findByModel(string $model): ?AiModel
    {
        return self::where('model', '=', $model)
            ->where('status', '=', 1)
            ->find();
    }

    /**
     * 设置为默认
     */
    public function setAsDefault(): bool
    {
        // 取消其他默认
        self::where('is_default', '=', 1)->update(['is_default' => 0]);
        
        $this->is_default = 1;
        return $this->save();
    }

    /**
     * 计算成本
     */
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1000) * $this->input_price;
        $outputCost = ($outputTokens / 1000) * $this->output_price;
        return $inputCost + $outputCost;
    }

    /**
     * 获取模型信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'provider' => $this->provider,
            'max_tokens' => $this->max_tokens,
            'input_price' => $this->input_price,
            'output_price' => $this->output_price,
            'is_default' => $this->is_default,
            'status' => $this->status,
            'sort' => $this->sort,
            'config' => json_decode($this->config, true) ?? [],
        ];
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}
