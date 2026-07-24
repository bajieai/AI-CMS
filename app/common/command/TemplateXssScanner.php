<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\command;

/**
 * 模板XSS安全扫描器 - V3.0 Phase 2
 * 扫描模板文件中的跨站脚本(XSS)风险
 */
class TemplateXssScanner
{
    /**
     * XSS风险模式列表
     * 仅标记真正的XSS风险，忽略常规的<script src="">标签
     */
    protected array $xssPatterns = [
        'event_handler' => '/\bon\w+\s*=\s*["\']?[^"\'>\s]+/i',
        'javascript_uri' => '/href\s*=\s*["\']?\s*javascript\s*:/i',
        'inline_code' => '/<script\b[^>]*>(?!\s*<\/script>)([\s\S]*?(document\.cookie|eval\(|alert\(|prompt\(|confirm\(|new\s+Function|setTimeout\s*\())[\s\S]*?<\/script>/i',
        'dangerous_tags' => '/<\/?(iframe|embed|object|applet)\b[^>]*>/i',
    ];

    /**
     * 扫描整个主题目录
     * @param string $themePath 主题目录路径
     * @return array
     */
    public function scan(string $themePath): array
    {
        $results = [];

        if (!is_dir($themePath)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['html', 'js'], true)) {
                continue;
            }
            $fileResults = $this->scanFile($file->getPathname());
            $results = array_merge($results, $fileResults);
        }

        return $results;
    }

    /**
     * 扫描单个文件
     * @param string $filePath 文件路径
     * @return array
     */
    public function scanFile(string $filePath): array
    {
        $results = [];

        if (!is_file($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $relPath = str_replace(root_path(), '', $filePath);

        foreach ($this->xssPatterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $level = ($type === 'inline_code' || $type === 'javascript_uri') ? 'high' : 'medium';
                    $results[] = [
                        'level'   => $level,
                        'file'    => $relPath,
                        'type'    => $type,
                        'match'   => mb_substr($match, 0, 100),
                        'message' => "发现{$type}风险",
                    ];
                }
            }
        }

        return $results;
    }
}
