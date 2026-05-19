<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

use app\common\service\theme\ThemeSchemaService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * V2.9.9 F-1: 模板主题 Schema 校验 CLI 命令
 *
 * 用法:
 *   php think theme:validate                    # 校验所有主题
 *   php think theme:validate corporate          # 校验指定主题
 *   php think theme:validate --path=xxx         # 指定 theme.json 路径
 *   php think theme:validate --strict           # 严格模式（warning也视为失败）
 */
class ThemeValidateCommand extends Command
{
    protected function configure()
    {
        $this->setName('theme:validate')
            ->setDescription('模板主题 theme.json Schema 校验')
            ->addArgument('theme', Argument::OPTIONAL, '主题目录名（如 corporate）')
            ->addOption('path', 'p', Option::VALUE_OPTIONAL, '指定 theme.json 路径', '')
            ->addOption('strict', 's', Option::VALUE_NONE, '严格模式（warning视为失败）')
            ->addOption('json', 'j', Option::VALUE_NONE, '输出 JSON 格式');
    }

    protected function execute(Input $input, Output $output)
    {
        $themeName = $input->getArgument('theme');
        $customPath = $input->getOption('path');
        $strict = (bool) $input->getOption('strict');
        $jsonOutput = (bool) $input->getOption('json');

        $themesDir = root_path() . 'template/themes/';

        if ($customPath) {
            $results = [$customPath => ThemeSchemaService::validate($customPath)];
        } elseif ($themeName) {
            $path = $themesDir . $themeName . '/theme.json';
            $results = [$themeName . '/theme.json' => ThemeSchemaService::validate($path)];
        } else {
            $output->writeln('<info>正在扫描模板目录: ' . $themesDir . '</info>');
            $results = ThemeSchemaService::validateAll($themesDir);
        }

        if ($jsonOutput) {
            $output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $total = count($results);
        $ok = 0;
        $warning = 0;
        $error = 0;

        foreach ($results as $path => $res) {
            $status = $res['status'];
            $msg = $res['message'];

            if ($status === 'ok') {
                $output->writeln('<info>[OK]</info> ' . $path . ' - ' . $msg);
                $ok++;
            } elseif ($status === 'warning') {
                $output->writeln('<comment>[WARN]</comment> ' . $path . ' - ' . $msg);
                $warning++;
                foreach ($res['warnings'] as $w) {
                    $output->writeln('  <comment>-> ' . $w . '</comment>');
                }
            } else {
                $output->writeln('<error>[ERR]</error> ' . $path . ' - ' . $msg);
                $error++;
                foreach ($res['errors'] as $e) {
                    $output->writeln('  <error>-> ' . $e . '</error>');
                }
                foreach ($res['warnings'] as $w) {
                    $output->writeln('  <comment>-> ' . $w . '</comment>');
                }
            }
        }

        $output->writeln('');
        $output->writeln('=== 汇总 ===');
        $output->writeln('总计: ' . $total . ' | 通过: ' . $ok . ' | 警告: ' . $warning . ' | 错误: ' . $error);

        $exitCode = 0;
        if ($error > 0) {
            $exitCode = 1;
        } elseif ($strict && $warning > 0) {
            $exitCode = 2;
        }

        if ($exitCode === 0) {
            $output->writeln('<info>✅ 全部通过</info>');
        } else {
            $output->writeln('<error>❌ 存在 ' . ($error + ($strict ? $warning : 0)) . ' 个问题</error>');
        }

        return $exitCode;
    }
}
