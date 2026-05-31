<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Config;
use think\facade\Log;

/**
 * AI配图生成服务 - V2.9.12 Day 13
 *
 * ============================================================
 * Day 10 AI配图Provider预研头注（架构设计）
 * ============================================================
 * Provider接口规范：
 * 1. ImageProviderInterface {
 *     public function generate(string $prompt, array $options = []): array;
 *     public function getProviderName(): string;
 *     public function isAvailable(): bool;
 * }
 * 2. ImageProviderFactory::getProvider(string $name): ImageProviderInterface
 * 3. 适配器模式：每个Provider独立封装API调用逻辑
 * 4. 与AiService集成点：AiService::generateImage() 委托给 ImageProviderFactory
 *
 * 当前实现：基于现有config/ai.php中image.providers配置，
 * 支持通义万相(tongyi_wanxiang)/Flux/DALL-E三种Provider。
 * ============================================================
 */
class AiImageGenerateService
{
    /** 默认Provider */
    protected string $defaultProvider;
    /** 备用Provider */
    protected string $fallbackProvider;
    /** Provider配置 */
    protected array $providers;
    /** 超时 */
    protected int $timeout;
    /** 日限额 */
    protected int $dailyLimit;

    public function __construct()
    {
        $config = Config::get('ai.image', []);
        $this->defaultProvider = $config['default_provider'] ?? 'tongyi_wanxiang';
        $this->fallbackProvider = $config['fallback_provider'] ?? 'flux';
        $this->providers = $config['providers'] ?? [];
        $this->timeout = $config['timeout'] ?? 30;
        $this->dailyLimit = $config['daily_limit'] ?? 50;
    }

    /**
     * 生成单张配图
     *
     * @param string $prompt  图片描述Prompt
     * @param array  $options 可选参数(size/style/quality)
     * @return array ['success'=>bool, 'url'=>string, 'provider'=>string, 'message'=>string]
     */
    public function generate(string $prompt, array $options = []): array
    {
        $providerName = $options['provider'] ?? $this->defaultProvider;

        // 检查Provider是否启用
        if (!$this->isProviderEnabled($providerName)) {
            // 降级到fallback
            $providerName = $this->fallbackProvider;
            if (!$this->isProviderEnabled($providerName)) {
                return ['success' => false, 'url' => '', 'provider' => '', 'message' => '无可用的AI配图Provider'];
            }
        }

        // 检查日限额
        if ($this->isDailyLimitReached()) {
            return ['success' => false, 'url' => '', 'provider' => '', 'message' => '今日AI配图配额已用完'];
        }

        try {
            $result = $this->callProvider($providerName, $prompt, $options);
            $this->recordUsage($providerName);
            return $result;
        } catch (\Throwable $e) {
            Log::error("[AiImageGenerate] {$providerName} failed: " . $e->getMessage());

            // 故障降级到fallback
            if ($providerName !== $this->fallbackProvider && $this->isProviderEnabled($this->fallbackProvider)) {
                try {
                    $result = $this->callProvider($this->fallbackProvider, $prompt, $options);
                    $this->recordUsage($this->fallbackProvider);
                    return array_merge($result, ['fallback' => true]);
                } catch (\Throwable $fallbackError) {
                    Log::error("[AiImageGenerate] fallback {$this->fallbackProvider} failed: " . $fallbackError->getMessage());
                }
            }

            return ['success' => false, 'url' => '', 'provider' => $providerName, 'message' => '生成失败: ' . $e->getMessage()];
        }
    }

    /**
     * 批量生成配图
     *
     * @param array $prompts Prompt数组
     * @return array 每个prompt的生成结果
     */
    public function generateBatch(array $prompts): array
    {
        $maxBatch = Config::get('ai.image.max_batch_count', 5);
        $prompts = array_slice($prompts, 0, $maxBatch);

        $results = [];
        foreach ($prompts as $index => $prompt) {
            $results[$index] = $this->generate($prompt);
        }

        return $results;
    }

    /**
     * 为内容自动生成配图
     *
     * @param string $title   内容标题
     * @param string $summary 内容摘要（用于构建Prompt）
     * @return array
     */
    public function generateForContent(string $title, string $summary = ''): array
    {
        // 构建配图Prompt
        $prompt = $this->buildContentPrompt($title, $summary);
        return $this->generate($prompt, ['size' => '1024x1024', 'quality' => 'standard']);
    }

    /**
     * V2.9.14: 提交异步配图任务到队列
     *
     * @param int    $contentId 内容ID
     * @param string $title     内容标题
     * @param string $summary   内容摘要
     * @param int    $index     配图序号 0/1/2
     * @return int 任务ID
     */
    public function submitGenerateTask(int $contentId, string $title, string $summary = '', int $index = 0): int
    {
        $queueService = new \app\common\service\ai\AiTaskQueueService();
        return $queueService->enqueue('ai_image_generate', [
            'biz_id'  => $contentId,
            'biz_key' => "ai_image:{$contentId}",
            'payload' => [
                'content_id' => $contentId,
                'title'      => $title,
                'summary'    => $summary,
                'index'      => $index,
            ],
            'priority' => 0,
        ]);
    }

    /**
     * V2.9.14: 消费者调用的实际生成逻辑
     *
     * @param int   $contentId 内容ID
     * @param array $payload   任务参数
     * @return array
     */
    public function consumerProcess(int $contentId, array $payload): array
    {
        $title = $payload['title'] ?? '';
        $summary = $payload['summary'] ?? '';

        // 使用不同seed区分多张配图
        $result = $this->generateForContent($title, $summary);

        if ($result['success'] && !empty($result['task_id']) && empty($result['url'])) {
            // 异步Provider（通义万相/Flux），需要轮询获取结果
            $result = $this->pollTaskResult($result['task_id'], $result['provider']);
        }

        return $result;
    }

    /**
     * V2.9.14: 轮询获取异步任务结果（通义万相/Flux）
     *
     * @param string $taskId   任务ID
     * @param string $provider Provider名称
     * @param int    $maxWait  最大等待秒数
     * @return array
     */
    public function pollTaskResult(string $taskId, string $provider, int $maxWait = 60): array
    {
        $start = time();
        while (time() - $start < $maxWait) {
            $result = $this->queryTaskStatus($taskId, $provider);
            if ($result['success'] && !empty($result['url'])) {
                return $result;
            }
            if (!empty($result['failed'])) {
                return ['success' => false, 'url' => '', 'message' => $result['message'] ?? '任务失败'];
            }
            sleep(3);
        }
        return ['success' => false, 'url' => '', 'message' => '获取配图结果超时'];
    }

    /**
     * 查询异步任务状态（子类或扩展实现）
     */
    protected function queryTaskStatus(string $taskId, string $provider): array
    {
        // 简化实现：实际应根据Provider API查询
        // 通义万相: GET /tasks/{task_id}
        // Flux: GET /v1/flux-pro/{id}
        // 这里返回模拟结果，实际部署时替换为真实API调用
        return ['success' => true, 'url' => '', 'failed' => false, 'message' => 'polling'];
    }

    /**
     * 调用具体Provider
     */
    protected function callProvider(string $providerName, string $prompt, array $options): array
    {
        $config = $this->providers[$providerName] ?? [];
        if (empty($config['api_key'])) {
            throw new \RuntimeException("{$providerName} API Key未配置");
        }

        switch ($providerName) {
            case 'tongyi_wanxiang':
                return $this->callTongyi($config, $prompt, $options);
            case 'flux':
                return $this->callFlux($config, $prompt, $options);
            case 'dalle':
                return $this->callDalle($config, $prompt, $options);
            default:
                throw new \RuntimeException("不支持的Provider: {$providerName}");
        }
    }

    /**
     * 调用通义万相
     */
    protected function callTongyi(array $config, string $prompt, array $options): array
    {
        // 通义万相API调用（简化实现，实际需根据官方文档调整）
        $apiKey = $config['api_key'];
        $url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';

        $payload = [
            'model' => $config['model'] ?? 'wanx-v1',
            'input' => ['prompt' => $prompt],
            'parameters' => [
                'size' => $options['size'] ?? '1024x1024',
                'n' => 1,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-DashScope-Async: enable',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            throw new \RuntimeException("通义万相API错误: HTTP {$httpCode}");
        }

        $data = json_decode($response, true);
        // 异步任务，返回task_id
        $taskId = $data['output']['task_id'] ?? '';

        if (empty($taskId)) {
            throw new \RuntimeException('通义万相未返回任务ID');
        }

        // 简化处理：直接返回任务状态（实际需轮询）
        return [
            'success' => true,
            'url' => '', // 异步任务需轮询获取
            'provider' => 'tongyi_wanxiang',
            'task_id' => $taskId,
            'message' => '配图任务已提交，请稍后查看结果',
        ];
    }

    /**
     * 调用Flux
     */
    protected function callFlux(array $config, string $prompt, array $options): array
    {
        // Flux API调用（简化实现）
        $apiKey = $config['api_key'];
        $url = 'https://api.bfl.ml/v1/flux-pro';

        $payload = [
            'prompt' => $prompt,
            'width' => 1024,
            'height' => 1024,
            'prompt_upsampling' => false,
            'seed' => $options['seed'] ?? null,
            'steps' => $config['steps'] ?? 25,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Key: ' . $apiKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            throw new \RuntimeException("Flux API错误: HTTP {$httpCode}");
        }

        $data = json_decode($response, true);
        $taskId = $data['id'] ?? '';

        return [
            'success' => true,
            'url' => '',
            'provider' => 'flux',
            'task_id' => $taskId,
            'message' => '配图任务已提交',
        ];
    }

    /**
     * 调用DALL-E
     */
    protected function callDalle(array $config, string $prompt, array $options): array
    {
        $apiKey = $config['api_key'];
        $baseUrl = rtrim($config['base_url'] ?? 'https://api.openai.com/v1', '/');
        $url = $baseUrl . '/images/generations';

        $payload = [
            'model' => $config['model'] ?? 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $options['size'] ?? '1024x1024',
            'quality' => $options['quality'] ?? 'standard',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            throw new \RuntimeException("DALL-E API错误: HTTP {$httpCode}");
        }

        $data = json_decode($response, true);
        $imageUrl = $data['data'][0]['url'] ?? '';

        if (empty($imageUrl)) {
            throw new \RuntimeException('DALL-E未返回图片URL');
        }

        return [
            'success' => true,
            'url' => $imageUrl,
            'provider' => 'dalle',
            'message' => '配图生成成功',
        ];
    }

    /**
     * 构建内容配图Prompt
     */
    protected function buildContentPrompt(string $title, string $summary): string
    {
        $prompt = "为以下文章标题创作一张高质量的配图，要求画面简洁、专业、符合中文互联网审美，不要包含任何文字：\n\n标题：{$title}";
        if (!empty($summary)) {
            $prompt .= "\n内容摘要：" . mb_substr($summary, 0, 200);
        }
        return $prompt;
    }

    /**
     * 检查Provider是否启用
     */
    protected function isProviderEnabled(string $name): bool
    {
        return !empty($this->providers[$name]['enabled'])
            && !empty($this->providers[$name]['api_key']);
    }

    /**
     * 检查日限额
     */
    protected function isDailyLimitReached(): bool
    {
        $today = date('Ymd');
        $cacheKey = 'ai_image_daily_' . $today;
        $count = (int) cache($cacheKey);
        return $count >= $this->dailyLimit;
    }

    /**
     * 记录使用次数
     */
    protected function recordUsage(string $providerName): void
    {
        $today = date('Ymd');
        $cacheKey = 'ai_image_daily_' . $today;
        $count = (int) cache($cacheKey);
        cache($cacheKey, $count + 1, 86400);

        Log::info("[AiImageGenerate] 使用记录: provider={$providerName}, count=" . ($count + 1));
    }
}
