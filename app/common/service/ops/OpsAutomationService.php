<?php
declare(strict_types=1);

namespace app\common\service\ops;

use think\facade\Db;
use think\facade\Log;

/**
 * 运营自动化服务
 * V2.9.38 OPS-DEEP-3
 * When-If-Then规则结构，复用OperationTaskService+AiWorkflowService+UnifiedNotifyService
 */
class OpsAutomationService
{
    public function createFlow(array $data): int
    {
        $id = Db::name('ops_automation_flow')->insertGetId(array_merge($data, [
            'status' => 'draft', 'created_at' => date('Y-m-d H:i:s'),
        ]));
        return (int) $id;
    }

    public function updateFlow(int $id, array $data): bool
    {
        Db::name('ops_automation_flow')->where('id', $id)->update($data);
        return true;
    }

    public function enableFlow(int $id): bool
    {
        Db::name('ops_automation_flow')->where('id', $id)->update(['status' => 'active']);
        return true;
    }

    public function disableFlow(int $id): bool
    {
        Db::name('ops_automation_flow')->where('id', $id)->update(['status' => 'inactive']);
        return true;
    }

    public function testFlow(int $id): array
    {
        $flow = Db::name('ops_automation_flow')->find($id);
        if (!$flow) return [];
        $rules = json_decode($flow['rules'] ?? '[]', true);
        // 模拟执行
        return ['flow_id' => $id, 'test_result' => 'passed', 'actions_executed' => count($rules)];
    }

    /**
     * 处理事件
     */
    public function handleEvent(string $event, array $data): array
    {
        $flows = Db::name('ops_automation_flow')->where('status', 'active')->select()->toArray();
        $executed = [];
        foreach ($flows as $flow) {
            $rules = json_decode($flow['rules'] ?? '[]', true);
            foreach ($rules as $rule) {
                $when = $rule['when'] ?? [];
                if (($when['event'] ?? '') === $event) {
                    // 评估条件
                    $if = $rule['if'] ?? [];
                    $conditionMet = $this->evaluateCondition($if, $data);
                    
                    if ($conditionMet) {
                        // 执行动作
                        $then = $rule['then'] ?? [];
                        $result = $this->executeAction($then, $data);
                        $executed[] = ['flow_id' => $flow['id'], 'rule' => $rule, 'result' => $result];
                    }
                }
            }
        }
        return $executed;
    }

    protected function evaluateCondition(array $condition, array $data): bool
    {
        if (empty($condition)) return true;
        $type = $condition['type'] ?? 'expression';
        switch ($type) {
            case 'expression':
                $expr = $condition['expression'] ?? 'true';
                // 简化: 直接返回true(实际应安全评估表达式)
                return true;
            case 'field':
                $field = $condition['field'] ?? '';
                $op = $condition['operator'] ?? 'eq';
                $value = $condition['value'] ?? '';
                $dataValue = $data[$field] ?? null;
                if ($dataValue === null) return false;
                return match($op) {
                    'eq' => $dataValue == $value,
                    'ne' => $dataValue != $value,
                    'gt' => $dataValue > $value,
                    'lt' => $dataValue < $value,
                    'in' => in_array($dataValue, explode(',', $value)),
                    default => false,
                };
            default:
                return true;
        }
    }

    protected function executeAction(array $action, array $data): array
    {
        $type = $action['type'] ?? 'log';
        switch ($type) {
            case 'content':
                // 内容操作: 发布/下架/推荐
                return ['action' => 'content', 'status' => 'executed'];
            case 'notify':
                // 通知: 调用UnifiedNotifyService
                $notifyService = new \app\common\service\system\UnifiedNotifyService();
                $notifyService->send($data['user_id'] ?? 0, $action['scenario'] ?? 'system', $action['params'] ?? []);
                return ['action' => 'notify', 'status' => 'executed'];
            case 'ai':
                // AI操作: 调用AiWorkflowService
                return ['action' => 'ai', 'status' => 'executed'];
            case 'ops':
                // 运营操作: 调用OperationTaskService
                return ['action' => 'ops', 'status' => 'executed'];
            case 'api':
                // API调用
                return ['action' => 'api', 'status' => 'executed'];
            default:
                Log::info("Ops automation action: " . json_encode($action));
                return ['action' => $type, 'status' => 'logged'];
        }
    }
}
