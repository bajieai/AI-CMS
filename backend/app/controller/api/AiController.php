<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\DeepSeekAiService;
use app\service\AiTaskQueue;
use app\model\AiTask;
use app\model\AiPrompt;
use app\model\AiModel;
use app\model\AiUsageStat;
use app\exception\BusinessException;

/**
 * AI控制器
 */
class AiController extends BaseController
{
    /**
     * AI服务
     */
    protected DeepSeekAiService $aiService;

    /**
     * 任务队列
     */
    protected AiTaskQueue $taskQueue;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct($this->app ?? app());
        $this->aiService = new DeepSeekAiService();
        $this->taskQueue = new AiTaskQueue();
    }

    /**
     * 生成内容(同步)
     */
    public function generate(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['prompt'], $input);
        $this->validateData([
            'prompt' => 'require|max:10000',
            'model' => 'require',
        ], $input);
        
        // 检查API配置
        if (!$this->aiService->isConfigured()) {
            throw new BusinessException('AI服务未配置，请联系管理员');
        }
        
        $options = [
            'model' => $input['model'] ?? 'deepseek-chat',
            'system_prompt' => $input['system_prompt'] ?? '',
            'temperature' => (float) ($input['temperature'] ?? 0.7),
            'max_tokens' => (int) ($input['max_tokens'] ?? 4096),
            'messages' => $input['messages'] ?? [],
        ];
        
        try {
            $result = $this->aiService->generate($input['prompt'], $options);
            
            // 记录使用统计
            if (isset($result['usage'])) {
                $model = AiModel::findByModel($options['model']);
                $modelId = $model ? $model->id : 0;
                
                AiUsageStat::record(
                    $this->request->user_id,
                    $modelId,
                    $result['usage']['prompt_tokens'] ?? 0,
                    $result['usage']['completion_tokens'] ?? 0,
                    $this->aiService->calculateCost(
                        $options['model'],
                        $result['usage']['prompt_tokens'] ?? 0,
                        $result['usage']['completion_tokens'] ?? 0
                    )
                );
            }
            
            return $this->success($result, '生成成功');
            
        } catch (\Exception $e) {
            throw new BusinessException('AI生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成内容(流式SSE)
     */
    public function generateStream(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['prompt'], $input);
        
        // 检查API配置
        if (!$this->aiService->isConfigured()) {
            throw new BusinessException('AI服务未配置，请联系管理员');
        }
        
        $options = [
            'model' => $input['model'] ?? 'deepseek-chat',
            'system_prompt' => $input['system_prompt'] ?? '',
            'temperature' => (float) ($input['temperature'] ?? 0.7),
            'max_tokens' => (int) ($input['max_tokens'] ?? 4096),
        ];
        
        // 设置SSE响应头
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        
        $callback = function ($content, $chunk) {
            echo "data: " . json_encode(['content' => $content, 'done' => false]) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        };
        
        try {
            $result = $this->aiService->generateStream($input['prompt'], $options, $callback);
            
            echo "data: " . json_encode(['content' => '', 'done' => true, 'result' => $result]) . "\n\n";
            
        } catch (\Exception $e) {
            echo "data: " . json_encode(['error' => $e->getMessage(), 'done' => true]) . "\n\n";
        }
        
        echo "data: [DONE]\n\n";
        flush();
        exit;
    }

    /**
     * 内容优化
     */
    public function optimize(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['content'], $input);
        $this->validateData([
            'content' => 'require',
            'type' => 'require',
        ], $input);
        
        // 检查API配置
        if (!$this->aiService->isConfigured()) {
            throw new BusinessException('AI服务未配置，请联系管理员');
        }
        
        // 获取优化模板
        $promptTemplate = $this->getOptimizePrompt($input['type']);
        
        // 替换变量
        $prompt = str_replace('{{content}}', $input['content'], $promptTemplate);
        if (isset($input['keywords'])) {
            $prompt = str_replace('{{keywords}}', $input['keywords'], $prompt);
        }
        
        $options = [
            'model' => $input['model'] ?? 'deepseek-chat',
            'temperature' => 0.7,
        ];
        
        try {
            $result = $this->aiService->generate($prompt, $options);
            
            return $this->success([
                'original' => $input['content'],
                'optimized' => $result['content'],
                'usage' => $result['usage'] ?? null,
            ], '优化成功');
            
        } catch (\Exception $e) {
            throw new BusinessException('内容优化失败: ' . $e->getMessage());
        }
    }

    /**
     * 地理核查
     */
    public function geoCheck(): \think\Response
    {
        $input = $this->getInput();
        
        $this->validateRequired(['content'], $input);
        
        // 检查API配置
        if (!$this->aiService->isConfigured()) {
            throw new BusinessException('AI服务未配置，请联系管理员');
        }
        
        $prompt = "请检查以下内容中的地理位置信息是否准确，是否存在虚构或错误的地理位置。\n\n内容：\n" . $input['content'] . "\n\n请以JSON格式返回检查结果，包括：\n1. 地理位置列表\n2. 每个位置的准确性评估\n3. 存在的问题（如有）\n4. 建议（如有）";
        
        try {
            $result = $this->aiService->generate($prompt, [
                'model' => $input['model'] ?? 'deepseek-chat',
                'temperature' => 0.3,
            ]);
            
            // 尝试解析JSON结果
            $checkResult = $this->parseGeoCheckResult($result['content']);
            
            return $this->success([
                'original' => $input['content'],
                'check_result' => $checkResult,
                'raw_response' => $result['content'],
            ], '核查完成');
            
        } catch (\Exception $e) {
            throw new BusinessException('地理核查失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取任务列表
     */
    public function tasks(): \think\Response
    {
        $pageParams = $this->getPageParams();
        $status = $this->request->param('status', '');
        
        $query = AiTask::where('user_id', '=', $this->request->user_id);
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($pageParams['page'], $pageParams['per_page'])
            ->select();
        
        return $this->paginate($list->toArray(), $total, $pageParams['page'], $pageParams['per_page']);
    }

    /**
     * 获取Prompt模板
     */
    public function prompts(): \think\Response
    {
        $type = $this->request->param('type', '');
        
        if ($type) {
            $prompts = AiPrompt::getByType($type);
        } else {
            $prompts = AiPrompt::getList();
        }
        
        return $this->success($prompts);
    }

    /**
     * 获取可用模型
     */
    public function models(): \think\Response
    {
        $models = AiModel::getSelectList();
        
        return $this->success($models);
    }

    /**
     * 获取使用统计
     */
    public function stats(): \think\Response
    {
        $period = $this->request->param('period', 'today'); // today/week/month
        
        $userId = $this->request->user_id;
        
        switch ($period) {
            case 'today':
                $stats = AiUsageStat::getUserTodayStats($userId);
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
                break;
                
            case 'week':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                $endDate = date('Y-m-d');
                $stats = AiUsageStat::getUserStatsByDateRange($userId, $startDate, $endDate);
                $stats = $this->aggregateStats($stats);
                break;
                
            case 'month':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $endDate = date('Y-m-d');
                $stats = AiUsageStat::getUserStatsByDateRange($userId, $startDate, $endDate);
                $stats = $this->aggregateStats($stats);
                break;
                
            default:
                $stats = AiUsageStat::getUserTodayStats($userId);
        }
        
        // 获取每日趋势
        $trend = AiUsageStat::getDailyTrend($userId, $period === 'today' ? 1 : ($period === 'week' ? 7 : 30));
        
        return $this->success([
            'period' => $period,
            'start_date' => $startDate ?? date('Y-m-d'),
            'end_date' => $endDate ?? date('Y-m-d'),
            'stats' => $stats,
            'trend' => $trend,
        ]);
    }

    /**
     * 获取优化提示词
     */
    protected function getOptimizePrompt(string $type): string
    {
        $prompts = [
            'seo' => "请优化以下内容的SEO效果，包括标题、关键词提取、描述优化等：\n\n{{content}}\n\n关键词：{{keywords}}",
            'readability' => "请优化以下内容的可读性和表达：\n\n{{content}}",
            'grammar' => "请检查并修正以下内容的语法错误：\n\n{{content}}",
            'brevity' => "请精简以下内容，保持核心信息：\n\n{{content}}",
        ];
        
        return $prompts[$type] ?? "请优化以下内容：\n\n{{content}}";
    }

    /**
     * 解析地理核查结果
     */
    protected function parseGeoCheckResult(string $content): array
    {
        // 尝试提取JSON
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        
        // 返回原始文本
        return [
            'raw_text' => $content,
            'parsed' => false,
        ];
    }

    /**
     * 聚合统计数据
     */
    protected function aggregateStats(array $stats): array
    {
        $total = [
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'cost' => 0,
            'request_count' => 0,
        ];
        
        foreach ($stats as $stat) {
            $total['input_tokens'] += $stat['input_tokens'];
            $total['output_tokens'] += $stat['output_tokens'];
            $total['total_tokens'] += $stat['total_tokens'];
            $total['cost'] += $stat['cost'];
            $total['request_count'] += $stat['request_count'];
        }
        
        return $total;
    }
}
