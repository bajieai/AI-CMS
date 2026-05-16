<?php
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
    ],
];