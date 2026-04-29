<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\SeoService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SitemapGenerate extends Command
{
    protected function configure()
    {
        $this->setName('sitemap:generate')
            ->setDescription('生成Sitemap XML文件');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new SeoService;
        $success = $service->saveSitemap();
        $output->writeln($success ? 'Sitemap生成成功' : 'Sitemap生成失败');
        return 0;
    }
}