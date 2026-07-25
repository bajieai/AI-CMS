<?php
/**
 * HelloWorld示例插件启动文件 - V2.5
 * 演示如何注册Hook和响应事件
 */

// 获取Plugin API实例（由PluginService注入）
/** @var \app\common\service\PluginService\PluginApi $api */
$api = $pluginApi ?? null;

if (!$api) {
    return;
}

// 注册Hook: 内容详情页底部
$api->register('content_after_detail', function ($content) {
    $greeting = $api->getConfig('greeting', 'Hello from HelloWorld Plugin!');
    return '<div class="alert alert-info mt-3"><i class="bi bi-info-circle"></i> ' . htmlspecialchars($greeting) . '</div>';
});

// 注册Hook: 看板组件
$api->register('dashboard_widget', function () {
    return [
        'title' => 'HelloWorld插件',
        'html'  => '<div class="card"><div class="card-body"><h5>HelloWorld 插件已激活</h5><p class="text-muted">这是一个示例插件，演示V2.5插件Hook系统。</p></div></div>',
    ];
});
