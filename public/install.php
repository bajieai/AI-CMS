<?php
// [ACT] AI-CMS V2.9.41 安装向导入口
// 支持：1) 傻瓜式安装（完整包含vendor） 2) Git方式安装（无vendor时自动引导composer）
namespace think;

// ===== 编码根治：全局强制UTF-8 =====
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
ini_set('default_charset', 'UTF-8');
mb_regex_encoding('UTF-8');

// 如果已安装，直接跳转到后台
if (file_exists(__DIR__ . '/../install.lock')) {
    header('Location: /admin.php');
    exit;
}

// 检查vendor目录是否存在（完整安装包 vs Git源码包）
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($vendorAutoload)) {
    // vendor不存在 = 用户从Git下载的源码包，需要安装依赖
    // 显示引导页面，提供两种选择
    http_response_code(503);
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI-CMS 安装引导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans SC", sans-serif; background: #f0f2f5; color: #333; line-height: 1.6; }
        .container { max-width: 800px; margin: 50px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; text-align: center; }
        .card-header h1 { font-size: 24px; margin-bottom: 8px; }
        .card-header p { opacity: 0.9; font-size: 14px; }
        .card-body { padding: 30px; }
        .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .step-list { list-style: none; }
        .step-list li { padding: 12px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; }
        .step-list li:last-child { border-bottom: none; }
        .step-num { width: 28px; height: 28px; background: #667eea; color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 12px; flex-shrink: 0; }
        .btn { display: inline-block; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: 500; transition: all 0.2s; }
        .btn-primary { background: #667eea; color: #fff; }
        .btn-primary:hover { background: #5568d3; }
        .btn-outline { background: #fff; color: #667eea; border: 1px solid #667eea; }
        .btn-outline:hover { background: #f8f9ff; }
        .actions { text-align: center; margin-top: 24px; display: flex; gap: 16px; justify-content: center; }
        .code-block { background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 8px; font-family: "Cascadia Code", "Fira Code", Consolas, monospace; font-size: 13px; margin: 12px 0; overflow-x: auto; }
        .code-block .cmd { color: #4fc1ff; }
        .code-block .comment { color: #6a9955; }
        h2 { font-size: 18px; margin-bottom: 12px; color: #333; }
        .tip { font-size: 13px; color: #999; margin-top: 8px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">
            <h1>AI-CMS V2.9.41 安装引导</h1>
            <p>检测到依赖包未安装，请选择以下安装方式</p>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>提示：</strong>当前为源码包（不含依赖），需要安装 Composer 依赖包后才能继续安装。
            </div>

            <h2>方式一：下载完整安装包（推荐）</h2>
            <p>从 Gitee 下载完整安装包（已包含所有依赖），解压后直接访问即可安装：</p>
            <div class="actions">
                <a href="https://gitee.com/bajieai/ai-cms/releases" target="_blank" class="btn btn-primary">下载完整安装包</a>
            </div>
            <p class="tip">下载完整包后，删除当前目录所有文件，将完整包解压到网站根目录，重新访问即可。</p>

            <h2 style="margin-top: 30px;">方式二：通过 Composer 安装依赖</h2>
            <p>如果您已安装 Composer，可以在当前目录执行以下命令安装依赖：</p>
            <div class="code-block">
<span class="comment"># Windows 用户在网站根目录打开命令行执行：</span>
<span class="cmd">composer install --optimize-autoloader</span>

<span class="comment"># Linux/Mac 用户：</span>
<span class="cmd">cd /www/wwwroot/your-site</span>
<span class="cmd">composer install --optimize-autoloader</span>
            </div>
            <p>安装完成后，<a href="/install.php" style="color: #667eea;">点击这里重新进入安装向导</a></p>

            <div class="alert alert-info" style="margin-top: 24px;">
                <strong>什么是 Composer？</strong><br>
                Composer 是 PHP 的包管理工具。如果您不熟悉 Composer，建议使用<strong>方式一</strong>下载完整安装包，无需任何命令行操作。
            </div>
        </div>
    </div>
</div>
</body>
</html>
    <?php
    exit;
}

// vendor存在，正常加载ThinkPHP框架
require $vendorAutoload;

$http = (new App())->http;

// PHP 8.4 兼容：抑制 ThinkPHP 8.1 的隐式可空类型弃用警告
error_reporting(E_ALL & ~E_DEPRECATED);

$response = $http->name('install')->run();

$response->send();

$http->end($response);
