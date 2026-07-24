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

namespace app\common\service\ai;

use app\common\model\AiModel;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI模型配置中心服务 — V2.9.39 AI-DEEP-4
 *
 * 功能：
 *   - 多模型管理（增删改查、启用/禁用、设为默认）
 *   - 参数配置（temperature/max_tokens/top_p/penalty等）
 *   - 配额管理（每分钟/每小时/每日调用限制）
 *   - 成本统计（按模型/按日/按操作类型统计调用次数和费用）
 *
 * 复用 app\common\model\AiModel 模型和 AiProviderFactory 工厂
 * 与 app\common\service\AiModelService 互补（AiModelService处理基础CRUD，本服务处理配置/配额/统计）
 */
class AiModelConfigService
{
    private const CACHE_TAG = 'ai_model_config';
    private const CACHE_TTL = 3600; // 1小时

    /** 配额统计缓存键前缀 */
    private const QUOTA_CACHE_PREFIX = 'ai_model_quota_';

    /**
     * 获取模型配置详情（含参数、配额设置）
     * @param int $modelId 模型ID
     * @return array|null
     */
    public function getModelConfig(int $modelId): ?array
    {
        $cacheKey = 'model_config_' . $modelId;
        return Cache::remember($cacheKey, function () use ($modelId) {
            $model = AiModel::find($modelId);
            if (!$model) {
                return null;
            }

            $data = $model->toArray();

            // 从i8j_config读取配额配置
            $quotaConfig = $this->getModelQuotaConfig($modelId);
            $data['quota'] = $quotaConfig;

            // 从i8j_config读取高级参数
            $advancedParams = $this->getModelAdvancedParams($modelId);
            $data['advanced_params'] = $advancedParams;

            return $data;
        }, self::CACHE_TTL);
    }

    /**
     * 获取所有模型配置列表
     * @param bool $enabledOnly 是否只返回已启用的模型
     * @return array
     */
    public function listModelConfigs(bool $enabledOnly = false): array
    {
        $cacheKey = 'model_configs_' . ($enabledOnly ? 'enabled' : 'all');
        return Cache::remember($cacheKey, function () use ($enabledOnly) {
            $query = AiModel::where('id', '>', 0);
            if ($enabledOnly) {
                $query->where('is_enabled', 1);
            }
            $models = $query->order('sort', 'asc')->select()->toArray();

            foreach ($models as &$model) {
                $model['quota'] = $this->getModelQuotaConfig((int) $model['id']);
                $model['advanced_params'] = $this->getModelAdvancedParams((int) $model['id']);
            }

            return $models;
        }, self::CACHE_TTL);
    }

    /**
     * 保存模型配置（含高级参数和配额设置）
     * @param int $modelId 模型ID
     * @param array $data 配置数据
     * @return bool
     */
    public function saveModelConfig(int $modelId, array $data): bool
    {
        $model = AiModel::find($modelId);
        if (!$model) {
            return false;
        }

        // 更新基础字段
        $basicFields = ['name', 'provider', 'model_id', 'api_base', 'api_key', 'capabilities',
                        'max_tokens', 'temperature', 'is_enabled', 'is_default', 'sort'];
        $updateData = [];
        foreach ($basicFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            // 如果设为默认，先取消其他默认
            if (!empty($updateData['is_default'])) {
                AiModel::where('is_default', 1)->update(['is_default' => 0]);
            }
            $model->save($updateData);
        }

        // 保存高级参数
        if (isset($data['advanced_params'])) {
            $this->saveModelAdvancedParams($modelId, $data['advanced_params']);
        }

        // 保存配额配置
        if (isset($data['quota'])) {
            $this->saveModelQuotaConfig($modelId, $data['quota']);
        }

        // 清除缓存
        Cache::clear();

        return true;
    }

    /**
     * 获取模型配额配置
     * @param int $modelId 模型ID
     * @return array
     */
    public function getModelQuotaConfig(int $modelId): array
    {
        $config = Db::name('config')
            ->where('name', 'like', 'ai_model_quota_' . $modelId . '_%')
            ->column('value', 'name');

        return [
            'rpm'            => (int) ($config['ai_model_quota_' . $modelId . '_rpm'] ?? 60),
            'rph'            => (int) ($config['ai_model_quota_' . $modelId . '_rph'] ?? 1000),
            'daily_limit'    => (int) ($config['ai_model_quota_' . $modelId . '_daily'] ?? 0),
            'monthly_limit'  => (int) ($config['ai_model_quota_' . $modelId . '_monthly'] ?? 0),
            'cost_per_1k_tokens' => (float) ($config['ai_model_quota_' . $modelId . '_cost_1k'] ?? 0.001),
        ];
    }

    /**
     * 保存模型配额配置
     * @param int $modelId 模型ID
     * @param array $quota 配额数据
     * @return void
     */
    public function saveModelQuotaConfig(int $modelId, array $quota): void
    {
        $configs = [
            'ai_model_quota_' . $modelId . '_rpm'     => (string) ($quota['rpm'] ?? 60),
            'ai_model_quota_' . $modelId . '_rph'     => (string) ($quota['rph'] ?? 1000),
            'ai_model_quota_' . $modelId . '_daily'   => (string) ($quota['daily_limit'] ?? 0),
            'ai_model_quota_' . $modelId . '_monthly' => (string) ($quota['monthly_limit'] ?? 0),
            'ai_model_quota_' . $modelId . '_cost_1k' => (string) ($quota['cost_per_1k_tokens'] ?? 0.001),
        ];

        foreach ($configs as $name => $value) {
            $this->upsertConfig($name, $value, 'ai');
        }
    }

    /**
     * 获取模型高级参数
     * @param int $modelId 模型ID
     * @return array
     */
    public function getModelAdvancedParams(int $modelId): array
    {
        $config = Db::name('config')
            ->where('name', 'like', 'ai_model_params_' . $modelId . '_%')
            ->column('value', 'name');

        return [
            'top_p'              => (float) ($config['ai_model_params_' . $modelId . '_top_p'] ?? 1.0),
            'frequency_penalty'  => (float) ($config['ai_model_params_' . $modelId . '_freq_penalty'] ?? 0),
            'presence_penalty'   => (float) ($config['ai_model_params_' . $modelId . '_pres_penalty'] ?? 0),
            'stop_sequences'     => $config['ai_model_params_' . $modelId . '_stop'] ?? '',
            'response_format'    => $config['ai_model_params_' . $modelId . '_response_format'] ?? 'text',
            'seed'               => (int) ($config['ai_model_params_' . $modelId . '_seed'] ?? 0),
        ];
    }

    /**
     * 保存模型高级参数
     * @param int $modelId 模型ID
     * @param array $params 参数
     * @return void
     */
    public function saveModelAdvancedParams(int $modelId, array $params): void
    {
        $configs = [
            'ai_model_params_' . $modelId . '_top_p'           => (string) ($params['top_p'] ?? 1.0),
            'ai_model_params_' . $modelId . '_freq_penalty'    => (string) ($params['frequency_penalty'] ?? 0),
            'ai_model_params_' . $modelId . '_pres_penalty'    => (string) ($params['presence_penalty'] ?? 0),
            'ai_model_params_' . $modelId . '_stop'            => (string) ($params['stop_sequences'] ?? ''),
            'ai_model_params_' . $modelId . '_response_format' => (string) ($params['response_format'] ?? 'text'),
            'ai_model_params_' . $modelId . '_seed'            => (string) ($params['seed'] ?? 0),
        ];

        foreach ($configs as $name => $value) {
            $this->upsertConfig($name, $value, 'ai');
        }
    }

    /**
     * 检查模型配额
     * @param int $modelId 模型ID
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function checkQuota(int $modelId): array
    {
        $quota = $this->getModelQuotaConfig($modelId);
        $date = date('Ymd');
        $month = date('Ym');

        // 每日限制
        if ($quota['daily_limit'] > 0) {
            $dailyUsed = $this->getUsageCount($modelId, 'daily', $date);
            if ($dailyUsed >= $quota['daily_limit']) {
                return ['allowed' => false, 'reason' => '已达每日调用上限'];
            }
        }

        // 每月限制
        if ($quota['monthly_limit'] > 0) {
            $monthlyUsed = $this->getUsageCount($modelId, 'monthly', $month);
            if ($monthlyUsed >= $quota['monthly_limit']) {
                return ['allowed' => false, 'reason' => '已达每月调用上限'];
            }
        }

        // RPM/RPH 检查（复用 AiModelService）
        $model = AiModel::find($modelId);
        if ($model) {
            $allowed = \app\common\service\AiModelService::checkRateLimit($modelId, $quota['rpm'], $quota['rph']);
            if (!$allowed) {
                return ['allowed' => false, 'reason' => '已达速率限制'];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * 记录调用并增加计数
     * @param int $modelId 模型ID
     * @param int $tokensUsed 消耗Token数
     * @return void
     */
    public function recordUsage(int $modelId, int $tokensUsed): void
    {
        $date = date('Ymd');
        $month = date('Ym');

        // 增加每日/每月计数
        Cache::inc(self::QUOTA_CACHE_PREFIX . $modelId . '_daily_' . $date);
        Cache::inc(self::QUOTA_CACHE_PREFIX . $modelId . '_monthly_' . $month);

        // 记录Token消耗
        Cache::inc(self::QUOTA_CACHE_PREFIX . $modelId . '_daily_tokens_' . $date, $tokensUsed);

        // 记录到日志表
        try {
            Db::name('ai_content_log')->insert([
                'model_id'    => $modelId,
                'tokens_used' => $tokensUsed,
                'created_at'  => date('Y-m-d H:i:s'),
                'operation'   => 'model_usage',
            ]);
        } catch (\Throwable) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 获取使用量统计
     * @param int $modelId 模型ID（0=全部）
     * @param string $period 统计周期（daily/monthly/total）
     * @param string $date 日期标识（Ymd/Ym/空）
     * @return int
     */
    public function getUsageCount(int $modelId, string $period = 'daily', string $date = ''): int
    {
        if (empty($date)) {
            $date = $period === 'monthly' ? date('Ym') : date('Ymd');
        }
        $key = self::QUOTA_CACHE_PREFIX . $modelId . '_' . $period . '_' . $date;
        return (int) Cache::get($key, 0);
    }

    /**
     * 获取成本统计
     * @param int $modelId 模型ID（0=全部）
     * @param int $days 统计天数
     * @return array
     */
    public function getCostStats(int $modelId = 0, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $cacheKey = 'cost_stats_' . $modelId . '_' . $days;
        return Cache::remember($cacheKey, function () use ($modelId, $startDate, $days) {
            $query = Db::name('ai_content_log')
                ->where('created_at', '>=', $startDate)
                ->where('operation', 'model_usage');

            if ($modelId > 0) {
                $query->where('model_id', $modelId);
            }

            // 按日期统计
            $dailyStats = $query->field('DATE(created_at) as date, COUNT(*) as calls, SUM(tokens_used) as tokens')
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // 按模型统计
            $modelStats = Db::name('ai_content_log')
                ->where('created_at', '>=', $startDate)
                ->where('operation', 'model_usage')
                ->field('model_id, COUNT(*) as calls, SUM(tokens_used) as tokens')
                ->group('model_id')
                ->select()
                ->toArray();

            // 计算总成本
            $totalCost = 0;
            $totalCalls = 0;
            $totalTokens = 0;

            foreach ($modelStats as &$stat) {
                $quota = $this->getModelQuotaConfig((int) $stat['model_id']);
                $cost = $stat['tokens'] * $quota['cost_per_1k_tokens'] / 1000;
                $stat['cost'] = round($cost, 4);
                $totalCost += $cost;
                $totalCalls += $stat['calls'];
                $totalTokens += $stat['tokens'];

                // 附加模型名称
                $model = AiModel::find($stat['model_id']);
                $stat['model_name'] = $model?->name ?? 'Unknown';
            }

            // 每日成本
            foreach ($dailyStats as &$daily) {
                $dailyModelStats = Db::name('ai_content_log')
                    ->where('created_at', '>=', $daily['date'] . ' 00:00:00')
                    ->where('created_at', '<=', $daily['date'] . ' 23:59:59')
                    ->where('operation', 'model_usage')
                    ->field('model_id, SUM(tokens_used) as tokens')
                    ->group('model_id')
                    ->select()
                    ->toArray();

                $dailyCost = 0;
                foreach ($dailyModelStats as $dm) {
                    $quota = $this->getModelQuotaConfig((int) $dm['model_id']);
                    $dailyCost += $dm['tokens'] * $quota['cost_per_1k_tokens'] / 1000;
                }
                $daily['cost'] = round($dailyCost, 4);
            }

            return [
                'summary' => [
                    'total_cost'   => round($totalCost, 4),
                    'total_calls'  => $totalCalls,
                    'total_tokens' => $totalTokens,
                    'days'         => $days,
                ],
                'daily_stats'  => $dailyStats,
                'model_stats'  => $modelStats,
            ];
        }, 300); // 统计缓存5分钟
    }

    /**
     * 获取配额使用情况
     * @param int $modelId 模型ID
     * @return array
     */
    public function getQuotaUsage(int $modelId): array
    {
        $quota = $this->getModelQuotaConfig($modelId);
        $date = date('Ymd');
        $month = date('Ym');

        $dailyUsed = $this->getUsageCount($modelId, 'daily', $date);
        $monthlyUsed = $this->getUsageCount($modelId, 'monthly', $month);
        $dailyTokens = $this->getUsageCount($modelId, 'daily_tokens', $date);

        return [
            'quota' => $quota,
            'usage' => [
                'daily_used'       => $dailyUsed,
                'daily_limit'      => $quota['daily_limit'],
                'daily_remaining'  => $quota['daily_limit'] > 0 ? max(0, $quota['daily_limit'] - $dailyUsed) : -1,
                'monthly_used'     => $monthlyUsed,
                'monthly_limit'    => $quota['monthly_limit'],
                'monthly_remaining'=> $quota['monthly_limit'] > 0 ? max(0, $quota['monthly_limit'] - $monthlyUsed) : -1,
                'daily_tokens'     => $dailyTokens,
            ],
        ];
    }

    /**
     * 测试模型连接
     * @param int $modelId 模型ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(int $modelId): array
    {
        try {
            $provider = AiProviderFactory::getById($modelId);
            $result = $provider->write('你好，请回复"连接成功"');
            return [
                'success'  => true,
                'message'  => '连接成功',
                'response' => mb_substr($result, 0, 100),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => '连接失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 获取支持的Provider列表
     * @return array
     */
    public function getSupportedProviders(): array
    {
        $providers = AiProviderFactory::getAvailableProviders();

        $providerNames = [
            'deepseek' => 'DeepSeek',
            'qwen'     => '通义千问',
            'glm'      => '智谱GLM',
            'ernie'    => '百度文心',
            'openai'   => 'OpenAI兼容',
        ];

        $result = [];
        foreach ($providers as $key) {
            $result[] = [
                'key'  => $key,
                'name' => $providerNames[$key] ?? ucfirst($key),
            ];
        }

        return $result;
    }

    /**
     * 获取能力列表
     * @return array
     */
    public function getCapabilities(): array
    {
        return [
            ['key' => 'write', 'name' => '写作'],
            ['key' => 'seo', 'name' => 'SEO优化'],
            ['key' => 'translate', 'name' => '翻译'],
            ['key' => 'summarize', 'name' => '摘要'],
            ['key' => 'image', 'name' => '配图'],
            ['key' => 'qa', 'name' => '质量检测'],
            ['key' => 'recommend', 'name' => '推荐'],
            ['key' => 'rewrite', 'name' => '改写'],
        ];
    }

    /**
     * 清除模型相关缓存
     * @param int $modelId 模型ID（0=全部）
     * @return void
     */
    public function clearCache(int $modelId = 0): void
    {
        if ($modelId > 0) {
            Cache::delete('model_config_' . $modelId);
        }
        Cache::clear();
    }

    /**
     * 插入或更新配置
     * @param string $name 配置名
     * @param string $value 值
     * @param string $group 分组
     */
    private function upsertConfig(string $name, string $value, string $group = 'ai'): void
    {
        $existing = Db::name('config')->where('name', $name)->find();
        if ($existing) {
            Db::name('config')->where('name', $name)->update(['value' => $value]);
        } else {
            Db::name('config')->insert(['name' => $name, 'value' => $value, 'group' => $group]);
        }
    }
}
