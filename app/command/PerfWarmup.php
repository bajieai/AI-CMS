<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint PERF: 性能优化CLI命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\command;

use app\common\service\CacheWarmupService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 性能优化CLI命令 - V2.9.31 PERF-4
 * 用法：php think perf:warmup [--all] [--config] [--category] [--template]
 */
class PerfWarmup extends Command
{
    protected function configure()
    {
        $this->setName('perf:warmup')
            ->setDescription('性能优化：缓存预热')
            ->addOption('all', 'a', Option::VALUE_NONE, '全量预热')
            ->addOption('config', 'c', Option::VALUE_NONE, '预热配置缓存')
            ->addOption('category', 'g', Option::VALUE_NONE, '预热分类缓存')
            ->addOption('template', 't', Option::VALUE_NONE, '预热模板商店缓存')
            ->addOption('stats', 's', Option::VALUE_NONE, '查看缓存统计');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new CacheWarmupService();

        if ($input->getOption('stats')) {
            $stats = $service->getStats();
            $output->writeln("========== 缓存统计 ==========");
            $output->writeln("命中次数：{$stats['hits']}");
            $output->writeln("未命中次数：{$stats['misses']}");
            $total = $stats['hits'] + $stats['misses'];
            $rate = $total > 0 ? round($stats['hits'] / $total * 100, 1) : 0;
            $output->writeln("命中率：{$rate}%");
            return;
        }

        if ($input->getOption('all')) {
            $output->writeln("开始全量缓存预热...");
            $result = $service->warmupAll();
            foreach ($result['results'] as $r) {
                if ($r['success']) {
                    $output->writeln("  [OK] {$r['type']}: {$r['count']} 条");
                } else {
                    $output->writeln("  [FAIL] {$r['type']}: {$r['message']}");
                }
            }
            $output->writeln("<info>预热完成，总计 {$result['total']} 条</info>");
            return;
        }

        if ($input->getOption('config')) {
            $result = $service->warmupConfig();
            $output->writeln($result['success'] ? "<info>配置缓存预热完成：{$result['count']} 条</info>" : "<error>失败：{$result['message']}</error>");
        }

        if ($input->getOption('category')) {
            $result = $service->warmupCategories();
            $output->writeln($result['success'] ? "<info>分类缓存预热完成：{$result['count']} 条</info>" : "<error>失败：{$result['message']}</error>");
        }

        if ($input->getOption('template')) {
            $result = $service->warmupTemplateStore();
            $output->writeln($result['success'] ? "<info>模板商店缓存预热完成：{$result['count']} 条</info>" : "<error>失败：{$result['message']}</error>");
        }

        if (!$input->getOption('config') && !$input->getOption('category') && !$input->getOption('template') && !$input->getOption('all') && !$input->getOption('stats')) {
            $output->writeln("<comment>请指定预热类型：--all / --config / --category / --template / --stats</comment>");
        }
    }
}
