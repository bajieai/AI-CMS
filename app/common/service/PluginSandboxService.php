<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 插件安全沙箱服务 — V2.9.28 P-5
 *
 * Q8: 高危阻止/中危警告/低危记录
 * 使用token_get_all静态分析检测危险函数调用
 */
class PluginSandboxService
{
    // 高危函数（阻止安装）
    private array $dangerousFunctions = [
        'eval', 'exec', 'shell_exec', 'system', 'passthru',
        'proc_open', 'popen', 'pcntl_exec', 'putenv',
    ];

    // 中危函数（警告）
    private array $warningFunctions = [
        'file_get_contents', 'file_put_contents', 'fopen', 'fsockopen',
        'curl_exec', 'unlink', 'rmdir', 'mkdir', 'chmod',
        'mysql_query', 'mysqli_query',
    ];

    // 低危函数（记录）
    private array $noticeFunctions = [
        'error_reporting', 'ini_set', 'set_time_limit',
        'dl', 'stream_context_create',
    ];

    // 危险文件模式
    private array $dangerousFilePatterns = [
        '/\.php\.bak$/i', '/\.php\.old$/i',
        '/^_\//', '/^\.\.\//',
    ];

    /**
     * 扫描ZIP包
     */
    public function scanZip(string $zipFile): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            return ['safe' => false, 'message' => '无法打开ZIP文件'];
        }

        $issues = ['high' => [], 'medium' => [], 'low' => []];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // 检查路径穿越
            if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
                $issues['high'][] = "路径穿越风险: {$filename}";
                continue;
            }

            // 检查危险文件模式
            foreach ($this->dangerousFilePatterns as $pattern) {
                if (preg_match($pattern, $filename)) {
                    $issues['medium'][] = "可疑文件: {$filename}";
                }
            }

            // 分析PHP文件
            if (preg_match('/\.php$/i', $filename)) {
                $content = $zip->getFromIndex($i);
                $fileIssues = $this->analyzePhpCode($content, $filename);
                $issues['high'] = array_merge($issues['high'], $fileIssues['high']);
                $issues['medium'] = array_merge($issues['medium'], $fileIssues['medium']);
                $issues['low'] = array_merge($issues['low'], $fileIssues['low']);
            }
        }

        $zip->close();

        // 判断安全性
        $safe = empty($issues['high']);
        $message = '';
        if (!$safe) {
            $message = '发现高危风险: ' . implode('; ', array_slice($issues['high'], 0, 5));
        } elseif (!empty($issues['medium'])) {
            $message = '发现中危警告: ' . implode('; ', array_slice($issues['medium'], 0, 5));
        }

        return [
            'safe' => $safe,
            'message' => $message,
            'issues' => $issues,
            'summary' => [
                'high' => count($issues['high']),
                'medium' => count($issues['medium']),
                'low' => count($issues['low']),
            ],
        ];
    }

    /**
     * 分析PHP代码（使用token_get_all静态分析）
     */
    public function analyzePhpCode(string $code, string $filename): array
    {
        $issues = ['high' => [], 'medium' => [], 'low' => []];

        $tokens = @token_get_all($code);
        if (!$tokens) return $issues;

        $inString = false;
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                continue;
            }

            $id = $token[0];
            $text = $token[1];

            // 检测函数调用
            if ($id == T_STRING) {
                $funcName = strtolower($text);

                // 检查是否是函数调用（前面不是 -> 或 ::）
                $prev = $i > 0 ? $tokens[$i - 1] : null;
                $isMethodCall = false;
                if (is_array($prev)) {
                    if (in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION])) {
                        $isMethodCall = true;
                    }
                }

                if (!$isMethodCall) {
                    if (in_array($funcName, $this->dangerousFunctions)) {
                        $line = $token[2] ?? 0;
                        $issues['high'][] = "{$filename}:{$line} 调用高危函数 {$funcName}()";
                    } elseif (in_array($funcName, $this->warningFunctions)) {
                        $line = $token[2] ?? 0;
                        $issues['medium'][] = "{$filename}:{$line} 调用中危函数 {$funcName}()";
                    } elseif (in_array($funcName, $this->noticeFunctions)) {
                        $line = $token[2] ?? 0;
                        $issues['low'][] = "{$filename}:{$line} 调用低危函数 {$funcName}()";
                    }
                }
            }

            // 检测反引号执行
            if ($id == T_BACKTICK) {
                $line = $token[2] ?? 0;
                $issues['high'][] = "{$filename}:{$line} 使用反引号执行命令";
            }
        }

        return $issues;
    }

    /**
     * 设置文件权限（最小化权限）
     */
    public function setFilePermissions(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @chmod($item->getPathname(), 0755);
            } else {
                @chmod($item->getPathname(), 0644);
            }
        }
    }
}
