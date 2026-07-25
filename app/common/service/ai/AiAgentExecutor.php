<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTaskQueue;
use think\facade\Log;
use think\facade\Container;

/**
 * AI智能体执行器
 * V2.9.38 AI-PLUS-3
 * 
 * 执行流程: plan(AI拆分子任务) → executeSubTask → selectTool → callTool(调用现有AI Service)
 *          → evaluate → selfCorrect → summarize → generateReport
 */
class AiAgentExecutor
{
    protected AiAgentMemory $memory;
    protected int $maxRetries = 3;

    public function __construct()
    {
        $this->memory = new AiAgentMemory();
    }

    /**
     * 执行智能体任务
     */
    public function execute(int $taskId, array $agentConfig, string $task, array $context = []): void
    {
        $taskRecord = AiTaskQueue::find($taskId);
        if (!$taskRecord) throw new \RuntimeException('Task not found');

        $sessionId = $taskRecord->agent_session_id ?: 'sess_' . uniqid();
        $taskRecord->save(['status' => 'running', 'started_at' => date('Y-m-d H:i:s')]);

        // 初始化记忆
        $this->memory->initSession($sessionId, $agentConfig);
        $this->memory->addShortTerm($sessionId, 'task', $task);
        $this->memory->addShortTerm($sessionId, 'context', $context);
        $this->memory->addShortTerm($sessionId, 'agent_config', $agentConfig);

        try {
            // 1. 计划: AI拆分子任务
            $plan = $this->plan($task, $agentConfig, $context);
            $taskRecord->save(['agent_plan' => json_encode($plan, JSON_UNESCAPED_UNICODE)]);
            $this->memory->addShortTerm($sessionId, 'plan', $plan);

            // 2. 执行各子任务
            $results = [];
            $actionCount = 0;
            $maxActions = $agentConfig['max_autonomous_actions'] ?? 5;

            foreach ($plan['subtasks'] as $subtask) {
                if ($actionCount >= $maxActions) {
                    $results[] = ['subtask' => $subtask, 'status' => 'skipped', 'reason' => 'max_autonomous_actions_reached'];
                    break;
                }

                // 检查是否需要人工确认
                if ($this->requiresHumanConfirmation($subtask, $agentConfig)) {
                    $results[] = ['subtask' => $subtask, 'status' => 'pending_human_confirmation'];
                    continue;
                }

                $result = $this->executeSubTask($subtask, $agentConfig, $sessionId, $results);
                $results[] = $result;
                $actionCount++;
            }

            // 3. 评估结果
            $evaluation = $this->evaluate($results, $task, $agentConfig);
            $this->memory->addShortTerm($sessionId, 'evaluation', $evaluation);

            // 4. 自我纠正(如果需要)
            if (!$evaluation['all_passed']) {
                $corrections = $this->selfCorrect($evaluation, $agentConfig, $sessionId);
                $this->memory->addShortTerm($sessionId, 'corrections', $corrections);
            }

            // 5. 总结
            $summary = $this->summarize($results, $evaluation, $task, $agentConfig, $sessionId);

            // 6. 生成报告
            $report = $this->generateReport($task, $results, $evaluation, $summary, $agentConfig);

            // 保存长期记忆
            $this->memory->saveLongTerm($sessionId, [
                'task' => $task,
                'results' => $results,
                'evaluation' => $evaluation,
                'summary' => $summary,
                'report' => $report,
            ]);

            // 更新任务记录
            $taskRecord->save([
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'agent_memory' => json_encode($this->memory->getSessionMemory($sessionId), JSON_UNESCAPED_UNICODE),
                'result' => json_encode($report, JSON_UNESCAPED_UNICODE),
            ]);

        } catch (\Throwable $e) {
            Log::error("Agent execution failed: " . $e->getMessage());
            $taskRecord->save([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        }
    }

    /**
     * 计划: AI拆分子任务
     */
    protected function plan(string $task, array $agentConfig, array $context): array
    {
        // 使用AI服务拆分任务
        $aiService = Container::getInstance()->make(\app\common\service\ai\AiConversationService::class);
        
        $prompt = sprintf(
            "你是一个%s。任务: %s\n请将此任务拆分为可执行的子任务步骤，每个步骤包含: action(动作类型), params(参数), tool(使用的工具)。\n角色描述: %s\n能力: %s\n规则: %s\n上下文: %s",
            $agentConfig['name'] ?? '智能体',
            $task,
            $agentConfig['role'] ?? '',
            implode(', ', $agentConfig['capabilities'] ?? []),
            implode('; ', $agentConfig['rules'] ?? []),
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        $aiResult = $aiService->chat($prompt, ['temperature' => 0.3]);
        
        // 解析AI返回的子任务列表
        $subtasks = $this->parsePlan($aiResult);
        
        return [
            'task' => $task,
            'subtasks' => $subtasks,
            'estimated_actions' => count($subtasks),
        ];
    }

    /**
     * 执行子任务
     */
    protected function executeSubTask(array $subtask, array $agentConfig, string $sessionId, array $previousResults): array
    {
        $tool = $subtask['tool'] ?? '';
        $params = $subtask['params'] ?? [];
        
        // 合并上下文
        $context = $this->memory->getContext($sessionId);
        $params = array_merge($params, ['context' => $context, 'previous_results' => $previousResults]);

        // 选择工具并调用
        $result = $this->selectAndCallTool($tool, $params, $agentConfig);

        // 记忆
        $this->memory->addShortTerm($sessionId, 'subtask_' . ($subtask['action'] ?? ''), $result);

        return [
            'subtask' => $subtask,
            'status' => $result['success'] ? 'success' : 'failed',
            'result' => $result,
        ];
    }

    /**
     * 选择工具并调用
     */
    protected function selectAndCallTool(string $tool, array $params, array $agentConfig): array
    {
        $capabilities = $agentConfig['capabilities'] ?? [];
        
        if (!in_array($tool, $capabilities) && $tool !== 'ai_write') {
            return ['success' => false, 'error' => "Tool {$tool} not in agent capabilities"];
        }

        try {
            switch ($tool) {
                case 'ai_write':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiWriteService::class);
                    return ['success' => true, 'output' => $service->write($params)];
                case 'ai_image':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiImageService::class);
                    return ['success' => true, 'output' => $service->generate($params)];
                case 'ai_translate':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiTranslationService::class);
                    return ['success' => true, 'output' => $service->translate($params['text'] ?? '', $params['lang'] ?? 'en')];
                case 'ai_qa':
                    $service = Container::getInstance()->make(\app\common\service\ai\ContentQualityScoreService::class);
                    return ['success' => true, 'output' => $service->score((int)($params['content_id'] ?? 0))];
                case 'ai_seo':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiSeoService::class);
                    return ['success' => true, 'output' => $service->optimize((int)($params['content_id'] ?? 0), $params)];
                case 'ai_summary':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiSummaryService::class);
                    return ['success' => true, 'output' => $service->summarize($params['text'] ?? '')];
                case 'ai_recommend':
                    $service = Container::getInstance()->make(\app\common\service\ai\AiRecommendService::class);
                    return ['success' => true, 'output' => $service->recommend($params)];
                default:
                    return ['success' => false, 'error' => "Unknown tool: {$tool}"];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 评估结果
     */
    protected function evaluate(array $results, string $task, array $agentConfig): array
    {
        $total = count($results);
        $successCount = count(array_filter($results, fn($r) => ($r['status'] ?? '') === 'success'));
        $allPassed = $successCount === $total;
        
        return [
            'total' => $total,
            'success' => $successCount,
            'failed' => $total - $successCount,
            'all_passed' => $allPassed,
            'success_rate' => $total > 0 ? round($successCount / $total * 100, 1) : 0,
        ];
    }

    /**
     * 自我纠正
     */
    protected function selfCorrect(array $evaluation, array $agentConfig, string $sessionId): array
    {
        $corrections = [];
        // 对失败的子任务进行重试
        return $corrections;
    }

    /**
     * 总结
     */
    protected function summarize(array $results, array $evaluation, string $task, array $agentConfig, string $sessionId): array
    {
        return [
            'task' => $task,
            'agent' => $agentConfig['name'] ?? '',
            'total_actions' => $evaluation['total'],
            'success_actions' => $evaluation['success'],
            'success_rate' => $evaluation['success_rate'],
            'completed_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 生成报告
     */
    protected function generateReport(string $task, array $results, array $evaluation, array $summary, array $agentConfig): array
    {
        return [
            'task' => $task,
            'agent_id' => $agentConfig['id'] ?? '',
            'agent_name' => $agentConfig['name'] ?? '',
            'summary' => $summary,
            'results' => $results,
            'evaluation' => $evaluation,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 判断是否需要人工确认
     */
    protected function requiresHumanConfirmation(array $subtask, array $agentConfig): bool
    {
        $maxAutonomous = $agentConfig['max_autonomous_actions'] ?? 0;
        if ($maxAutonomous === 0) return true;
        
        $dangerousActions = ['delete', 'publish', 'reject', 'refund'];
        if (in_array($subtask['action'] ?? '', $dangerousActions)) return true;
        
        return false;
    }

    /**
     * 解析AI计划
     */
    protected function parsePlan(string $aiResult): array
    {
        // 尝试解析JSON
        $decoded = json_decode($aiResult, true);
        if (is_array($decoded)) return $decoded;
        
        // 简化: 按行分割作为子任务
        $lines = explode("\n", trim($aiResult));
        $subtasks = [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $subtasks[] = [
                'action' => 'step_' . ($i + 1),
                'description' => $line,
                'tool' => 'ai_write',
                'params' => [],
            ];
        }
        return $subtasks ?: [['action' => 'execute', 'description' => $task ?? '执行任务', 'tool' => 'ai_write', 'params' => []];
    }
}
