<?php
declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\service\theme\ThemeQualityService;

/**
 * 模板质量每日扫描命令 — V2.9.30 Q-6
 * Cron: 0 3 * * * （每天凌晨3点）
 */
class TemplateQualityScanCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('template:quality_scan')
             ->setDescription('扫描全量模板并更新质量评分');
    }

    protected function execute(Input $input, Output $output): void
    {
        $output->writeln('🔍 开始扫描全量模板质量...');

        $service = new ThemeQualityService();
        $results = $service->scanAllTemplates();

        $output->writeln('');
        $output->writeln('═══════════════════════════════════════════');
        $output->writeln('  扫描完成');
        $output->writeln('═══════════════════════════════════════════');
        $output->writeln('  总模板数: ' . $results['total']);
        $output->writeln('  优秀(≥80): ' . $results['excellent']);
        $output->writeln('  合格(60-79): ' . $results['pass']);
        $output->writeln('  不合格(<60): ' . $results['fail']);
        $output->writeln('═══════════════════════════════════════════');

        if (!empty($results['details'])) {
            $output->writeln('');
            $output->writeln('详细评分:');
            foreach ($results['details'] as $d) {
                $status = $d['score'] >= 80 ? '✅优秀' : ($d['score'] >= 60 ? '⚠️合格' : '❌不合格');
                $output->writeln(sprintf('  %s: %d分 (质量%d+检测%d) %s',
                    $d['theme'], $d['score'], $d['quality_score'], $d['detector_score'], $status));
            }
        }

        if ($results['fail'] > 0) {
            $output->writeln('');
            $output->warning("⚠️  有 {$results['fail']} 个模板评分不合格(<60分)，建议优化后重新上架");
        }
    }
}
