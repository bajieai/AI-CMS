<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiModel;

/**
 * AI Provider工厂
 * 根据模型配置创建对应Provider实例，支持故障降级
 */
class AiProviderFactory
{
    /**
     * 根据provider名称创建Provider实例
     * @param string $providerName 供应商名称（deepseek/qwen等）
     * @throws \Exception
     */
    public static function create(string $providerName): AiProviderInterface
    {
        // 优先从数据库查找配置
        $model = AiModel::where('provider', $providerName)
            ->where('is_enabled', 1)
            ->find();

        if (!$model) {
            throw new \Exception("AI模型 {$providerName} 未配置或已禁用");
        }

        $class = "\\app\\common\\service\\ai\\" . ucfirst($providerName) . "Provider";

        if (!class_exists($class)) {
            throw new \Exception("AI Provider {$providerName} 不存在");
        }

        return new $class($model);
    }

    /**
     * 获取默认Provider
     */
    public static function getDefault(): AiProviderInterface
    {
        // 1. 查找数据库中标记为默认的模型
        $model = AiModel::where('is_default', 1)
            ->where('is_enabled', 1)
            ->find();

        // 2. 如果没有默认模型，取第一个启用的
        if (!$model) {
            $model = AiModel::where('is_enabled', 1)
                ->order('sort', 'asc')
                ->find();
        }

        // 3. 数据库无配置时，回退到.env配置的DeepSeek
        if (!$model) {
            return self::createFromEnv();
        }

        $class = "\\app\\common\\service\\ai\\" . ucfirst($model->provider) . "Provider";
        if (!class_exists($class)) {
            return self::createFromEnv();
        }

        return new $class($model);
    }

    /**
     * 获取故障降级Provider（排除指定provider）
     * @param string|null $excludeProvider 要排除的provider
     */
    public static function getFallbackProvider(?string $excludeProvider = null): AiProviderInterface
    {
        $query = AiModel::where('is_enabled', 1)->order('sort', 'asc');

        if ($excludeProvider) {
            $query->where('provider', '<>', $excludeProvider);
        }

        $model = $query->find();

        if (!$model) {
            throw new \Exception("无可用备用AI模型");
        }

        $class = "\\app\\common\\service\\ai\\" . ucfirst($model->provider) . "Provider";

        if (!class_exists($class)) {
            throw new \Exception("备用AI Provider {$model->provider} 不存在");
        }

        return new $class($model);
    }

    /**
     * 从.env配置创建DeepSeek Provider（向后兼容）
     */
    protected static function createFromEnv(): AiProviderInterface
    {
        $model = new AiModel();
        $model->provider = 'deepseek';
        $model->model_id = env('ai.deepseek_model', 'deepseek-chat');
        $model->api_base = env('ai.deepseek_base_url', 'https://api.deepseek.com');
        $model->api_key = env('ai.deepseek_api_key', '');
        $model->max_tokens = 2000;
        $model->temperature = 0.7;
        $model->capabilities = 'write,seo,translate,summarize';

        return new DeepSeekProvider($model);
    }
}
