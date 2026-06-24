<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板代码规范检测器 - V2.9.29 Sprint T-5
 */
class TemplateCodeValidator
{
    public function validate(string $content): array
    {
        $issues = [];
        if (preg_match('/<(\w+)[^>]*>/', $content) && !preg_match('/<\/\1>/', $content)) {
            $issues[] = ['type' => 'html', 'message' => '存在未闭合的HTML标签'];
        }
        if (preg_match('/style\s*=\s*"[^"]*;[^"]*"/', $content)) {
            $issues[] = ['type' => 'css', 'message' => '检测到内联CSS样式，建议提取到外部文件'];
        }
        if (preg_match('/<script[^>]*>/', $content) && !preg_match('/<\/script>/', $content)) {
            $issues[] = ['type' => 'js', 'message' => '存在未闭合的script标签'];
        }
        return ['valid' => empty($issues), 'issues' => $issues, 'score' => empty($issues) ? 5 : 3];
    }
}
