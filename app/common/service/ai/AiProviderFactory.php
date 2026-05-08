<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiModel;
use app\common\service\AiModelService;
use app\common\traits\CircuitBreakerTrait;

/**
 * AI Provider工厂 - V2.5增强
 * 新增：熔断器集成、速率限制、更多Provider路由
 */
class AiProviderFactory
{
    use CircuitBreakerTrait;

    /**
     * Provider类名映射（支持自定义路由）
     */
    protected static array $providerMap = [
        'deepseek' => DeepSeekProvider::class,
        'qwen'     => QwenProvider::class,
        'glm'      => GlmProvider::class,
        'ernie'    => ErnieProvider::class,
        'openai'   => OpenaiCompatibleProvider::class,
    ];

    /**
     * 注册自定义Provider
     */
    public static function registerProvider(string $name, string $class): void
    {
        self::$providerMap[$name] = $class;
    }

    /**
     * 根据provider名称创建Provider实例
     */
    public static function create(string $providerName): AiProviderInterface
    {
        $model = AiModel::where('provider', $providerName)
            ->where('is_enabled', 1)
            ->find();

        if (!$model) {
            throw new \Exception("AI模型 {$providerName} 未配置或已禁用");
        }

        return self::createFromModel($model);
    }

    /**
     * 从模型实例创建Provider
     */
    protected static function createFromModel(AiModel $model): AiProviderInterface
    {
        $providerName = $model->provider;
        $class = self::$providerMap[$providerName] ?? "\\app\\common\\service\\ai\\" . ucfirst($providerName) . "Provider";

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

        return self::createFromModel($model);
    }

    /**
     * 获取故障降级Provider（排除指定provider）
     * V2.5增强：集成熔断器，跳过已熔断的Provider
     */
    public static function getFallbackProvider(?string $excludeProvider = null): AiProviderInterface
    {
        $factory = new self(); // 需要实例化以使用trait方法
        $query = AiModel::where('is_enabled', 1)->order('sort', 'asc');

        if ($excludeProvider) {
            $query->where('provider', '<>', $excludeProvider);
        }

        $models = $query->select();

        foreach ($models as $model) {
            // 检查熔断状态
            if ($factory->isBreakerOpen($model->provider)) {
                continue; // 跳过已熔断的Provider
            }

            // 检查速率限制
            if (!AiModelService::checkRateLimit($model->id, $model->rate_limit_rpm, $model->rate_limit_rph)) {
                continue; // 跳过已达速率限制的Provider
            }

            $class = self::$providerMap[$model->provider] ?? "\\app\\common\\service\\ai\\" . ucfirst($model->provider) . "Provider";

            if (class_exists($class)) {
                return new $class($model);
            }
        }

        throw new \Exception("无可用备用AI模型");
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

    /**
     * 根据模型ID获取Provider实例（V2.9补全）
     */
    public static function getById(int $modelId): AiProviderInterface
    {
        $model = AiModel::find($modelId);
        if (!$model || !$model->is_enabled) {
            throw new \Exception("AI模型 #{$modelId} 未配置或已禁用");
        }
        return self::createFromModel($model);
    }

    /**
     * 获取所有可用Provider列表（供后台选择）
     */
    public static function getAvailableProviders(): array
    {
        return array_keys(self::$providerMap);
    }
}
