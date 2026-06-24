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
// 控制台命令配置
return [
    'commands' => [
        'schedule:publish'    => 'app\common\command\PublishSchedule',
        'sitemap:generate'    => 'app\common\command\SitemapGenerate',
        'seo:check-deadlinks' => 'app\common\command\SeoCheckDeadlinks',
        'visit:archive'       => 'app\common\command\VisitArchive',
        // V2.5 新增CLI命令
        'ai:batch-generate'   => 'app\common\command\AiBatchGenerate',
        'ai:migrate-encrypt'  => 'app\common\command\MigrateApiKeyEncrypt',
        'email:worker'        => 'app\common\command\EmailWorker',
        'collect:run'         => 'app\common\command\CollectRun',
        'order:close-timeout' => 'app\common\command\OrderCloseTimeout',
        // V2.7 新增CLI命令
        'vip:expire'          => 'app\common\command\VipExpireCommand',
        'points:archive'      => 'app\common\command\PointsArchiveCommand',
        'email:recover'       => 'app\common\command\EmailQueueRecoverCommand',
        // V2.9.1 M14a: 配图异步轮询命令
        'image:poll'          => 'app\common\command\ImagePollCommand',
        // V2.9.3 M26: 增强数据备份命令
        'backup:run'          => 'app\common\command\BackupCommand',
        // V2.9.3 M20: 会员自动降级命令
        'member:auto-downgrade' => 'app\common\command\AutoDowngradeCommand',
        // V2.9.6 P0: 预埋模板批量化生成
        'theme:batch'           => 'app\common\command\ThemeBatchGenerate',
        'theme:generate'        => 'app\common\command\ThemeGenerateCommand',
        // V2.9.9 F-1: 模板主题 Schema 校验
        'theme:validate'        => 'app\common\command\ThemeValidateCommand',
        // V2.9.9 J-2: 报表自动化
        'report:daily'          => 'app\common\command\ReportDailyCommand',
        'report:weekly'         => 'app\common\command\ReportWeeklyCommand',
        // V2.9.9 I-1: Schema迁移工具
        'theme:migrate'         => 'app\common\command\ThemeMigrateCommand',
        // V2.9.9 P0-4: 死链检测
        'seo:deadlink'          => 'app\common\command\DeadLinkCommand',
        // V2.9.11: 主题清理 + 骨架复制
        'theme:clean'           => 'app\common\command\ThemeCleanCommand',
        'theme:duplicate'       => 'app\common\command\ThemeDuplicateCommand',
        // V2.9.14: AI任务队列消费者（Cron模式）
        'ai-queue:consume'      => 'app\common\command\AiQueueConsume',
        // V2.9.19 R-5: 菜单同步命令
        'menu:sync'             => 'app\common\command\MenuSyncCommand',
        // V2.9.19 D-1c: 推送重试命令
        'push:retry'            => 'app\common\command\PushRetryCommand',
        // V2.9.20 C-2: 邮件失败重试命令
        'mail:retry'            => 'app\common\command\MailRetry',
        // V2.9.23 D-1: 插件管理CLI
        'plugin'                => 'app\admin\command\PluginCommand',
        // V2.9.28 M-3: 模板统计每日聚合
        'template:aggregate-stats' => 'app\common\command\TemplateStatsAggregateCommand',
        // V2.9.28 P-6: 插件更新检查
        'plugin:check-update' => 'app\common\command\PluginCheckUpdateCommand',
        // V2.9.29 C-6: 内容模型迁移
        'content_model:migrate' => 'app\admin\command\ContentModelMigrateCommand',
        // V2.9.29 D-4: Webhook失败重试
        'webhook:retry'        => 'app\admin\command\WebhookRetryCommand',
        // V2.9.29 I-3: 内容行动计划定时执行
        'content:action_plan'  => 'app\admin\command\ContentActionPlanCommand',
        // V2.9.29 I-7: 内容摘要推送
        'content:digest'       => 'app\admin\command\ContentDigestCommand',
    ],
];