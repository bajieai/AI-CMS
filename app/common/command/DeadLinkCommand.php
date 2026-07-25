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

/**
 * V2.9.9: 死链检测CLI命令
 * 扫描内容中的外链，检测HTTP状态码，输出死链报告
 */
class DeadLinkCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('seo:deadlink')
            ->setDescription('检测内容中的死链并生成报告')
            ->addOption('notify', 'n', null, '检测完成后发送通知')
            ->addOption('fix', 'f', null, '自动将死链标记为nofollow')
            ->addOption('limit', 'l', null, '限制检测的内容数量，默认全部', 0);
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . '] 开始死链检测...');

        $seoService = new SeoService();
        $deadLinks = $seoService->checkDeadLinks();

        if (empty($deadLinks)) {
            $output->writeln('<info>恭喜！未发现死链</info>');
            return 0;
        }

        $output->writeln('<warning>发现 ' . count($deadLinks) . ' 个死链：</warning>');
        $output->writeln(str_repeat('-', 80));

        foreach ($deadLinks as $idx => $link) {
            $statusText = $link['status_code'] === 0 ? '连接失败' : 'HTTP ' . $link['status_code'];
            $output->writeln(sprintf(
                '%3d. [%s] %s' . "\n" . '     来源: %s (内容ID: %d)',
                $idx + 1,
                $statusText,
                $link['url'],
                $link['source'],
                $link['content_id']
            ));
        }

        $output->writeln(str_repeat('-', 80));
        $output->writeln('检测完成时间: ' . date('Y-m-d H:i:s'));

        return 0;
    }
}
