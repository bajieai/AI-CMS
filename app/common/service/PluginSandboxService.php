<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PLUG-4: 插件沙箱服务
 * 安全检测 + 运行限制
 */
class PluginSandboxService
{
    protected array $maliciousPatterns = [
        '/\beval\s*\(/i'           => ['eval() 调用', 'danger'],
        '/\bassert\s*\(/i'         => ['assert() 调用', 'danger'],
        '/\bsystem\s*\(/i'         => ['system() 调用', 'danger'],
        '/\bexec\s*\(/i'           => ['exec() 调用', 'danger'],
        '/\bshell_exec\s*\(/i'     => ['shell_exec() 调用', 'danger'],
        '/\bpassthru\s*\(/i'       => ['passthru() 调用', 'danger'],
        '/\bproc_open\s*\(/i'      => ['proc_open() 调用', 'danger'],
        '/`.*`/s'                  => ['反引号命令执行', 'danger'],
        '/file_get_contents\s*\(\s*["\']http/i' => ['远程文件读取', 'warning'],
        '/file_put_contents\s*\(\s*["\'].*\$_(GET|POST|REQUEST)/i' => ['用户输入写入文件', 'warning'],
    ];

    public function scan(string $identifier): array
    {
        $pluginPath = root_path() . 'plugin' . DIRECTORY_SEPARATOR . $identifier . DIRECTORY_SEPARATOR;
        if (!is_dir($pluginPath)) {
            return ['status' => 'danger', 'message' => '插件目录不存在', 'danger_count' => 0, 'warning_count' => 0, 'issues' => []];
        }

        $issues = [];
        $dangerCount = 0;
        $warningCount = 0;

        $files = glob($pluginPath . '*.php') ?: [];
        $subFiles = glob($pluginPath . '**/*.php') ?: [];
        $files = array_merge($files, $subFiles);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace($pluginPath, '', $file);

            foreach ($this->maliciousPatterns as $pattern => $info) {
                if (preg_match($pattern, $content)) {
                    $issues[] = ['file' => $relativeFile, 'pattern' => $info[0], 'severity' => $info[1]];
                    if ($info[1] === 'danger') { $dangerCount++; } else { $warningCount++; }
                }
            }
        }

        $status = $dangerCount > 0 ? 'danger' : ($warningCount > 0 ? 'warning' : 'safe');

        return [
            'status'        => $status,
            'message'       => $status === 'safe' ? '安全检测通过' : ($status === 'danger' ? '发现危险代码' : '发现潜在风险'),
            'danger_count'  => $dangerCount,
            'warning_count' => $warningCount,
            'issues'        => $issues,
        ];
    }

    public function getStatus(): array
    {
        $plugins = Db::name('plugin')->where('status', '>', 0)->select()->toArray();
        $scanned = [];
        foreach ($plugins as $plugin) {
            $scanResult = $this->scan($plugin['identifier']);
            $scanned[] = [
                'identifier'  => $plugin['identifier'],
                'name'        => $plugin['name'],
                'status'      => $scanResult['status'],
                'danger'      => $scanResult['danger_count'],
                'warning'     => $scanResult['warning_count'],
            ];
        }
        return ['plugins' => $scanned];
    }
}
