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
 * Agent 能力发现命令（ACP-08 / S2-T6）
 *
 * 与 GET /api/agent/v1/actions 同源（同一个 AgentInvokeService::discover / ActionRegistry::all），
 * 严禁在本命令内另写动作清单。
 *
 * 用法：
 *   php think agent:actions
 *   php think agent:actions --json          # 输出与 API data 完全同构的 JSON
 *   php think agent:actions --domain=content
 */
class AgentActionsCommand extends Command
{
    protected function configure()
    {
        $this->setName('agent:actions')
            ->addOption('json', 'j', Option::VALUE_NONE, '以 JSON 输出（与 GET /api/agent/v1/actions 的 data 同构）')
            ->addOption('domain', 'd', Option::VALUE_OPTIONAL, '仅显示指定 domain 的动作', '')
            ->setDescription('列出 AI 助手可调用的动作能力（与 API 同源）');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new AgentInvokeService();
        $discover = $service->discover();

        $domain = trim((string)$input->getOption('domain'));
        if ($domain !== '') {
            $discover['actions'] = array_values(array_filter(
                $discover['actions'],
                static fn(array $a): bool => str_starts_with((string)$a['action_id'], $domain . '.')
            ));
            $discover['count'] = count($discover['actions']);
        }

        if ($input->hasOption('json') && $input->getOption('json')) {
            $output->writeln(json_encode($discover, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $output->writeln('');
        $output->writeln("<info>AI 助手动作能力（version {$discover['version']} · 共 {$discover['count']} 个）</info>");
        $output->writeln(str_repeat('-', 106));
        $output->writeln(
            str_pad('action_id', 32) . str_pad('scope', 24) . str_pad('risk', 7)
            . str_pad('source_table', 22) . 'display_name'
        );
        $output->writeln(str_repeat('-', 106));

        foreach ($discover['actions'] as $action) {
            $output->writeln(
                str_pad((string)$action['action_id'], 32)
                . str_pad((string)$action['scope'], 24)
                . str_pad((string)$action['risk_level'], 7)
                . str_pad((string)$action['source_table'], 22)
                . (string)$action['display_name']
            );
        }

        $output->writeln(str_repeat('-', 106));
        $output->writeln('调用示例：php think agent:run --action=content.article.list --params=\'{"limit":5}\'');

        return 0;
    }
}
