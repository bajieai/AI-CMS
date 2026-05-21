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

use app\common\service\BackupService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 自动备份命令 - V2.9.3
 * php think backup:run [--type=all|structure|data|files|full] [--gzip] [--cleanup=10]
 */
class BackupCommand extends Command
{
    protected function configure()
    {
        $this->setName('backup:run')
            ->setDescription('执行数据库/文件自动备份')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '备份类型: all|structure|data|files|full', 'all')
            ->addOption('gzip', 'z', Option::VALUE_NONE, '启用gzip压缩（仅数据库备份）')
            ->addOption('cleanup', 'c', Option::VALUE_OPTIONAL, '保留最近N个备份，超出则清理', '10')
            ->addOption('no-snapshot', null, Option::VALUE_NONE, '恢复时不创建快照（危险！）');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');
        $gzip = (bool) $input->getOption('gzip');
        $cleanup = (int) $input->getOption('cleanup');

        $allowedTypes = ['all', 'structure', 'data', 'files', 'full'];
        if (!in_array($type, $allowedTypes)) {
            $output->error("无效的备份类型: {$type}，允许的类型: " . implode(', ', $allowedTypes));
            return 1;
        }

        $service = new BackupService();
        $startTime = microtime(true);

        try {
            switch ($type) {
                case 'files':
                    $output->info('开始文件备份...');
                    $result = $service->createFileBackup();
                    $output->info("文件备份完成: {$result['filename']} ({$result['size_text']})");
                    break;

                case 'full':
                    $output->info('开始完整备份（数据库+文件）...');
                    $result = $service->createFullBackup($gzip);
                    $output->info("数据库备份: {$result['db']['filename']} ({$result['db']['size_text']})");
                    $output->info("文件备份: {$result['files']['filename']} ({$result['files']['size_text']})");
                    break;

                default:
                    $output->info("开始数据库备份 (type={$type}, gzip=" . ($gzip ? 'yes' : 'no') . ')...');
                    $result = $service->create($type, $gzip);
                    $output->info("备份完成: {$result['filename']} ({$result['size_text']})");
                    break;
            }

            // 清理旧备份
            if ($cleanup > 0) {
                $deleted = $service->cleanup($cleanup);
                if ($deleted > 0) {
                    $output->info("已清理 {$deleted} 个旧备份文件（保留最近 {$cleanup} 个）");
                }
            }

            $elapsed = round(microtime(true) - $startTime, 2);
            $output->info("备份任务完成，耗时 {$elapsed}s");
            return 0;

        } catch (\Throwable $e) {
            $output->error('备份失败: ' . $e->getMessage());
            error_log('[BACKUP_CLI] 备份失败: ' . $e->getMessage());
            return 1;
        }
    }
}
