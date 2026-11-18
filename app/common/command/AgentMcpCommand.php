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

namespace app\common\command;

use app\common\service\agent\ActionRegistry;
use app\common\service\agent\AgentInvokeService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * AI 助手 · MCP Server（ACP-14 / S4-T3）
 *
 * 依据 动作层规格 v1.2 §4.8 + 计划 A-9 裁定：
 *  - 走 CLI stdio（JSON-RPC 2.0，换行分隔），**不做常驻 HTTP**
 *  - 工具名 = FQN 的 `.` 换 `_`（`aicms.content.article.list` → `aicms_content_article_list`），
 *    反向映射用「名字→action_id 表」精确还原，避免把 action_id 里的下划线误拆
 *  - tools/list 与 GET /api/agent/v1/actions **同源**（都取 ActionRegistry::all()），天然一致
 *  - 执行一律经 AgentInvokeService → ActionExecutor（R-1：四通道同源，零 glue code）
 *  - stdout 只允许 JSON-RPC：启动即 ob_start() 兜住任何意外 echo，并只经 fwrite(STDOUT) 输出
 *
 * 客户端配置（以通用 MCP 客户端为例）：
 * {
 *   "mcpServers": {
 *     "ai-cms": {
 *       "command": "php",
 *       "args": ["/path/to/ai-cms/think", "agent:mcp"]
 *     }
 *   }
 * }
 *
 * 命令行自测：
 *   echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | php think agent:mcp
 */
class AgentMcpCommand extends Command
{
    /** MCP 协议版本（2024-11-05 为当前广泛兼容版本） */
    private const PROTOCOL_VERSION = '2024-11-05';
    private const SERVER_NAME = 'ai-cms-agent';
    private const SERVER_VERSION = '1.0.0';

    /** @var array<string,string> MCP 工具名 → action_id */
    private array $toolMap = [];

    /** @var array<int,array> 工具定义缓存 */
    private array $tools = [];

    private AgentInvokeService $service;
    private array $identity = [];

    protected function configure()
    {
        $this->setName('agent:mcp')
            ->addOption('token', 't', Option::VALUE_OPTIONAL, '机器身份 api_key（缺省用本机管理员代操作）', '')
            ->setDescription('启动 AI 助手 MCP Server（stdio · JSON-RPC 2.0，供外部 AI 工具挂载）');
    }

    protected function execute(Input $input, Output $output)
    {
        // stdout 纯净：任何意外 echo 都进缓冲后丢弃，只有 fwrite(STDOUT) 会真正下发
        ob_start();
        ini_set('display_errors', 'stderr');

        try {
            $this->identity = AgentInvokeService::resolveCliIdentity((string) $input->getOption('token'));
            if (empty($this->identity['ok'])) {
                $this->send([
                    'jsonrpc' => '2.0',
                    'id' => null,
                    'error' => ['code' => -32000, 'message' => '机器身份校验失败：' . $this->identity['message']],
                ]);
                ob_end_clean();
                return 1;
            }

            $this->service = new AgentInvokeService();
            $this->buildTools();

            // 主循环：换行分隔的 JSON-RPC（MCP stdio 传输标准）
            while (($line = fgets(STDIN)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                // 兼容 LSP 风格 Content-Length 头（少数客户端仍会发）
                if (stripos($line, 'Content-Length:') === 0) {
                    $length = (int) trim(substr($line, strlen('Content-Length:')));
                    if ($length > 0) {
                        $line = trim((string) fread(STDIN, $length));
                    } else {
                        continue;
                    }
                }
                $this->handleMessage($line);
            }
        } catch (\Throwable $e) {
            Log::error('MCP server fatal: ' . $e->getMessage());
            fwrite(STDERR, 'MCP server fatal: ' . $e->getMessage() . PHP_EOL);
            ob_end_clean();
            return 1;
        }

        ob_end_clean();
        return 0;
    }

    // ==================== JSON-RPC 分发 ====================

    private function handleMessage(string $line): void
    {
        $msg = json_decode($line, true);
        if (!is_array($msg)) {
            $this->send(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']]);
            return;
        }

        $method = (string) ($msg['method'] ?? '');
        $id = $msg['id'] ?? null;
        $params = is_array($msg['params'] ?? null) ? $msg['params'] : [];

        // 通知类（无 id）不响应：notifications/initialized、notifications/cancelled 等
        if (!array_key_exists('id', $msg)) {
            return;
        }

        switch ($method) {
            case 'initialize':
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => [
                    'protocolVersion' => self::PROTOCOL_VERSION,
                    'capabilities' => ['tools' => ['listChanged' => false]],
                    'serverInfo' => ['name' => self::SERVER_NAME, 'version' => self::SERVER_VERSION],
                ]]);
                return;

            case 'ping':
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => new \stdClass()]);
                return;

            case 'tools/list':
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => ['tools' => array_values($this->tools)]]);
                return;

            case 'tools/call':
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'result' => $this->callTool($params)]);
                return;

            default:
                $this->send(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                    'code' => -32601,
                    'message' => 'Method not found: ' . $method,
                ]]);
        }
    }

    /** tools/call：名字 → action_id → AgentInvokeService（与 API/CLI/窗口同源） */
    private function callTool(array $params): array
    {
        $name = (string) ($params['name'] ?? '');
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        $actionId = $this->toolMap[$name] ?? '';
        if ($actionId === '') {
            return [
                'content' => [['type' => 'text', 'text' => '未知工具：' . $name . '（请先 tools/list 获取可用工具）']],
                'isError' => true,
            ];
        }

        $result = $this->service->invoke($actionId, $arguments, $this->identity['scopes'], [
            'channel' => 'mcp',
            'actor_type' => $this->identity['actor_type'],
            'actor_id' => $this->identity['actor_id'],
            'agent_name' => $this->identity['agent_name'],
            'idempotency_key' => 'mcp_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)),
            'dry_run' => false,
        ]);

        Log::info('MCP tools/call tool=' . $name . ' action=' . $actionId . ' status=' . $result['status']
            . ' request_id=' . (string) ($result['body']['request_id'] ?? ''));

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]],
            'isError' => (int) $result['status'] !== 200,
        ];
    }

    // ==================== 工具定义 ====================

    /** 由 ActionRegistry 生成工具清单（与 GET /actions 同源，不写死） */
    private function buildTools(): void
    {
        foreach (ActionRegistry::all() as $def) {
            $actionId = (string) $def['action_id'];
            $toolName = self::toToolName((string) $def['fqn']);

            $schema = is_array($def['input_schema'] ?? null) ? $def['input_schema'] : [];
            if (!isset($schema['type'])) {
                $schema['type'] = 'object';
            }
            if (empty($schema['properties'])) {
                $schema['properties'] = new \stdClass();
            }

            $description = trim((string) ($def['display_name'] ?? '') . ' ' . (string) ($def['description'] ?? ''));
            $this->tools[] = [
                'name' => $toolName,
                'description' => '[' . (string) $def['scope'] . '] ' . ($description !== '' ? $description : $actionId),
                'inputSchema' => $schema,
            ];
            $this->toolMap[$toolName] = $actionId;
        }
    }

    /** FQN → MCP 工具名（`.` 换 `_`） */
    public static function toToolName(string $fqn): string
    {
        return str_replace('.', '_', $fqn);
    }

    // ==================== 输出 ====================

    private function send(array $payload): void
    {
        fwrite(STDOUT, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        fflush(STDOUT);
    }
}
