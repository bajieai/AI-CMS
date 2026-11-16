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

use app\common\service\agent\AgentInvokeService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * Agent 单动作执行命令（ACP-08 / S2-T6）
 *
 * 硬要求（动作层规格 v1.2 §4.5）：CLI 与 API 必须调同一个执行入口，
 * 同一 action_id + params 的结果逐字段一致；--dry-run 语义一致（零落库）。
 *
 * 用法：
 *   php think agent:run --action=content.article.list --params='{"limit":5}'
 *   php think agent:run --action=mockdata.content.batch_create --params='{"count":100}' --dry-run
 *   php think agent:run --action=content.article.list --params='{"limit":5}' --raw   # 与 API 响应体同构的紧凑 JSON
 *   php think agent:run --action=config.get --token=ak_xxx                          # 按机器身份 scopes 判定越权
 */
class AgentRunCommand extends Command
{
    protected function configure()
    {
        $this->setName('agent:run')
            ->addOption('action', 'a', Option::VALUE_REQUIRED, '动作 ID（原生 ID 或对外 FQN）')
            ->addOption('params', 'p', Option::VALUE_OPTIONAL, '动作参数 JSON，如 {"limit":5}', '{}')
            ->addOption('params-file', null, Option::VALUE_OPTIONAL, '动作参数 JSON 文件路径（Windows PowerShell 会吞双引号，推荐此方式）', '')
            ->addOption('dry-run', null, Option::VALUE_NONE, '仅预览不落库（R-5）')
            ->addOption('idempotency-key', 'k', Option::VALUE_OPTIONAL, '幂等键（缺省自动生成）', '')
            ->addOption('token', 't', Option::VALUE_OPTIONAL, '机器身份 api_key（缺省用本机管理员代操作）', '')
            ->addOption('raw', 'r', Option::VALUE_NONE, '输出与 API 响应体同构的紧凑 JSON（供 A-2 逐字段比对）')
            ->setDescription('按机器身份执行一个 AI 助手动作（与 API 同源）');
    }

    protected function execute(Input $input, Output $output)
    {
        $actionId = trim((string)$input->getOption('action'));
        if ($actionId === '') {
            $output->error('缺少 --action 参数，示例：php think agent:run --action=content.article.list --params=\'{"limit":5}\'');
            return 1;
        }

        $paramsRaw = (string)$input->getOption('params');
        $paramsFile = trim((string)$input->getOption('params-file'));
        if ($paramsFile !== '') {
            if (!is_file($paramsFile)) {
                $output->error('--params-file 文件不存在：' . $paramsFile);
                return 1;
            }
            $paramsRaw = (string)file_get_contents($paramsFile);
        }

        $params = $this->parseParams($paramsRaw);
        if ($params === null) {
            $output->error('--params 不是合法 JSON 对象（Windows PowerShell 会吞掉双引号，请改用 --params-file=<json文件>）');
            return 1;
        }

        $identity = AgentInvokeService::resolveCliIdentity((string)$input->getOption('token'));
        if (empty($identity['ok'])) {
            $output->error('机器身份校验失败：' . $identity['message']);
            return 1;
        }

        $idemKey = trim((string)$input->getOption('idempotency-key'));
        if ($idemKey === '') {
            $idemKey = 'cli_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
        }

        $service = new AgentInvokeService();
        $result = $service->invoke($actionId, $params, $identity['scopes'], [
            'channel'         => 'cli',
            'actor_type'      => $identity['actor_type'],
            'actor_id'        => $identity['actor_id'],
            'agent_name'      => $identity['agent_name'],
            'idempotency_key' => $idemKey,
            'dry_run'         => $input->hasOption('dry-run') && $input->getOption('dry-run'),
        ]);

        $body = $result['body'];
        $raw = $input->hasOption('raw') && $input->getOption('raw');

        if ($raw) {
            $output->writeln(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $output->writeln('');
            $output->writeln(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            $output->writeln('');
            if ((int)$result['status'] === 200) {
                $output->writeln(sprintf(
                    '<info>OK</info> action=%s request_id=%s elapsed=%sms dry_run=%s',
                    (string)($body['action_id'] ?? $actionId),
                    (string)($body['request_id'] ?? ''),
                    (string)($body['meta']['elapsed_ms'] ?? ''),
                    !empty($body['meta']['dry_run']) ? 'true' : 'false'
                ));
            } else {
                $output->writeln(sprintf(
                    '<error>FAILED</error> status=%d action=%s msg=%s',
                    (int)$result['status'],
                    (string)($body['action_id'] ?? $actionId),
                    (string)($body['msg'] ?? '')
                ));
            }
        }

        return (int)$result['status'] === 200 ? 0 : 1;
    }

    /** 解析 --params JSON，非法返回 null */
    private function parseParams(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
