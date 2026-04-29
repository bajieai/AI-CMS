<?php
// 控制台命令配置
return [
    'commands' => [
        'schedule:publish'    => 'app\common\command\PublishSchedule',
        'sitemap:generate'    => 'app\common\command\SitemapGenerate',
        'seo:check-deadlinks' => 'app\common\command\SeoCheckDeadlinks',
        'visit:archive'       => 'app\common\command\VisitArchive',
    ],
];