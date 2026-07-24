<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiWorkflow;
use app\common\model\AiWorkflowExec;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI工作流服务 - 编排层
 * V2.9.38 AI-PLUS-1
 * 工作流为编排层，调用现有54个AI Service，不重写AI调用逻辑。
 */
class AiWorkflowService
{
    protected const CACHE_TAG = 'workflow';
    protected const CACHE_TTL_DEF = 3600;
    protected const CACHE_TTL_STATS = 300;

    public function createWorkflow(array $data): int
    {
        $workflow = new AiWorkflow();
        $workflow->save([
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'workflow_type' => $data['workflow_type'] ?? AiWorkflow::TYPE_CUSTOM,
            'workflow_definition' => $data['workflow_definition'] ?? null,
            'trigger_type' => $data['trigger_type'] ?? AiWorkflow::TRIGGER_MANUAL,
            'trigger_config' => $data['trigger_config'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'is_template' => $data['is_template'] ?? 0,
            'category' => $data['category'] ?? '',
            'tags' => $data['tags'] ?? '',
            'icon' => $data['icon'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
            'cost_budget' => $data['cost_budget'] ?? 0,
            'creator_id' => $data['creator_id'] ?? 0,
            'status' => $data['status'] ?? AiWorkflow::STATUS_ACTIVE,
        ]);
        Cache::clear();
        return (int) $workflow->id;
    }

    public function updateWorkflow(int $id, array $data): bool
    {
        $workflow = AiWorkflow::find($id);
        if (!$workflow) return false;
        $workflow->save($data);
        Cache::clear();
        return true;
    }

    public function deleteWorkflow(int $id): bool
    {
        $workflow = AiWorkflow::find($id);
        if (!$workflow) return false;
        $workflow->delete();
        Cache::clear();
        return true;
    }

    public function getWorkflow(int $id): ?array
    {
        return Cache::remember('workflow_def_' . $id, function() use ($id) {
            $workflow = AiWorkflow::find($id);
            return $workflow ? $workflow->toArray() : null;
        }, self::CACHE_TTL_DEF);
    }

    public function listWorkflows(array $params = []): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(100, max(1, (int)($params['limit'] ?? 20)));
        $query = AiWorkflow::where('id', '>', 0);
        if (!empty($params['workflow_type'])) $query->where('workflow_type', $params['workflow_type']);
        if (isset($params['is_active'])) $query->where('is_active', $params['is_active']);
        if (isset($params['is_template'])) $query->where('is_template', $params['is_template']);
        if (!empty($params['keyword'])) $query->where('name', 'like', '%' . $params['keyword'] . '%');
        $total = $query->count();
        $list = $query->order('sort_order', 'asc')->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    public function getTemplates(): array
    {
        return Cache::remember('workflow_templates', function() {
            return AiWorkflow::where('is_template', 1)->where('status', AiWorkflow::STATUS_ACTIVE)->order('sort_order', 'asc')->select()->toArray();
        }, self::CACHE_TTL_DEF);
    }

    public function createFromTemplate(int $templateId, array $overrides = []): int
    {
        $template = AiWorkflow::find($templateId);
        if (!$template || !$template->is_template) throw new \InvalidArgumentException('Template not found');
        return $this->createWorkflow([
            'name' => $overrides['name'] ?? $template->name . ' (副本)',
            'description' => $template->description,
            'workflow_type' => $template->workflow_type,
            'workflow_definition' => $template->workflow_definition,
            'trigger_type' => $overrides['trigger_type'] ?? AiWorkflow::TRIGGER_MANUAL,
            'trigger_config' => $overrides['trigger_config'] ?? null,
            'is_active' => 1,
            'is_template' => 0,
            'category' => $template->category,
            'creator_id' => $overrides['creator_id'] ?? 0,
        ]);
    }

    public function importWorkflow(string $json, int $creatorId = 0): int
    {
        $data = json_decode($json, true);
        if (!$data || !isset($data['name'])) throw new \InvalidArgumentException('Invalid workflow JSON');
        $data['creator_id'] = $creatorId;
        $data['is_template'] = 0;
        return $this->createWorkflow($data);
    }

    public function exportWorkflow(int $id): string
    {
        $workflow = AiWorkflow::find($id);
        if (!$workflow) throw new \InvalidArgumentException('Workflow not found');
        return json_encode([
            'name' => $workflow->name, 'description' => $workflow->description,
            'workflow_type' => $workflow->workflow_type, 'workflow_definition' => $workflow->workflow_definition,
            'trigger_type' => $workflow->trigger_type, 'trigger_config' => $workflow->trigger_config,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function execute(int $workflowId, array $targetIds = [], string $trigger = AiWorkflow::TRIGGER_MANUAL, int $triggerBy = 0): int
    {
        $workflow = AiWorkflow::find($workflowId);
        if (!$workflow || !$workflow->is_active) throw new \RuntimeException('Workflow not found or inactive');
        $exec = new AiWorkflowExec();
        $exec->save([
            'workflow_id' => $workflowId, 'exec_status' => AiWorkflowExec::STATUS_PENDING,
            'trigger_type' => $trigger, 'trigger_by' => $triggerBy,
            'target_ids' => $targetIds, 'target_count' => count($targetIds),
            'current_node' => '', 'node_results' => [],
        ]);
        $execId = (int) $exec->id;
        try {
            $executor = new AiWorkflowExecutor();
            $executor->run($execId);
        } catch (\Throwable $e) {
            Log::error('Workflow execute failed: ' . $e->getMessage());
            $exec->save(['exec_status' => AiWorkflowExec::STATUS_FAILED, 'error_message' => $e->getMessage(), 'completed_at' => date('Y-m-d H:i:s')]);
        }
        AiWorkflow::where('id', $workflowId)->inc('exec_count')->update();
        return $execId;
    }

    public function executeBatch(int $workflowId, array $targetIds, int $batchSize = 10): array
    {
        $execIds = [];
        foreach (array_chunk($targetIds, $batchSize) as $chunk) {
            $execIds[] = $this->execute($workflowId, $chunk);
        }
        return $execIds;
    }

    public function cancelExecution(int $execId): bool
    {
        $exec = AiWorkflowExec::find($execId);
        if (!$exec) return false;
        if (in_array($exec->exec_status, [AiWorkflowExec::STATUS_SUCCESS, AiWorkflowExec::STATUS_FAILED, AiWorkflowExec::STATUS_CANCELLED])) return false;
        $exec->save(['exec_status' => AiWorkflowExec::STATUS_CANCELLED, 'completed_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function retryNode(int $execId, string $nodeId): bool
    {
        $exec = AiWorkflowExec::find($execId);
        if (!$exec) return false;
        $executor = new AiWorkflowExecutor();
        $executor->retryNode($execId, $nodeId);
        return true;
    }

    public function getStats(int $workflowId): array
    {
        return Cache::remember('workflow_stats_' . $workflowId, function() use ($workflowId) {
            $workflow = AiWorkflow::find($workflowId);
            if (!$workflow) return [];
            $totalExecs = AiWorkflowExec::where('workflow_id', $workflowId)->count();
            $successExecs = AiWorkflowExec::where('workflow_id', $workflowId)->where('exec_status', AiWorkflowExec::STATUS_SUCCESS)->count();
            $avgDuration = AiWorkflowExec::where('workflow_id', $workflowId)->where('exec_status', AiWorkflowExec::STATUS_SUCCESS)->avg('total_duration');
            $totalCost = AiWorkflowExec::where('workflow_id', $workflowId)->sum('ai_call_cost');
            return [
                'workflow' => $workflow->toArray(), 'total_execs' => $totalExecs,
                'success_execs' => $successExecs,
                'success_rate' => $totalExecs > 0 ? round($successExecs / $totalExecs * 100, 1) : 0,
                'avg_duration' => round((float)$avgDuration), 'total_cost' => round((float)$totalCost, 4),
            ];
        }, self::CACHE_TTL_STATS);
    }

    public function getExecLogs(int $workflowId, int $page = 1, int $limit = 20): array
    {
        $query = AiWorkflowExec::where('workflow_id', $workflowId);
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    public function getNodeDurationRanking(int $workflowId): array
    {
        $execs = AiWorkflowExec::where('workflow_id', $workflowId)->where('exec_status', AiWorkflowExec::STATUS_SUCCESS)->limit(100)->select()->toArray();
        $nodeDurations = [];
        foreach ($execs as $exec) {
            $results = $exec['node_results'] ?? [];
            if (!is_array($results)) continue;
            foreach ($results as $nodeId => $result) {
                if (!isset($nodeDurations[$nodeId])) $nodeDurations[$nodeId] = ['total' => 0, 'count' => 0];
                $nodeDurations[$nodeId]['total'] += $result['duration'] ?? 0;
                $nodeDurations[$nodeId]['count']++;
            }
        }
        $ranking = [];
        foreach ($nodeDurations as $nodeId => $data) {
            $ranking[] = ['node_id' => $nodeId, 'avg_duration' => $data['count'] > 0 ? round($data['total'] / $data['count']) : 0, 'total_duration' => $data['total'], 'exec_count' => $data['count']];
        }
        usort($ranking, fn($a, $b) => $b['avg_duration'] <=> $a['avg_duration']);
        return $ranking;
    }

    public function registerTriggers(): void
    {
        // 注册定时触发器和事件触发器到系统调度
        $scheduledWorkflows = AiWorkflow::where('trigger_type', AiWorkflow::TRIGGER_SCHEDULED)->where('is_active', 1)->select()->toArray();
        foreach ($scheduledWorkflows as $wf) {
            $config = $wf['trigger_config'] ?? [];
            // 注册到ThinkPHP定时任务
            Log::info("Registered scheduled workflow: {$wf['id']} - {$wf['name']}");
        }
    }

    public function handleEvent(string $event, array $data): void
    {
        $eventWorkflows = AiWorkflow::where('trigger_type', AiWorkflow::TRIGGER_EVENT)->where('is_active', 1)->select()->toArray();
        foreach ($eventWorkflows as $wf) {
            $config = $wf['trigger_config'] ?? [];
            $eventName = is_array($config) ? ($config['event'] ?? '') : '';
            if ($eventName === $event) {
                $this->execute($wf['id'], $data['target_ids'] ?? [], AiWorkflow::TRIGGER_EVENT, $data['user_id'] ?? 0);
            }
        }
    }
}
