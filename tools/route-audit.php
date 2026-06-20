<?php
/**
 * AI-CMS 路由审计脚本 — V2.9.25 K-1
 *
 * 用途：自动统计路由文件中的有效路由数，按模块分组，生成验证清单。
 *
 * 用法：
 *   php tools/route-audit.php              # 统计所有路由
 *   php tools/route-audit.php --module=content  # 只统计 content 模块
 *   php tools/route-audit.php --export=md  # 导出为 Markdown 清单
 *
 * 运行环境：PHP 8.0+
 */

declare(strict_types=1);

// ── 配置 ──────────────────────────────────────────────
$routeFiles = [
    'admin' => __DIR__ . '/../app/admin/route/app.php',
    'api'   => __DIR__ . '/../app/api/route/app.php',
    'home'  => __DIR__ . '/../app/home/route/app.php',
];

$moduleFilter = '';
$exportFormat = '';

// 解析命令行参数
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--module=')) {
        $moduleFilter = substr($arg, 9);
    } elseif (str_starts_with($arg, '--export=')) {
        $exportFormat = substr($arg, 9);
    }
}

/**
 * 解析路由文件，提取有效路由
 */
function parseRoutes(string $filePath): array
{
    if (!file_exists($filePath)) {
        return [];
    }

    $lines    = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $routes   = [];
    $lineNum  = 0;

    foreach ($lines as $line) {
        $lineNum++;
        $trimmed = trim($line);

        // 跳过空行和注释
        if ($trimmed === '' || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
            continue;
        }

        // 匹配 Route::get/post/rule/put/delete/patch
        if (preg_match('/Route::(get|post|rule|put|delete|patch)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $m)) {
            $method = strtoupper($m[1]);
            if ($method === 'RULE') {
                $method = 'ANY';
            }

            $uri       = $m[2];
            $handler   = $m[3];
            $module    = extractModule($uri);

            // 提取版本注释（上一行）
            $version = '';
            if ($lineNum >= 2 && preg_match('/V(\d+\.\d+(?:\.\d+)?)/', $lines[$lineNum - 2] ?? '', $vm)) {
                $version = $vm[1];
            }

            $routes[] = [
                'line'     => $lineNum,
                'method'   => $method,
                'uri'      => $uri,
                'handler'  => $handler,
                'module'   => $module,
                'version'  => $version,
            ];
        }
    }

    return $routes;
}

/**
 * 从 URI 提取模块名
 */
function extractModule(string $uri): string
{
    $uri = ltrim($uri, '/');
    $parts = explode('/', $uri);
    return $parts[0] ?? 'unknown';
}

// ── 主逻辑 ────────────────────────────────────────────
$allStats = [];

foreach ($routeFiles as $app => $file) {
    $routes = parseRoutes($file);

    // 按模块分组
    $byModule = [];
    foreach ($routes as $route) {
        $mod = $route['module'];
        if (!isset($byModule[$mod])) {
            $byModule[$mod] = [];
        }
        $byModule[$mod][] = $route;
    }

    // 按版本分组
    $byVersion = [];
    foreach ($routes as $route) {
        $ver = $route['version'] ?: '未标注';
        if (!isset($byVersion[$ver])) {
            $byVersion[$ver] = 0;
        }
        $byVersion[$ver]++;
    }

    $allStats[$app] = [
        'total'     => count($routes),
        'byModule'  => $byModule,
        'byVersion' => $byVersion,
        'routes'    => $routes,
    ];
}

// ── 输出 ──────────────────────────────────────────────
if ($exportFormat === 'md') {
    // Markdown 导出
    echo "# AI-CMS 路由验证清单\n\n";
    echo "> 生成时间：" . date('Y-m-d H:i:s') . "\n\n";

    foreach ($allStats as $app => $stat) {
        echo "## {$app} 应用（共 {$stat['total']} 条路由）\n\n";
        echo "| # | 行号 | 方法 | URI | 控制器方法 | 模块 | 版本 |\n";
        echo "|---|------|------|-----|-----------|------|------|\n";

        $i = 1;
        foreach ($stat['routes'] as $route) {
            if ($moduleFilter && $route['module'] !== $moduleFilter) {
                continue;
            }
            echo "| {$i} | {$route['line']} | {$route['method']} | `{$route['uri']}` | `{$route['handler']}` | {$route['module']} | V{$route['version']} |\n";
            $i++;
        }
        echo "\n";

        // 模块统计
        echo "### 模块统计\n\n";
        echo "| 模块 | 路由数 |\n|------|--------|\n";
        foreach ($stat['byModule'] as $mod => $count) {
            echo "| {$mod} | {$count} |\n";
        }
        echo "\n";
    }
} else {
    // 控制台输出
    foreach ($allStats as $app => $stat) {
        echo "\n═══════════════════════════════════════════\n";
        echo "  {$app} 应用路由统计\n";
        echo "═══════════════════════════════════════════\n";
        echo "  总路由数：{$stat['total']}\n\n";

        echo "  按模块分组：\n";
        arsort($stat['byModule']);
        foreach ($stat['byModule'] as $mod => $count) {
            $bar = str_repeat('█', min($count, 30));
            echo "    " . str_pad($mod, 20) . " {$count}  {$bar}\n";
        }

        echo "\n  按版本分组：\n";
        ksort($stat['byVersion']);
        foreach ($stat['byVersion'] as $ver => $count) {
            echo "    V{$ver}: {$count} 条\n";
        }
        echo "\n";
    }

    echo "═══════════════════════════════════════════\n";
    $grandTotal = array_sum(array_map(fn($s) => $s['total'], $allStats));
    echo "  全部应用路由总计：{$grandTotal}\n";
    echo "═══════════════════════════════════════════\n\n";
}
