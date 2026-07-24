<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint AI3: SEO诊断CLI命令
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\command;

use app\common\service\ai\AiSeoDiagnosisService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * SEO诊断CLI命令 - V2.9.31 AI3-3
 * 用法：php think seo:diagnose [content_id] [--all] [--fix]
 */
class SeoDiagnose extends Command
{
    protected function configure()
    {
        $this->setName('seo:diagnose')
            ->setDescription('AI SEO诊断：分析内容SEO质量并输出报告')
            ->addArgument('content_id', Argument::OPTIONAL, '内容ID（不指定则诊断全部）')
            ->addOption('all', 'a', Option::VALUE_NONE, '诊断所有内容')
            ->addOption('fix', 'f', Option::VALUE_NONE, '自动修复可修复的问题')
            ->addOption('limit', 'l', Option::VALUE_REQUIRED, '限制诊断数量', 100);
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new AiSeoDiagnosisService();
        $contentId = $input->getArgument('content_id');
        $isAll = $input->hasOption('all') && $input->getOption('all');
        $isFix = $input->hasOption('fix') && $input->getOption('fix');
        $limit = (int) $input->getOption('limit');

        if ($contentId) {
            // 单条诊断
            $result = $service->diagnose((int) $contentId);
            $this->outputResult($output, $result);
            return;
        }

        if ($isAll) {
            // 批量诊断
            $ids = \app\common\model\Content::limit($limit)->column('id');
            $output->writeln("开始诊断 " . count($ids) . " 条内容...");

            $lowScoreCount = 0;
            foreach ($ids as $id) {
                $result = $service->diagnose((int) $id);
                if ($result['success'] && $result['score'] < 60) {
                    $lowScoreCount++;
                    $output->writeln("  [低分] ID={$id} 评分={$result['score']}");
                }
            }

            $output->writeln("诊断完成，低分内容（<60分）：{$lowScoreCount} 条");
            return;
        }

        // 默认：输出全站SEO概况
        $overview = $service->getSiteOverview();
        $output->writeln("========== 全站SEO概况 ==========");
        $output->writeln("内容总数：{$overview['total_content']}");
        $output->writeln("有SEO标题：{$overview['with_title']} ({$overview['title_rate']}% )");
        $output->writeln("有SEO描述：{$overview['with_desc']} ({$overview['desc_rate']}% )");
        $output->writeln("有SEO关键词：{$overview['with_keywords']} ({$overview['keyword_rate']}% )");
        $output->writeln("平均SEO评分：{$overview['avg_score']}");
        $output->writeln("抽样数量：{$overview['sample_count']}");
    }

    private function outputResult(Output $output, array $result): void
    {
        if (!($result['success'] ?? false)) {
            $output->writeln("<error>诊断失败：{$result['message']}</error>");
            return;
        }

        $output->writeln("========== SEO诊断报告 ==========");
        $output->writeln("内容ID：{$result['content_id']}");
        $output->writeln("综合评分：{$result['score']}/100");
        $output->writeln("");

        if (!empty($result['issues'])) {
            $output->writeln("发现 " . count($result['issues']) . " 个问题：");
            foreach ($result['issues'] as $issue) {
                $severity = match ($issue['severity']) {
                    'high' => '<error>[严重]</error>',
                    'medium' => '<comment>[一般]</comment>',
                    default => '<info>[轻微]</info>',
                };
                $output->writeln("  {$severity} {$issue['message']}");
            }
        } else {
            $output->writeln("<info>未发现明显SEO问题</info>");
        }

        if (!empty($result['suggestions'])) {
            $output->writeln("");
            $output->writeln("优化建议：");
            foreach ($result['suggestions'] as $suggestion) {
                $output->writeln("  - {$suggestion}");
            }
        }

        $output->writeln("");
        $output->writeln("统计信息：");
        foreach ($result['stats'] as $key => $val) {
            $output->writeln("  {$key}: {$val}");
        }
    }
}
