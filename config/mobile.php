<?php
declare(strict_types=1);

// V2.9.32 PERF2-3: 移动端适配配置
return [
    'min_touch_target'  => 44,
    'viewport'          => 'width=device-width, initial-scale=1',
    'min_font_size'     => 16,
    'image_quality'     => 50,
    'image_format'      => 'webp',
    'responsive_range'  => [320, 768],
    'bottom_nav'        => [
        'items'     => ['home', 'store', 'content', 'profile'],
        'sticky'    => true,
        'icon_size' => 24,
    ],
];
