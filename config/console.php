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
    ],
];