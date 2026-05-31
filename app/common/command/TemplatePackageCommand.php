<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use app\common\service\template\TemplatePackageService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * 模板打包命令行工具 - V2.9.12
 *
 * 用法：php think template:package <slug>
 *       php think template:import <zipPath>
 */
class TemplatePackageCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('template:package')
            ->addArgument('slug', Argument::REQUIRED, '模板标识(slug)')
            ->setDescription('将指定模板打包为ZIP文件');
    }

    protected function execute(Input $input, Output $output): int
    {
        $slug = $input->getArgument('slug');
        $output->info("正在打包模板: {$slug}");

        $service = new TemplatePackageService();
        $result = $service->package($slug, true);

        if ($result['success']) {
            $size = number_format($result['size'] / 1024, 2);
            $output->info("打包成功: {$result['path']}");
            $output->info("文件大小: {$size} KB");
            return 0;
        }

        $output->error("打包失败: {$result['message']}");
        return 1;
    }
}
