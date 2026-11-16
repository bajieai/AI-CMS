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
 * Agent 批量执行命令（ACP-08 / S2-T6 · ACP-11 灌数入口）
 *
 * 硬要求（动作层规格 v1.2 §4.5 / §4.8）：零 key、零真实数据；一键灌 ≥100 条模拟数据并自动跑通；
 * 逐条独立审计 + 汇总报告；幂等键以 `<batch_id>_<index>` 派生，整批可重跑不产生脏数据。
 *
 * 用法：
 *   php think agent:batch --file=runtime/mock/seed.json
 *   php think agent:batch --generate=100                 # 一键灌 100 条模拟内容（mockdata 物理隔离表）
 *   php think agent:batch --generate=100 --dry-run       # 只报计划，不落库
 *   php think agent:batch --file=... --token=ak_xxx      # 按机器身份 scopes 判定
 *
 * 文件格式（二选一）：
 *   {"items":[{"action_id":"content.article.list","params":{"limit":5}}, ...]}
 *   [{"action_id":"content.article.list","params":{"limit":5}}, ...]
 */
class AgentBatchCommand extends Command
{
    /** 单批最大条目（与 AgentInvokeService::MAX_BATCH_ITEMS 对齐） */
    private const MAX_ITEMS = AgentInvokeService::MAX_BATCH_ITEMS;

    protected function configure()
    {
        $this->setName('agent:batch')
            ->addOption('file', 'f', Option::VALUE_OPTIONAL, '批量任务 JSON 文件路径', '')
            ->addOption('generate', 'g', Option::VALUE_OPTIONAL, '自动生成 N 条模拟数据任务（走 mockdata 动作）', '0')
            ->addOption('dry-run', null, Option::VALUE_NONE, '仅预览不落库（R-5）')
            ->addOption('token', 't', Option::VALUE_OPTIONAL, '机器身份 api_key（缺省用本机管理员代操作）', '')
            ->addOption('raw', 'r', Option::VALUE_NONE, '输出与 API 响应体同构的紧凑 JSON')
            ->setDescription('批量执行 AI 助手动作（与 API 同一幂等键域）');
    }

    protected function execute(Input $input, Output $output)
    {
        $items = [];
        $file = trim((string)$input->getOption('file'));
        $generate = (int)$input->getOption('generate');

        if ($file !== '') {
            $loaded = $this->loadItems($file);
            if ($loaded === null) {
                $output->error('批量文件读取失败或格式非法：' . $file);
                return 1;
            }
            $items = $loaded;
        }

        if ($generate > 0) {
            $items = array_merge($items, $this->buildGenerateItems($generate));
        }

        if ($items === []) {
            $output->error('缺少任务：请使用 --file=<json> 或 --generate=<N>，示例：php think agent:batch --generate=100');
            return 1;
        }

        if (count($items) > self::MAX_ITEMS) {
            $output->error('单批不超过 ' . self::MAX_ITEMS . ' 条（S-7），当前 ' . count($items) . ' 条；请拆分文件');
            return 1;
        }

        $identity = AgentInvokeService::resolveCliIdentity((string)$input->getOption('token'));
        if (empty($identity['ok'])) {
            $output->error('机器身份校验失败：' . $identity['message']);
            return 1;
        }

        $service = new AgentInvokeService();
        $result = $service->batch($items, $identity['scopes'], [
            'channel'    => 'cli',
            'actor_type' => $identity['actor_type'],
            'actor_id'   => $identity['actor_id'],
            'agent_name' => $identity['agent_name'],
            'dry_run'    => $input->hasOption('dry-run') && $input->getOption('dry-run'),
        ]);

        $body = $result['body'];

        if ($input->hasOption('raw') && $input->getOption('raw')) {
            $output->writeln(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (int)($body['data']['failed'] ?? 0) === 0 ? 0 : 1;
        }

        $data = $body['data'] ?? [];
        $output->writeln('');
        $output->writeln(sprintf(
            '<info>批量执行完成</info> batch_id=%s total=%d success=%d failed=%d dry_run=%s',
            (string)($data['batch_id'] ?? ''),
            (int)($data['total'] ?? 0),
            (int)($data['success'] ?? 0),
            (int)($data['failed'] ?? 0),
            !empty($data['dry_run']) ? 'true' : 'false'
        ));
        $output->writeln(str_repeat('-', 96));

        foreach (($data['results'] ?? []) as $row) {
            $line = sprintf(
                '#%d %-34s status=%d',
                (int)($row['index'] ?? 0),
                (string)($row['action_id'] ?? ''),
                (int)($row['status'] ?? 0)
            );
            if (!empty($row['data']['diff_summary'])) {
                $line .= ' | ' . (string)$row['data']['diff_summary'];
            } elseif (!empty($row['data']['created'])) {
                $line .= ' | created=' . (int)$row['data']['created'];
            } elseif (!empty($row['msg']) && (int)($row['status'] ?? 0) !== 200) {
                $line .= ' | ' . (string)$row['msg'];
            }
            $output->writeln($line);
        }

        $output->writeln(str_repeat('-', 96));

        return (int)($data['failed'] ?? 0) === 0 ? 0 : 1;
    }

    /**
     * 读取批量任务文件
     *
     * @return array|null null 表示读取/解析失败
     */
    private function loadItems(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        $raw = (string)file_get_contents($file);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        // 兼容 {"items":[...]} 与裸数组两种写法
        $list = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : $decoded;

        $items = [];
        foreach ($list as $row) {
            if (is_array($row) && !empty($row['action_id'])) {
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * 按 N 条自动构造模拟数据任务（走 mockdata.*，只写物理隔离的 mock 表）
     *
     * @return array<int, array{action_id:string, params:array{count:int}}>
     */
    private function buildGenerateItems(int $total): array
    {
        $items = [];
        $remain = $total;

        while ($remain > 0 && count($items) < self::MAX_ITEMS) {
            $count = min(100, $remain);
            $items[] = [
                'action_id' => 'mockdata.content.batch_create',
                'params'    => ['count' => $count],
            ];
            $remain -= $count;
        }

        return $items;
    }
}
