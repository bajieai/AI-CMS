<?php
// +----------------------------------------------------------------------
// | 八界AI-CMS 模板商店配置
// +----------------------------------------------------------------------

return [
    // 退款配置
    'refund_enabled' => env('TEMPLATE_STORE_REFUND_ENABLED', true),
    'refund_days' => env('TEMPLATE_STORE_REFUND_DAYS', 7),

    // 发票配置
    'invoice_enabled' => env('TEMPLATE_STORE_INVOICE_ENABLED', true),

    // 结算配置
    'commission_rate' => env('TEMPLATE_STORE_COMMISSION_RATE', 30),  // 平台抽成比例(%)
    'min_withdraw' => env('TEMPLATE_STORE_MIN_WITHDRAW', 100),       // 最低提现金额
    'settle_cycle' => env('TEMPLATE_STORE_SETTLE_CYCLE', 1),         // 结算周期:1月2季3年

    // SEO配置
    'seo_title' => '模板商店 - 八界AI-CMS',
    'seo_description' => '专业CMS模板商店，提供海量优质网站模板',
    'seo_keywords' => 'CMS模板,网站模板,响应式模板',

    // 统计配置
    'stats_cache_ttl' => 300, // 统计缓存时间(秒)

    // 图表库
    'chart_library' => 'chartjs', // 使用项目已有的Chart.js
];
