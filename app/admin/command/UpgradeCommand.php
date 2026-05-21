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

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\facade\Db;

/**
 * CLI 升级命令：创建 V2.6 缺失表
 * 用法：php think upgrade:fixMessageSystem
 */
class UpgradeCommand extends Command
{
    protected function configure()
    {
        $this->setName('upgrade:fixMessageSystem')
            ->setDescription('创建 V2.6 缺失表（message_system 等）');
    }

    protected function execute(Input $input, Output $output)
    {
        $prefix = config('database.connections.mysql.prefix');
        $output->writeln('<info>表前缀: ' . $prefix . '</info>');
        $output->writeln('');

        $sqls = $this->getPatchSqls($prefix);
        $success = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($sqls as $sql) {
            $desc = $this->getSqlDesc($sql);
            try {
                Db::execute($sql);
                $output->writeln('<info>✔ ' . $desc . '</info>');
                $success++;
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'already exists') !== false
                    || stripos($msg, '1050') !== false) {
                    $output->writeln('<comment>⊘ ' . $desc . ' (已存在，跳过)</comment>');
                    $skipped++;
                } else {
                    $output->writeln('<error>✘ ' . $desc . ': ' . $msg . '</error>');
                    $failed++;
                }
            }
        }

        $output->writeln('');
        $output->writeln('结果：成功 ' . $success . '，跳过 ' . $skipped . '，失败 ' . $failed);
    }

    private function getPatchSqls(string $prefix): array
    {
        $raw = <<<SQL
CREATE TABLE IF NOT EXISTS `{$prefix}message_conversation` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id_1` int UNSIGNED NOT NULL COMMENT '用户1ID(较小值)',
    `user_id_2` int UNSIGNED NOT NULL COMMENT '用户2ID(较大值)',
    `last_message_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后一条消息ID',
    `last_message_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后消息时间',
    `unread_count_1` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户1的未读数',
    `unread_count_2` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户2的未读数',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    `update_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users` (`user_id_1`, `user_id_2`),
    KEY `idx_user1_time` (`user_id_1`, `last_message_time`),
    KEY `idx_user2_time` (`user_id_2`, `last_message_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信会话表';

CREATE TABLE IF NOT EXISTS `{$prefix}message` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` int UNSIGNED NOT NULL COMMENT '会话ID',
    `from_user_id` int UNSIGNED NOT NULL COMMENT '发送者ID',
    `to_user_id` int UNSIGNED NOT NULL COMMENT '接收者ID',
    `content` text NOT NULL COMMENT '消息内容',
    `is_read` tinyint NOT NULL DEFAULT 0 COMMENT '是否已读:0否1是',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_conversation` (`conversation_id`, `create_time`),
    KEY `idx_to_user` (`to_user_id`, `is_read`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信消息表';

CREATE TABLE IF NOT EXISTS `{$prefix}message_system` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '通知标题',
    `content` text COMMENT '通知内容',
    `type` varchar(50) DEFAULT 'system' COMMENT '类型:system/vip/ai/order',
    `target_url` varchar(500) DEFAULT '' COMMENT '跳转链接',
    `send_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
    `expire_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间(0永不过期)',
    `create_time` int UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_type_time` (`type`, `send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表';

CREATE TABLE IF NOT EXISTS `{$prefix}message_system_read` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `message_id` int UNSIGNED NOT NULL COMMENT '通知ID',
    `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
    `read_time` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '阅读时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_message_user` (`message_id`, `user_id`),
    KEY `idx_user` (`user_id`, `read_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表';
SQL;
        $parts = preg_split('/;\s*\n/', $raw);
        $sqls  = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '' && !str_starts_with($p, '--')) {
                $sqls[] = $p;
            }
        }
        return $sqls;
    }

    private function getSqlDesc(string $sql): string
    {
        if (preg_match('/COMMENT\s*=\s*\'([^\']+)\'/', $sql, $m)) {
            return $m[1];
        }
        if (preg_match('/CREATE TABLE.*?`(\w+)`/', $sql, $m)) {
            return '表 ' . $m[1];
        }
        return 'SQL执行';
    }
}
