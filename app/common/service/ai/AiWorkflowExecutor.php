<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiWorkflow;
use app\common\model\AiWorkflowExec;
use think\facade\Log;
use think\facade\Container;

/**
 * AI工作流执行器
 * V2.9.38 AI-PLUS-1
 * 负责工作流节点的实际执行调度
 */
class AiWorkflowExecutor
{
    protected AiWorkflowNodeHandler $nodeHandler;

    public function __construct()
    {
        $this->nodeHandler = new AiWorkflowNodeHandler();
    }

    /**
     * 运行工作流执行
     */
    public function run(int $execId): void
    {
        $exec = AiWorkflowExec::find($execId);
        if (!$exec) throw new \RuntimeException('Execution not found');

        $workflow = AiWorkflow::find($exec->workflow_id);
        if (!$workflow) throw new \RuntimeException('Workflow not found');

        $startTime = microtime(true);
        $exec->save([
            'exec_status' => AiWorkflowExec::STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $definition = $workflow->workflow_definition;
        if (!$definition || !isset($definition['nodes'])) {
            throw new \RuntimeException('Invalid workflow definition');
        }

        $nodes = $definition['nodes'];
        $edges = $definition['edges'] ?? [];
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        // 构建邻接表
        $adjacency = [];
        foreach ($edges as $edge) {
            $from = $edge['from'];
            $to = $edge['to'];
            if (!isset($adjacency[$from])) $adjacency[$from] = [];
            $adjacency[$from][] = $to;
        }

        // 找到起始节点(没有入边的节点)
        $incomingNodes = array_flip(array_column($edges, 'to'));
        $startNodes = array_filter(array_keys($nodeMap), fn($id) => !isset($incomingNodes[$id]));

        $nodeResults = [];
        $aiCallCount = 0;
        $aiCallCost = 0.0;
        $visited = [];

        // 执行所有起始节点
        $queue = $startNodes;
        while (!empty($queue)) {
            $nodeId = array_shift($queue);
            if (isset($visited[$nodeId])) continue;
            $visited[$nodeId] = true;

            // 检查是否所有前驱节点已完成
            $preds = $this->getPredecessors($nodeId, $edges);
            $allPredsDone = true;
            foreach ($preds as $pred) {
                if (!isset($nodeResults[$pred]) || ($nodeResults[$pred]['status'] ?? '') !== 'success') {
                    $allPredsDone = false;
                    break;
                }
            }
            if (!$allPredsDone) {
                $queue[] = $nodeId; // 放回队列稍后处理
                continue;
            }

            $node = $nodeMap[$nodeId];
            $exec->save(['current_node' => $nodeId]);

            $nodeStart = microtime(true);
            try {
                $result = $this->nodeHandler->execute($node, $exec->target_ids ?? [], $nodeResults);
                $nodeResults[$nodeId] = [
                    'status' => 'success',
                    'duration' => round((microtime(true) - $nodeStart) * 1000),
                    'output' => $result['output'] ?? null,
                    'ai_calls' => $result['ai_calls'] ?? 0,
                    'ai_cost' => $result['ai_cost'] ?? 0,
                ];
                $aiCallCount += $result['ai_calls'] ?? 0;
                $aiCallCost += $result['ai_cost'] ?? 0;

                // 处理条件分支: 检查节点的condition配置
                if ($node['type'] === 'condition' && isset($result['branch'])) {
                    $nextNodes = $this->getConditionalSuccessors($nodeId, $adjacency, $result['branch']);
                } else {
                    $nextNodes = $adjacency[$nodeId] ?? [];
                }
                foreach ($nextNodes as $next) {
                    if (!isset($visited[$next])) $queue[] = $next;
                }
            } catch (\Throwable $e) {
                $nodeResults[$nodeId] = [
                    'status' => 'failed',
                    'duration' => round((microtime(true) - $nodeStart) * 1000),
                    'error' => $e->getMessage(),
                ];
                Log::error("Workflow node {$nodeId} failed: " . $e->getMessage());
                // 失败处理: 如果节点配置了on_error=continue则继续，否则中断
                $onError = $node['config']['on_error'] ?? 'stop';
                if ($onError === 'stop') break;
            }

            // 更新执行进度
            $exec->save(['node_results' => $nodeResults]);
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000);
        $allSuccess = true;
        foreach ($nodeResults as $result) {
            if (($result['status'] ?? '') !== 'success') {
                $allSuccess = false;
                break;
            }
        }

        $exec->save([
            'exec_status' => $allSuccess ? AiWorkflowExec::STATUS_SUCCESS : AiWorkflowExec::STATUS_FAILED,
            'current_node' => '',
            'node_results' => $nodeResults,
            'total_duration' => $totalDuration,
            'ai_call_count' => $aiCallCount,
            'ai_call_cost' => $aiCallCost,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // 更新工作流统计
        if ($allSuccess) {
            AiWorkflow::where('id', $exec->workflow_id)->inc('success_count')->update();
        } else {
            AiWorkflow::where('id', $exec->workflow_id)->inc('fail_count')->update();
        }
        AiWorkflow::where('id', $exec->workflow_id)->update(['avg_duration' => $totalDuration]);
    }

    /**
     * 重试单个节点
     */
    public function retryNode(int $execId, string $nodeId): void
    {
        $exec = AiWorkflowExec::find($execId);
        if (!$exec) return;
        $workflow = AiWorkflow::find($exec->workflow_id);
        if (!$workflow) return;
        $definition = $workflow->workflow_definition;
        $node = null;
        foreach ($definition['nodes'] ?? [] as $n) {
            if ($n['id'] === $nodeId) { $node = $n; break; }
        }
        if (!$node) return;
        $nodeResults = $exec->node_results ?? [];
        $nodeStart = microtime(true);
        try {
            $result = $this->nodeHandler->execute($node, $exec->target_ids ?? [], $nodeResults);
            $nodeResults[$nodeId] = [
                'status' => 'success',
                'duration' => round((microtime(true) - $nodeStart) * 1000),
                'output' => $result['output'] ?? null,
                'ai_calls' => $result['ai_calls'] ?? 0,
                'ai_cost' => $result['ai_cost'] ?? 0,
                'retry' => true,
            ];
        } catch (\Throwable $e) {
            $nodeResults[$nodeId] = [
                'status' => 'failed', 'duration' => round((microtime(true) - $nodeStart) * 1000),
                'error' => $e->getMessage(), 'retry' => true,
            ];
        }
        $exec->save(['node_results' => $nodeResults]);
    }

    /**
     * 获取前驱节点
     */
    protected function getPredecessors(string $nodeId, array $edges): array
    {
        $preds = [];
        foreach ($edges as $edge) {
            if ($edge['to'] === $nodeId) $preds[] = $edge['from'];
        }
        return $preds;
    }

    /**
     * 获取条件分支后继节点
     */
    protected function getConditionalSuccessors(string $nodeId, array $adjacency, string $branch): array
    {
        $successors = $adjacency[$nodeId] ?? [];
        // 条件分支: 边上可以标注condition字段匹配branch
        return $successors; // 默认返回所有后继
    }
}
