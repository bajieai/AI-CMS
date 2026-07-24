<?php
declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Cache;

/**
 * 插件自动更新检查命令 — V2.9.28 P-6
 * 每日3:00检查所有已安装插件是否有更新
 *
 * 使用：php think plugin:check-update
 * Crontab: 0 3 * * * cd /var/www/html && php think plugin:check-update
 */
class PluginCheckUpdateCommand extends Command
{
    protected function configure()
    {
        $this->setName('plugin:check-update')
            ->setDescription('检查所有已安装插件的更新（V2.9.28 P-6）');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始检查插件更新...</info>');

        // 获取所有已安装插件
        $plugins = Db::name('plugin')->where('status', 'in', [0, 1])->select()->toArray();
        $marketUrl = config('plugin.market_url', 'https://market.aicms.io/api');

        $updateCount = 0;
        foreach ($plugins as $plugin) {
            try {
                $url = $marketUrl . '/check-update?name=' . urlencode($plugin['name']) . '&version=' . urlencode($plugin['version']);
                $response = @file_get_contents($url);
                if ($response === false) continue;

                $data = json_decode($response, true);
                if (!$data) continue;

                $hasUpdate = ($data['latest_version'] ?? '') !== $plugin['version'];
                $latestVersion = $data['latest_version'] ?? $plugin['version'];
                $changelog = $data['changelog'] ?? '';

                // 写入更新检查记录
                Db::name('plugin_update_check')->replace()->insert([
                    'plugin_name' => $plugin['name'],
                    'current_version' => $plugin['version'],
                    'latest_version' => $latestVersion,
                    'has_update' => $hasUpdate ? 1 : 0,
                    'check_time' => time(),
                    'changelog' => $changelog,
                ]);

                if ($hasUpdate) {
                    $updateCount++;
                    $output->writeln("  <comment>{$plugin['name']}: {$plugin['version']} → {$latestVersion} (有更新)</comment>");
                }
            } catch (\Throwable $e) {
                $output->writeln("  <error>{$plugin['name']}: 检查失败 - {$e->getMessage()}</error>");
            }
        }

        Cache::clear();
        $output->writeln("<info>检查完成: {$updateCount} 个插件有更新</info>");
    }
}
