<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;

/**
 * API文档生成器 - V2.9.1 M10
 * 基于PHP Reflection扫描Controller+DocBlock解析
 */
class ApiDocGenerator
{
    protected array $docs = [];
    protected array $routes = [];

    /**
     * 扫描并生成API文档
     */
    public function scan(): array
    {
        $this->docs = [];
        $this->loadRoutes();

        $apiPath = app_path('api/controller');
        $this->scanDirectory($apiPath, 'app\api\controller');

        // 扫描v1子目录
        $v1Path = $apiPath . '/v1';
        if (is_dir($v1Path)) {
            $this->scanDirectory($v1Path, 'app\api\controller\v1');
        }

        return $this->docs;
    }

    /**
     * 加载路由定义（匹配真实URL）
     */
    protected function loadRoutes(): void
    {
        $routeFile = app_path('api/route/app.php');
        if (!file_exists($routeFile)) {
            return;
        }

        // 解析路由文件中的注册信息
        $content = file_get_contents($routeFile);
        preg_match_all('/Route::(get|post|put|delete)\([\'"]([^\'"]+)[\'"]\s*,\s*[\'"]?([^\'",\)]+)/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = strtoupper($match[1]);
            $path = $match[2];
            $handler = trim($match[3]);
            $this->routes[$handler] = ['method' => $method, 'path' => $path];
        }

        // 解析group路由
        preg_match_all('/Route::group\([\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\)\s*\{([^}]+)\}/s', $content, $groupMatches, PREG_SET_ORDER);
        foreach ($groupMatches as $g) {
            $prefix = trim($g[1], '/');
            $body = $g[2];
            preg_match_all('/Route::(get|post|put|delete)\([\'"]([^\'"]+)[\'"]\s*,\s*[\'"]?([^\'",\)]+)/', $body, $subMatches, PREG_SET_ORDER);
            foreach ($subMatches as $sm) {
                $method = strtoupper($sm[1]);
                $path = '/' . $prefix . '/' . ltrim($sm[2], '/');
                $handler = trim($sm[3]);
                $this->routes[$handler] = ['method' => $method, 'path' => $path];
            }
        }
    }

    /**
     * 扫描目录中的Controller
     */
    protected function scanDirectory(string $path, string $namespace): void
    {
        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $className = $namespace . '\\' . basename($file, '.php');
            if (!class_exists($className)) {
                continue;
            }
            $this->parseController($className);
        }
    }

    /**
     * 解析单个Controller
     */
    protected function parseController(string $className): void
    {
        try {
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            return;
        }

        $classDoc = $this->parseDocComment($reflection->getDocComment() ?: '');
        $groupName = $classDoc['api_group'] ?? $this->getShortClassName($className);
        $groupDesc = $classDoc['api_desc'] ?? '';

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->class !== $className || $method->isConstructor()) {
                continue;
            }

            $methodDoc = $this->parseDocComment($method->getDocComment() ?: '');
            if (empty($methodDoc['api'])) {
                continue; // 没有@api注解的跳过
            }

            $handler = str_replace('\\', '\\\\', $className) . '@' . $method->getName();
            $routeInfo = $this->routes[$handler] ?? ['method' => 'GET', 'path' => '/api/unknown'];

            $this->docs[] = [
                'group'        => $groupName,
                'group_desc'   => $groupDesc,
                'title'        => $methodDoc['api'] ?? $method->getName(),
                'desc'         => $methodDoc['api_desc'] ?? '',
                'method'       => $routeInfo['method'],
                'path'         => $routeInfo['path'],
                'handler'      => $className . '::' . $method->getName(),
                'params'       => $methodDoc['param'] ?? [],
                'return'       => $methodDoc['return'] ?? [],
                'auth'         => $methodDoc['api_auth'] ?? false,
                'version'      => $methodDoc['api_version'] ?? 'v1',
            ];
        }
    }

    /**
     * 解析DocBlock
     */
    protected function parseDocComment(string $doc): array
    {
        $result = [];
        if (empty($doc)) {
            return $result;
        }

        // @api 标题
        if (preg_match('/@api\s+(.+)/', $doc, $m)) {
            $result['api'] = trim($m[1]);
        }

        // @api_group 分组
        if (preg_match('/@api_group\s+(.+)/', $doc, $m)) {
            $result['api_group'] = trim($m[1]);
        }

        // @api_desc 描述
        if (preg_match('/@api_desc\s+(.+)/', $doc, $m)) {
            $result['api_desc'] = trim($m[1]);
        }

        // @api_auth 是否需要认证
        if (preg_match('/@api_auth\s*(\w*)/', $doc, $m)) {
            $result['api_auth'] = in_array(strtolower(trim($m[1] ?? '')), ['true', 'yes', '1']);
        }

        // @api_version
        if (preg_match('/@api_version\s+(.+)/', $doc, $m)) {
            $result['api_version'] = trim($m[1]);
        }

        // @param 参数
        $result['param'] = [];
        if (preg_match_all('/@param\s+(\w+)\s+\$(\w+)\s+(.+)/', $doc, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $result['param'][] = [
                    'type' => $match[1],
                    'name' => $match[2],
                    'desc' => trim($match[3]),
                ];
            }
        }

        // @return 返回
        $result['return'] = [];
        if (preg_match_all('/@return\s+(\w+)\s+(.+)/', $doc, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $result['return'][] = [
                    'type' => $match[1],
                    'desc' => trim($match[2]),
                ];
            }
        }

        return $result;
    }

    protected function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return str_replace('Controller', '', end($parts));
    }

    /**
     * 导出为Markdown
     */
    public function toMarkdown(array $docs): string
    {
        $lines = ["# API 文档\n", "生成时间: " . date('Y-m-d H:i:s') . "\n"];
        $grouped = [];
        foreach ($docs as $doc) {
            $grouped[$doc['group']][] = $doc;
        }

        foreach ($grouped as $group => $items) {
            $lines[] = "## {$group}\n";
            foreach ($items as $item) {
                $lines[] = "### {$item['title']}\n";
                $lines[] = "- **路径**: `{$item['method']} {$item['path']}`\n";
                $lines[] = "- **处理器**: `{$item['handler']}`\n";
                if ($item['desc']) $lines[] = "- **描述**: {$item['desc']}\n";
                if ($item['auth']) $lines[] = "- **认证**: 需要\n";
                if (!empty($item['params'])) {
                    $lines[] = "- **参数**:\n";
                    foreach ($item['params'] as $p) {
                        $lines[] = "  - `{$p['name']}` ({$p['type']}): {$p['desc']}\n";
                    }
                }
                $lines[] = "\n";
            }
        }

        return implode("", $lines);
    }
}
