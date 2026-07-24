<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

return [
    'listen' => [
        // V2.9.12: AI主题生成完成后自动触发质量校验
        'AiThemeGenerated' => [
            \app\common\listener\ThemeQualityCheckListener::class,
        ],
        // V2.9.18: 内容发布后自动推送到已配置通道 + 邮件通知订阅者
        'ContentPublished' => [
            \app\common\listener\PushDispatchListener::class,
            \app\common\listener\MailSendListener::class,
        ],
    ],
];
