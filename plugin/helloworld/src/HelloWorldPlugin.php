<?php

declare(strict_types=1);

/**
 * HelloWorld插件主类
 * 演示最基础的插件钩子使用
 */
class HelloWorldPlugin
{
    /**
     * 内容保存后触发
     */
    public function onContentSave(array $data): void
    {
        // 获取插件配置
        $config = require __DIR__ . '/../config.php';
        $settings = $config['settings'] ?? [];

        if (!empty($settings['show_message'])) {
            $message = $settings['message_text'] ?? 'Hello, AI-CMS!';
            // 记录日志
            \think\facade\Log::info("[HelloWorld插件] 内容已保存: ID={$data['id']}, 消息={$message}");
        }
    }
}
