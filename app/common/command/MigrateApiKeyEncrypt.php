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

use app\common\service\AiModelService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AI密钥加密迁移CLI命令 - V2.5新增
 * 用法: php think ai:migrate-encrypt
 */
class MigrateApiKeyEncrypt extends Command
{
    protected function configure()
    {
        $this->setName('ai:migrate-encrypt')
            ->setDescription('将AI模型明文API密钥加密存储');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>开始迁移AI密钥加密...</info>');

        try {
            $result = AiModelService::migrateEncryptAll();
            $output->writeln("<info>迁移完成: 加密{$result['encrypted']}个密钥，跳过{$result['skipped']}个</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>迁移失败: {$e->getMessage()}</error>");
            return 1;
        }

        return 0;
    }
}
