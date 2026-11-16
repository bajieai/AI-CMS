<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTaskQueue;
use think\facade\Log;

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
        // S0-T1 附修：i8j_ai_task_queue.status 为 tinyint（0 pending / 1 running / 2 completed / 3 failed），
        //            started_at / completed_at 为 int 时间戳，原代码写字符串 'running' 与 datetime 会被截断成 0/2026
        $taskRecord->save(['status' => 1, 'started_at' => time()]);

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

            // 4. 自我纠正(如果需要)：V2.9.64 O-2 落地真实重试（最多 1 次），失败项重跑并重新评估
            if (!$evaluation['all_passed']) {
                $corrections = $this->selfCorrect($results, $agentConfig, $sessionId, 1);
                if (!empty($corrections['retried'])) {
                    $results    = array_merge($results, $corrections['retried']);
                    $evaluation = $this->evaluate($results, $task, $agentConfig);
                }
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

            // 更新任务记录（status=2 即 completed；completed_at 为 int 时间戳）
            $taskRecord->save([
                'status' => 2,
                'completed_at' => time(),
                'agent_memory' => json_encode($this->memory->getSessionMemory($sessionId), JSON_UNESCAPED_UNICODE),
                'result' => json_encode($report, JSON_UNESCAPED_UNICODE),
            ]);

        } catch (\Throwable $e) {
            Log::error("Agent execution failed: " . $e->getMessage());
            // S0-T4 附修：i8j_ai_task_queue 实际字段为 error_msg（无 error_message），原代码写错字段名
            $taskRecord->save([
                'status' => 3,
                'error_msg' => $e->getMessage(),
                'completed_at' => time(),
            ]);
            throw $e;
        }
    }

    /**
     * 计划: AI拆分子任务
     */
    protected function plan(string $task, array $agentConfig, array $context): array
    {
        $prompt = sprintf(
            "你是一个%s。任务: %s\n请将此任务拆分为可执行的子任务步骤，每个步骤包含: action(动作类型), params(参数), tool(使用的工具)。\n角色描述: %s\n能力: %s\n规则: %s\n上下文: %s",
            $agentConfig['name'] ?? '智能体',
            $task,
            $agentConfig['role'] ?? '',
            implode(', ', $agentConfig['capabilities'] ?? []),
            implode('; ', $agentConfig['rules'] ?? []),
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        // S0-T4：原调 AiConversationService::chat($prompt, ['temperature'=>0.3]) 签名错配
        //        （真实签名 chat(string $sessionId, int $userId, string $message, int $contentId=0)，
        //          第 2 个 array 撞 int $userId，strict_types 下必抛 TypeError）
        //        改为 Provider 单次生成；并加零 key / 零本地模型降级，不让缺 Provider 掐断整条链
        try {
            $provider = AiProviderFactory::getDefault();
            $aiResult = $provider->write($prompt, ['temperature' => 0.3]);
            $subtasks = $this->parsePlan((string)$aiResult);
            $degraded = false;
        } catch (\Throwable $e) {
            Log::warning('Agent plan degraded (no available AI provider): ' . $e->getMessage());
            $subtasks = [[
                'action'      => 'execute',
                'description' => '执行任务',
                'tool'        => 'ai_write',
                'params'      => [],
            ]];
            $degraded = true;
        }

        return [
            'task'              => $task,
            'subtasks'          => $subtasks,
            'estimated_actions' => count($subtasks),
            'degraded'          => $degraded,
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
                    // S0-T5：原 AiWriteService 不存在，改映射到 AiWritingAssistService::continueWriting()
                    $service = app(AiWritingAssistService::class);
                    return ['success' => true, 'output' => $service->continueWriting(
                        (string)($params['text'] ?? ''),
                        (int)($params['length'] ?? 500),
                        (array)($params['options'] ?? [])
                    )];
                case 'ai_image':
                    // S0-T5：原传 array 给 int $contentId（TypeError），按真实签名改
                    $service = app(AiImageService::class);
                    return ['success' => true, 'output' => $service->generate(
                        (int)($params['content_id'] ?? 0),
                        (string)($params['style'] ?? 'auto'),
                        (int)($params['count'] ?? 1)
                    )];
                case 'ai_translate':
                    // S0-T5：原 AiTranslationService 不存在，改映射到 AiTranslateService::translateField()
                    $service = app(AiTranslateService::class);
                    return ['success' => true, 'output' => $service->translateField(
                        (string)($params['text'] ?? ''),
                        (string)($params['lang'] ?? 'en'),
                        (array)($params['options'] ?? [])
                    )];
                case 'ai_qa':
                    $service = app(ContentQualityScoreService::class);
                    return ['success' => true, 'output' => $service->score((int)($params['content_id'] ?? 0))];
                case 'ai_seo':
                    // S0-T5：原 AiSeoService 不存在，改映射到 AiSeoDiagnosisService::diagnose()
                    $service = app(AiSeoDiagnosisService::class);
                    return ['success' => true, 'output' => $service->diagnose((int)($params['content_id'] ?? 0))];
                case 'ai_summary':
                    // S0-T5：原 summarize() 方法不存在，改映射到 generate(int $contentId)
                    $service = app(AiSummaryService::class);
                    return ['success' => true, 'output' => $service->generate((int)($params['content_id'] ?? 0))];
                case 'ai_recommend':
                    // S0-T5：原 recommend() 方法不存在，改映射到 recommendByContent(int, int)
                    $service = app(AiRecommendService::class);
                    return ['success' => true, 'output' => $service->recommendByContent(
                        (int)($params['content_id'] ?? 0),
                        (int)($params['limit'] ?? 10)
                    )];
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
     *
     * V2.9.64 O-2：原为空实现（只返回空数组），方法名与文档承诺的重试从未发生。
     * 现按「失败子任务重跑、最多 $maxRetries 次、成功即停」执行，并把每次尝试记录进审计。
     * 不对 pending_human_confirmation / skipped 项重试（前者等人、后者是超限保护）。
     */
    protected function selfCorrect(array $results, array $agentConfig, string $sessionId, int $maxRetries = 1): array
    {
        $retried  = [];
        $attempts = [];

        foreach ($results as $index => $result) {
            $status = (string) ($result['status'] ?? '');
            if ($status === 'success' || $status === 'pending_human_confirmation' || $status === 'skipped') {
                continue;
            }

            $subtask = $result['subtask'] ?? null;
            if (!is_array($subtask)) {
                continue;
            }

            $limit = max(1, $maxRetries);
            for ($try = 1; $try <= $limit; $try++) {
                $retryResult = $this->executeSubTask($subtask, $agentConfig, $sessionId, $results);
                $retryResult['retried_from_index'] = $index;
                $retryResult['retry_attempt']      = $try;

                $attempts[] = [
                    'index'   => $index,
                    'attempt' => $try,
                    'status'  => (string) ($retryResult['status'] ?? 'unknown'),
                ];

                if (($retryResult['status'] ?? '') === 'success') {
                    $retried[] = $retryResult;
                    break;
                }

                if ($try >= max(1, $maxRetries)) {
                    $retried[] = $retryResult;
                }
            }
        }

        return [
            'retried'     => $retried,
            'attempts'    => $attempts,
            'max_retries' => max(1, $maxRetries),
        ];
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
        // S0-T1：补齐缺失的闭合括号；$task 在本作用域未定义（本方法仅接收 $aiResult），改为固定文案
        return $subtasks ?: [['action' => 'execute', 'description' => '执行任务', 'tool' => 'ai_write', 'params' => []]];
    }
}
