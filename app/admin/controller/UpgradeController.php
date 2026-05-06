<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Db;

class UpgradeController extends AdminBaseController
{
    public function fixMessageSystem()
    {
        if (!$this->checkLogin()) {
            return '请先登录后台';
        }

        $prefix = config('database.connections.mysql.prefix');
        $success = 0;
        $skipped = 0;
        $failed  = 0;
        $log = '';

        $sqls = $this->getSqlList($prefix);

        foreach ($sqls as $sql) {
            try {
                Db::execute($sql);
                $success++;
                $log .= "[成功] " . substr($sql, 0, 80) . "...\n";
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (stripos($msg, 'already exists') !== false
                    || stripos($msg, '1050') !== false) {
                    $skipped++;
                } else {
                    $failed++;
                    $log .= "[失败] " . $msg . "\n";
                }
            }
        }

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>升级结果</title>';
        $html .= '<link href="/assets/css/bootstrap.min.css" rel="stylesheet">';
        $html .= '<style>body{padding:40px;background:#f5f5f5;}</style></head><body>';
        $html .= '<div class="container" style="max-width:800px">';
        $html .= '<div class="card"><div class="card-body">';
        $html .= '<h4>V2.6 升级补丁执行结果</h4>';
        $html .= '<p>成功: ' . $success . ' | 跳过: ' . $skipped . ' | 失败: ' . $failed . '</p>';
        $html .= '<pre style="background:#f8f9fa;padding:15px;border-radius:6px;font-size:13px;">' . htmlspecialchars($log) . '</pre>';
        $html .= '<a href="/admin/index/index" class="btn btn-primary">返回后台</a>';
        $html .= '</div></div></div></body></html>';
        return response($html);
    }

    private function getSqlList(string $prefix): array
    {
        return [
            "CREATE TABLE IF NOT EXISTS `{$prefix}message_conversation` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id_1` int UNSIGNED NOT NULL,
                `user_id_2` int UNSIGNED NOT NULL,
                `last_message_id` int UNSIGNED NOT NULL DEFAULT 0,
                `last_message_time` int UNSIGNED NOT NULL DEFAULT 0,
                `unread_count_1` int UNSIGNED NOT NULL DEFAULT 0,
                `unread_count_2` int UNSIGNED NOT NULL DEFAULT 0,
                `create_time` int UNSIGNED NOT NULL DEFAULT 0,
                `update_time` int UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_users` (`user_id_1`, `user_id_2`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信会话表'",

            "CREATE TABLE IF NOT EXISTS `{$prefix}message` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `conversation_id` int UNSIGNED NOT NULL,
                `from_user_id` int UNSIGNED NOT NULL,
                `to_user_id` int UNSIGNED NOT NULL,
                `content` text NOT NULL,
                `is_read` tinyint NOT NULL DEFAULT 0,
                `create_time` int UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_conversation` (`conversation_id`, `create_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='私信消息表'",

            "CREATE TABLE IF NOT EXISTS `{$prefix}message_system` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL DEFAULT '',
                `content` text,
                `type` varchar(50) DEFAULT 'system',
                `target_url` varchar(500) DEFAULT '',
                `send_time` int UNSIGNED NOT NULL DEFAULT 0,
                `expire_time` int UNSIGNED NOT NULL DEFAULT 0,
                `create_time` int UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_type_time` (`type`, `send_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知表'",

            "CREATE TABLE IF NOT EXISTS `{$prefix}message_system_read` (
                `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
                `message_id` int UNSIGNED NOT NULL,
                `user_id` int UNSIGNED NOT NULL,
                `read_time` int UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_message_user` (`message_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统通知已读记录表'",
        ];
    }

}
