<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 插件开发SDK服务 - V2.9.29 Sprint D-3
 */
class PluginDevSdkService
{
    /**
     * 获取SDK文档
     */
    public function getSdkDoc(): array
    {
        return [
            'hooks' => [
                'name' => 'Hook注册',
                'methods' => ['PluginMarketService::on()', 'PluginMarketService::fire()'],
                'desc' => '注册和触发Hook事件',
            ],
            'database' => [
                'name' => '数据库操作',
                'methods' => ['Db::table()', 'Db::name()'],
                'desc' => '使用ThinkPHP Db类操作数据库',
            ],
            'cache' => [
                'name' => '缓存操作',
                'methods' => ['Cache::set()', 'Cache::get()', 'Cache::tag()'],
                'desc' => '使用ThinkPHP Cache缓存数据',
            ],
            'config' => [
                'name' => '配置读取',
                'methods' => ['Config::get()', 'config()'],
                'desc' => '读取系统配置',
            ],
            'log' => [
                'name' => '日志记录',
                'methods' => ['Log::info()', 'Log::error()'],
                'desc' => '记录运行日志',
            ],
        ];
    }

    /**
     * 获取代码示例
     */
    public function getExamples(): array
    {
        return [
            [
                'title' => '内容增强插件',
                'desc' => '在内容发布后自动添加水印',
                'code' => '<?php
// 注册Hook
PluginMarketService::on("content.after_publish", function($ctx) {
    $contentId = $ctx["content_id"] ?? 0;
    // 处理逻辑
});',
            ],
            [
                'title' => '支付扩展插件',
                'desc' => '扩展支付渠道',
                'code' => '<?php
PluginMarketService::on("payment.register_channel", function($ctx) {
    return [
        "code" => "my_payment",
        "name" => "自定义支付",
        "handler" => MyPaymentChannel::class,
    ];
});',
            ],
            [
                'title' => 'SEO增强插件',
                'desc' => '自动生成SEO关键词',
                'code' => '<?php
PluginMarketService::on("seo.before_optimize", function($ctx) {
    $content = $ctx["content"] ?? "";
    // 自动生成关键词
    return ["keywords" => "auto,generated,keywords"];
});',
            ],
        ];
    }
}
