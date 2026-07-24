<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiTaskQueue;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI智能体服务
 * V2.9.38 AI-PLUS-3
 * 三层架构: AiAgentService(管理) + AiAgentExecutor(执行) + AiAgentMemory(记忆)
 * 基于AiConversationService扩展，配置存i8j_system_config JSON字段
 */
class AiAgentService
{
    protected const CACHE_TAG = 'agent';
    protected const CACHE_TTL = 3600;

    // 预设智能体配置
    protected const PRESET_AGENTS = [
        'content_editor' => [
            'name' => '内容编辑助手',
            'role' => '你是一个专业的内容编辑，擅长内容创作、润色和优化',
            'capabilities' => ['ai_write', 'ai_summary', 'ai_qa'],
            'trigger' => 'event',
            'trigger_config' => ['event' => 'content.submitted'],
            'rules' => ['自动润色内容', '检查语法错误', '优化SEO', '关键操作需人工确认'],
            'permissions' => ['content.edit', 'content.publish'],
            'style' => 'professional',
            'max_autonomous_actions' => 5,
        ],
        'operation_analyst' => [
            'name' => '运营分析助手',
            'role' => '你是一个运营数据分析师，擅长数据解读和运营建议',
            'capabilities' => ['ai_summary', 'ai_recommend'],
            'trigger' => 'scheduled',
            'trigger_config' => ['cron' => '0 8 * * *'],
            'rules' => ['每日8:00生成运营日报', '识别异常指标', '给出运营建议'],
            'permissions' => ['dashboard.view', 'report.view'],
            'style' => 'analytical',
            'max_autonomous_actions' => 3,
        ],
        'seo_optimizer' => [
            'name' => 'SEO优化助手',
            'role' => '你是一个SEO专家，擅长搜索引擎优化和排名提升',
            'capabilities' => ['ai_seo', 'ai_write', 'ai_qa'],
            'trigger' => 'condition',
            'trigger_config' => ['condition' => 'content.seo_score < 70'],
            'rules' => ['检测SEO问题', '自动修复常见问题', '生成优化建议', '重大修改需确认'],
            'permissions' => ['content.edit', 'seo.optimize'],
            'style' => 'professional',
            'max_autonomous_actions' => 10,
        ],
        'user_service' => [
            'name' => '用户服务助手',
            'role' => '你是一个用户服务代表，擅长解答用户问题和处理用户反馈',
            'capabilities' => ['ai_qa', 'ai_summary'],
            'trigger' => 'event',
            'trigger_config' => ['event' => 'user.message'],
            'rules' => ['自动回复常见问题', '收集用户反馈', '复杂问题转人工'],
            'permissions' => ['message.reply', 'user.view'],
            'style' => 'friendly',
            'max_autonomous_actions' => 20,
        ],
        'content_auditor' => [
            'name' => '内容审核助手',
            'role' => '你是一个内容审核员，擅长识别违规内容和敏感信息',
            'capabilities' => ['ai_qa'],
            'trigger' => 'event',
            'trigger_config' => ['event' => 'content.submitted'],
            'rules' => ['检测违规内容', '识别敏感信息', '生成审核报告', '可疑内容标记人工审核'],
            'permissions' => ['content.audit', 'content.reject'],
            'style' => 'strict',
            'max_autonomous_actions' => 0,
        ],
    ];

    /**
     * 创建智能体
     */
    public function createAgent(array $config): string
    {
        $agentId = 'agent_' . uniqid();
        $agents = $this->getAllAgents();
        $config['id'] = $agentId;
        $config['created_at'] = date('Y-m-d H:i:s');
        $config['exec_count'] = 0;
        $config['success_count'] = 0;
        $config['is_active'] = $config['is_active'] ?? true;
        $agents[$agentId] = $config;
        $this->saveAgents($agents);
        Cache::clear();
        return $agentId;
    }

    /**
     * 更新智能体
     */
    public function updateAgent(string $agentId, array $data): bool
    {
        $agents = $this->getAllAgents();
        if (!isset($agents[$agentId])) return false;
        $agents[$agentId] = array_merge($agents[$agentId], $data);
        $this->saveAgents($agents);
        Cache::clear();
        return true;
    }

    /**
     * 删除智能体
     */
    public function deleteAgent(string $agentId): bool
    {
        $agents = $this->getAllAgents();
        if (!isset($agents[$agentId])) return false;
        unset($agents[$agentId]);
        $this->saveAgents($agents);
        Cache::clear();
        return true;
    }

    /**
     * 获取智能体
     */
    public function getAgent(string $agentId): ?array
    {
        $agents = $this->getAllAgents();
        return $agents[$agentId] ?? null;
    }

    /**
     * 获取所有智能体
     */
    public function getAllAgents(): array
    {
        $config = Db::name('system_config')->where('config_key', 'ai_agents')->value('config_value');
        $agents = $config ? json_decode($config, true) : [];
        if (!is_array($agents)) $agents = [];
        // 合并预设智能体
        foreach (self::PRESET_AGENTS as $key => $preset) {
            if (!isset($agents[$key])) {
                $agents[$key] = array_merge(['id' => $key, 'is_preset' => true, 'is_active' => true, 'exec_count' => 0, 'success_count' => 0], $preset);
            }
        }
        return $agents;
    }

    /**
     * 列出智能体
     */
    public function listAgents(array $params = []): array
    {
        $agents = $this->getAllAgents();
        $list = array_values($agents);
        if (!empty($params['keyword'])) {
            $list = array_filter($list, fn($a) => stripos($a['name'] ?? '', $params['keyword']) !== false);
        }
        if (isset($params['is_active'])) {
            $list = array_filter($list, fn($a) => ($a['is_active'] ?? false) === (bool)$params['is_active']);
        }
        return ['total' => count($list), 'list' => array_values($list)];
    }

    /**
     * 运行智能体
     */
    public function run(string $agentId, string $task, array $context = []): int
    {
        $agent = $this->getAgent($agentId);
        if (!$agent || !($agent['is_active'] ?? false)) {
            throw new \RuntimeException('Agent not found or inactive');
        }

        // 创建任务记录(复用i8j_ai_task_queue)
        $taskRecord = new AiTaskQueue();
        $taskRecord->save([
            'task_type' => 'agent',
            'agent_id' => $agentId,
            'agent_session_id' => 'sess_' . uniqid(),
            'task_data' => json_encode(['task' => $task, 'context' => $context]),
            'agent_plan' => null,
            'agent_memory' => null,
            'status' => 'pending',
            'priority' => 0,
        ]);
        $taskId = (int) $taskRecord->id;

        // 执行
        try {
            $executor = new AiAgentExecutor();
            $executor->execute($taskId, $agent, $task, $context);
            
            // 更新统计
            $agents = $this->getAllAgents();
            if (isset($agents[$agentId])) {
                $agents[$agentId]['exec_count'] = ($agents[$agentId]['exec_count'] ?? 0) + 1;
                $agents[$agentId]['success_count'] = ($agents[$agentId]['success_count'] ?? 0) + 1;
                $this->saveAgents($agents);
            }
        } catch (\Throwable $e) {
            Log::error("Agent {$agentId} execution failed: " . $e->getMessage());
            $taskRecord->save(['status' => 'failed', 'error_message' => $e->getMessage()]);
            
            $agents = $this->getAllAgents();
            if (isset($agents[$agentId])) {
                $agents[$agentId]['exec_count'] = ($agents[$agentId]['exec_count'] ?? 0) + 1;
                $this->saveAgents($agents);
            }
        }

        return $taskId;
    }

    /**
     * 定时执行智能体
     */
    public function runScheduled(): array
    {
        $agents = $this->getAllAgents();
        $results = [];
        foreach ($agents as $id => $agent) {
            if (($agent['trigger'] ?? '') !== 'scheduled') continue;
            if (!($agent['is_active'] ?? false)) continue;
            $cron = $agent['trigger_config']['cron'] ?? '';
            if (empty($cron)) continue;
            // 简化: 每次都执行(实际应解析cron表达式判断是否到达执行时间)
            $taskId = $this->run($id, $agent['trigger_config']['task'] ?? '执行定时任务');
            $results[] = ['agent_id' => $id, 'task_id' => $taskId];
        }
        return $results;
    }

    /**
     * 获取监控数据
     */
    public function getMonitorData(string $agentId = ''): array
    {
        $agents = $this->getAllAgents();
        $data = [];
        foreach ($agents as $id => $agent) {
            if ($agentId && $id !== $agentId) continue;
            $execCount = $agent['exec_count'] ?? 0;
            $successCount = $agent['success_count'] ?? 0;
            $recentExecs = AiTaskQueue::where('agent_id', $id)->order('id', 'desc')->limit(10)->select()->toArray();
            $data[] = [
                'agent_id' => $id,
                'name' => $agent['name'] ?? '',
                'exec_count' => $execCount,
                'success_count' => $successCount,
                'success_rate' => $execCount > 0 ? round($successCount / $execCount * 100, 1) : 0,
                'is_active' => $agent['is_active'] ?? false,
                'trigger' => $agent['trigger'] ?? 'manual',
                'recent_execs' => $recentExecs,
            ];
        }
        return $data;
    }

    protected function saveAgents(array $agents): void
    {
        $exists = Db::name('system_config')->where('config_key', 'ai_agents')->find();
        if ($exists) {
            Db::name('system_config')->where('config_key', 'ai_agents')->update(['config_value' => json_encode($agents, JSON_UNESCAPED_UNICODE)]);
        } else {
            Db::name('system_config')->insert(['config_key' => 'ai_agents', 'config_value' => json_encode($agents, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
        }
    }
}
