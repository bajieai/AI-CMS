<?php
declare(strict_types=1);

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

/**
 * 插件发布命令 - V2.9.40 DEV-ECO2-1
 *
 * 将打包好的插件发布到插件市场
 * 包含：校验→上传→生成市场页面→通知审核
 */
class PluginPublishCommand extends Command
{
    protected function configure()
    {
        $this->setName('plugin:publish')
            ->setDescription('发布插件到市场')
            ->addArgument('plugin_name', '插件名称')
            ->addOption('channel', 'c', '发布渠道(stable/beta)', 'stable');
    }

    protected function execute(Input $input, Output $output)
    {
        $pluginName = $input->getArgument('plugin_name');
        $channel = $input->getOption('channel');

        if (empty($pluginName)) {
            $output->error('请指定插件名称: plugin:publish <plugin_name>');
            return 1;
        }

        $output->info('开始发布插件: ' . $pluginName . ' (渠道: ' . $channel . ')');

        // Step1: 查找ZIP包
        $pluginDir = app_path() . 'common/plugin/' . $pluginName;
        $configFile = $pluginDir . '/plugin.json';

        if (!file_exists($configFile)) {
            $output->error('缺少plugin.json配置文件');
            return 1;
        }

        $config = json_decode(file_get_contents($configFile), true);
        $version = $config['version'] ?? '1.0.0';

        $zipFile = runtime_path() . 'plugin_build/' . $pluginName . '-' . $version . '.zip';
        $sigFile = $zipFile . '.sig';

        if (!file_exists($zipFile)) {
            $output->error('ZIP包不存在，请先执行 plugin:build ' . $pluginName);
            return 1;
        }

        // Step2: 校验签名
        if (file_exists($sigFile)) {
            $sig = json_decode(file_get_contents($sigFile), true);
            $currentHash = hash_file('sha256', $zipFile);
            if ($sig['sha256'] !== $currentHash) {
                $output->error('签名校验失败，ZIP包可能被篡改');
                return 1;
            }
            $output->info('签名校验通过');
        }

        // Step3: 检查是否已在市场存在
        $existing = Db::name('plugin_market')->where('name', $pluginName)->find();
        if ($existing) {
            // 更新版本
            Db::name('plugin_market')->where('id', $existing['id'])->update([
                'version'       => $version,
                'channel'       => $channel,
                'description'   => $config['description'] ?? $existing['description'],
                'zip_path'      => $zipFile,
                'size'          => filesize($zipFile),
                'sha256'        => hash_file('sha256', $zipFile),
                'status'        => 0, // 待审核
                'updated_at'    => time(),
            ]);
            $output->info('更新已有插件版本: v' . $version);
        } else {
            // 新增插件
            Db::name('plugin_market')->insert([
                'name'          => $pluginName,
                'version'       => $version,
                'channel'       => $channel,
                'title'         => $config['title'] ?? $pluginName,
                'description'   => $config['description'] ?? '',
                'author'        => $config['author'] ?? '',
                'category'      => $config['category'] ?? 'general',
                'icon'          => $config['icon'] ?? '',
                'zip_path'      => $zipFile,
                'size'          => filesize($zipFile),
                'sha256'        => hash_file('sha256', $zipFile),
                'dependencies'  => json_encode($config['dependencies'] ?? []),
                'status'        => 0, // 待审核
                'download_count' => 0,
                'rating'        => 0,
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);
            $output->info('新增插件到市场: v' . $version);
        }

        // Step4: 触发审核通知
        try {
            $devService = new \app\common\service\plugin\PluginDevService();
            $devService->notifyReview($pluginName, $version);
            $output->info('审核通知已发送');
        } catch (\Exception $e) {
            $output->warning('审核通知发送失败: ' . $e->getMessage());
        }

        $output->info('插件发布完成! 状态: 待审核');
        Log::info('插件发布: ' . $pluginName . ' v' . $version . ' channel=' . $channel);

        return 0;
    }
}
