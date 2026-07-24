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

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\service\content\ContentMigrationService;

/**
 * 内容模型迁移命令 (V2.9.29 C-6)
 * 
 * 用法：
 *   php think content_model:migrate          # 执行迁移
 *   php think content_model:migrate --check  # 仅检查模板一致性
 */
class ContentModelMigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('content_model:migrate')
            ->addOption('check', null, Option::VALUE_NONE, '仅检查模板一致性，不执行迁移')
            ->setDescription('V2.9.29 内容模型迁移：将旧栏目迁移到内容模型体系');
    }

    protected function execute(Input $input, Output $output): void
    {
        $service = new ContentMigrationService();

        if ($input->hasOption('check')) {
            $output->writeln('<info>=== 模板一致性检查 ===</info>');
            $report = $service->checkTemplateConsistency();
            $output->writeln("总栏目数: {$report['total_cates']}");
            $output->writeln("一致: {$report['consistent']}");
            $output->writeln("不一致: " . count($report['inconsistent']));

            if (!empty($report['inconsistent'])) {
                $output->writeln('<error>不一致详情：</error>');
                foreach ($report['inconsistent'] as $item) {
                    $output->writeln("  栏目ID {$item['cate_id']} ({$item['cate_name']}): list={$item['list_template']} detail={$item['detail_template']}");
                }
            }
            return;
        }

        $output->writeln('<info>=== 开始内容模型迁移 ===</info>');

        $migrationReport = $service->migrate();
        $output->writeln("迁移栏目总数: {$migrationReport['total']}");
        $output->writeln("成功迁移: {$migrationReport['migrated']}");
        $output->writeln("失败: " . count($migrationReport['errors']));

        $output->writeln('<info>=== 检查模板一致性 ===</info>');
        $consistencyReport = $service->checkTemplateConsistency();
        $output->writeln("一致: {$consistencyReport['consistent']}");
        $output->writeln("不一致: " . count($consistencyReport['inconsistent']));

        $summary = $service->generateReportSummary($migrationReport, $consistencyReport);
        $output->writeln('');
        $output->writeln('<info>' . $summary . '</info>');

        $output->writeln('<info>=== 迁移完成 ===</info>');
    }
}
