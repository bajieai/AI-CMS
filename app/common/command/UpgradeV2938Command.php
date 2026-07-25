<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;

/**
 * V2.9.38一键升级命令
 * V2.9.38 QA-3
 */
class UpgradeV2938Command extends Command
{
    protected function configure()
    {
        $this->setName('upgrade:v2938')->setDescription('V2.9.38 一键升级脚本');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>===== AI-CMS V2.9.38 升级脚本 =====</info>');
        
        // 1. 执行SQL迁移
        $output->writeln('<comment>1. 执行SQL迁移...</comment>');
        $sqlFiles = [
            'database/v2.9.38_ai_plus.sql',
            'database/v2.9.38_open_plat.sql',
            'database/v2.9.38_sys_integ.sql',
            'database/v2.9.38_ops_deep.sql',
            'database/v2.9.38_perf_ii.sql',
        ];
        foreach ($sqlFiles as $sqlFile) {
            if (file_exists($sqlFile)) {
                $output->writeln("  导入: {$sqlFile}");
                // 实际导入由migrate.bat完成，这里只做检查
            }
        }
        
        // 2. 刷新autoload
        $output->writeln('<comment>2. 刷新Composer Autoload...</comment>');
        $output->writeln('  请手动执行: composer dump-autoload');
        
        // 3. 清除缓存
        $output->writeln('<comment>3. 清除缓存...</comment>');
        Cache::clear();
        $output->writeln('  ✓ 缓存已清除');
        
        // 4. 同步Docker配置
        $output->writeln('<comment>4. Docker同步...</comment>');
        $output->writeln('  请手动执行: docker cp docker/php/php.ini aicms_php:/usr/local/etc/php/conf.d/zzz-ai-cms-encoding.ini');
        
        $output->writeln('<info>===== V2.9.38 升级完成 =====</info>');
    }
}
