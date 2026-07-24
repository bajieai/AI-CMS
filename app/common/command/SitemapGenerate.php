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