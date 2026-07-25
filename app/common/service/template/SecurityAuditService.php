<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint SEC: 安全审计服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\template;

use think\facade\Db;

/**
 * 安全审计服务 - V2.9.31 SEC-1
 * 提供模板文件安全扫描、敏感信息检测
 */
class SecurityAuditService
{
    /**
     * 危险函数列表（PHP）
     */
    private const DANGEROUS_FUNCTIONS = [
        'eval', 'exec', 'system', 'shell_exec', 'passthru',
        'popen', 'proc_open', 'pcntl_exec', 'assert',
        'create_function', 'preg_replace', 'include', 'require',
        'file_get_contents', 'file_put_contents', 'unlink', 'rmdir',
    ];

    /**
     * 敏感信息正则模式
     */
    private const SENSITIVE_PATTERNS = [
        'api_key' => '/api[_-]?key\s*[:=]\s*[\'"][a-zA-Z0-9_-]{16,}/i',
        'password' => '/password\s*[:=]\s*[\'"][^\'"]{4,}/i',
        'secret' => '/secret\s*[:=]\s*[\'"][a-zA-Z0-9_-]{8,}/i',
        'token' => '/token\s*[:=]\s*[\'"][a-zA-Z0-9_-]{8,}/i',
        'db_password' => '/db[_-]?pass(word)?\s*[:=]\s*[\'"][^\'"]+/i',
        'private_key' => '/-----BEGIN (RSA |DSA |EC |OPENSSH )?PRIVATE KEY-----/',
    ];

    /**
     * 扫描模板文件安全
     */
    public function scanTemplate(string $themeSlug): array
    {
        $themePath = root_path() . 'template/themes/' . $themeSlug . '/';
        if (!is_dir($themePath)) {
            return ['success' => false, 'message' => '模板目录不存在'];
        }

        $issues = [];
        $files = $this->scanFiles($themePath);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace(root_path(), '', $file);

            // 1. 检测危险函数
            foreach (self::DANGEROUS_FUNCTIONS as $func) {
                if (preg_match('/\b' . $func . '\s*\(/i', $content)) {
                    $issues[] = [
                        'file' => $relativePath,
                        'type' => 'dangerous_function',
                        'severity' => 'high',
                        'message' => "发现危险函数: {$func}",
                        'line' => $this->findLine($content, $func),
                    ];
                }
            }

            // 2. 检测敏感信息泄露
            foreach (self::SENSITIVE_PATTERNS as $type => $pattern) {
                if (preg_match($pattern, $content)) {
                    $issues[] = [
                        'file' => $relativePath,
                        'type' => 'sensitive_info',
                        'severity' => 'high',
                        'message' => "发现可能的敏感信息泄露: {$type}",
                        'line' => 0,
                    ];
                }
            }

            // 3. 检测远程文件包含
            if (preg_match('/(include|require)\s*\(?\s*[\'"]https?:/i', $content)) {
                $issues[] = [
                    'file' => $relativePath,
                    'type' => 'remote_include',
                    'severity' => 'critical',
                    'message' => '发现远程文件包含风险',
                    'line' => 0,
                ];
            }

            // 4. 检测SQL注入风险（简单模式）
            if (preg_match('/\$_(GET|POST|REQUEST)\s*\[.*?\]\s*\.\s*[\'"].*?(SELECT|INSERT|UPDATE|DELETE)/i', $content)) {
                $issues[] = [
                    'file' => $relativePath,
                    'type' => 'sql_injection',
                    'severity' => 'critical',
                    'message' => '发现可能的SQL注入风险',
                    'line' => 0,
                ];
            }
        }

        return [
            'success' => true,
            'theme' => $themeSlug,
            'file_count' => count($files),
            'issue_count' => count($issues),
            'issues' => $issues,
            'risk_level' => $this->calculateRiskLevel($issues),
        ];
    }

    /**
     * 批量扫描所有模板
     */
    public function scanAllTemplates(): array
    {
        $themesDir = root_path() . 'template/themes/';
        $themes = [];
        if (is_dir($themesDir)) {
            $dirs = glob($themesDir . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $themes[] = basename($dir);
            }
        }

        $results = [];
        foreach ($themes as $theme) {
            $results[$theme] = $this->scanTemplate($theme);
        }
        return $results;
    }

    /**
     * 扫描文件列表
     */
    private function scanFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'html', 'js', 'css'])) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    /**
     * 查找行号
     */
    private function findLine(string $content, string $keyword): int
    {
        $lines = explode("\n", $content);
        foreach ($lines as $index => $line) {
            if (stripos($line, $keyword) !== false) {
                return $index + 1;
            }
        }
        return 0;
    }

    /**
     * 计算风险等级
     */
    private function calculateRiskLevel(array $issues): string
    {
        $critical = count(array_filter($issues, fn($i) => $i['severity'] === 'critical'));
        $high = count(array_filter($issues, fn($i) => $i['severity'] === 'high'));

        if ($critical > 0) return 'critical';
        if ($high > 2) return 'high';
        if ($high > 0) return 'medium';
        return 'low';
    }
}
