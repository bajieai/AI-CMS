<?php
declare(strict_types=1);

namespace app\common\service\template;

/**
 * 模板代码规范检测器 — V2.9.29 T-5 / V2.9.30 Q-6增强
 * 检测规则：8条（原3条+新增5条）
 */
class TemplateCodeValidator
{
    /**
     * 验证模板代码规范
     * @param string $content 模板HTML内容
     * @return array {valid: bool, issues: array, score: int(0-100)}
     */
    public function validate(string $content): array
    {
        $issues = [];
        $totalRules = 8;
        $passedRules = 0;

        // 规则1: HTML标签闭合检查
        if (preg_match_all('/<(\w+)[^>]*>/', $content, $matches)) {
            $unclosed = false;
            foreach ($matches[1] as $tag) {
                if (in_array($tag, ['br', 'hr', 'img', 'input', 'meta', 'link'])) continue;
                if (strpos($content, "</{$tag}>") === false) {
                    $unclosed = true;
                    break;
                }
            }
            if ($unclosed) {
                $issues[] = ['type' => 'html', 'rule' => 'tag_closure', 'message' => '存在未闭合的HTML标签'];
            } else {
                $passedRules++;
            }
        } else {
            $passedRules++;
        }

        // 规则2: 内联CSS样式检查
        $inlineStyleCount = preg_match_all('/style\s*=\s*"[^"]*;[^"]*"/', $content);
        if ($inlineStyleCount > 3) {
            $issues[] = ['type' => 'css', 'rule' => 'inline_css', 'message' => "检测到{$inlineStyleCount}处内联CSS样式，建议提取到外部文件"];
        } else {
            $passedRules++;
        }

        // 规则3: script标签闭合检查
        if (preg_match('/<script[^>]*>/', $content) && !preg_match('/<\/script>/', $content)) {
            $issues[] = ['type' => 'js', 'rule' => 'script_closure', 'message' => '存在未闭合的script标签'];
        } else {
            $passedRules++;
        }

        // 规则4: CSS覆盖率检查（<style>标签或外部CSS引用占比）
        $hasStyleTag = strpos($content, '<style>') !== false || strpos($content, '<link rel="stylesheet"') !== false;
        if (!$hasStyleTag && strlen($content) > 500) {
            $issues[] = ['type' => 'css', 'rule' => 'css_coverage', 'message' => '未检测到CSS样式定义（无<style>标签或外部CSS引用），CSS覆盖率不足'];
        } else {
            $passedRules++;
        }

        // 规则5: 图片Alt属性检查
        $imgCount = preg_match_all('/<img\s[^>]*>/i', $content);
        $imgWithAlt = preg_match_all('/<img\s[^>]*alt\s*=\s*["\'][^"\']*["\'][^>]*>/i', $content);
        if ($imgCount > 0 && $imgWithAlt < $imgCount) {
            $issues[] = ['type' => 'html', 'rule' => 'img_alt', 'message' => "有" . ($imgCount - $imgWithAlt) . "张图片缺少alt属性，影响SEO和无障碍访问"];
        } else {
            $passedRules++;
        }

        // 规则6: 模板变量正确性检查（ThinkPHP模板变量{$var}）
        $invalidVars = [];
        if (preg_match_all('/\{(\$\w+)[^}]*\}/', $content, $varMatches)) {
            foreach ($varMatches[1] as $var) {
                if (strlen($var) < 3) {
                    $invalidVars[] = $var;
                }
            }
        }
        if (!empty($invalidVars)) {
            $issues[] = ['type' => 'template', 'rule' => 'template_var', 'message' => '检测到可疑短模板变量: ' . implode(', ', array_slice($invalidVars, 0, 3))];
        } else {
            $passedRules++;
        }

        // 规则7: HTML文档结构完整性检查
        $hasDoctype = stripos($content, '<!DOCTYPE') !== false || stripos($content, '<!doctype') !== false;
        $hasMetaViewport = stripos($content, 'viewport') !== false;
        if (!$hasDoctype && strlen($content) > 200) {
            $issues[] = ['type' => 'html', 'rule' => 'doctype', 'message' => '未检测到DOCTYPE声明，可能影响浏览器渲染模式'];
        } else {
            $passedRules++;
        }

        // 规则8: 编码声明检查
        $hasCharset = stripos($content, 'charset=utf-8') !== false || stripos($content, 'charset="utf-8"') !== false;
        if (!$hasCharset && strlen($content) > 200) {
            $issues[] = ['type' => 'html', 'rule' => 'charset', 'message' => '未检测到UTF-8编码声明，可能导致中文乱码'];
        } else {
            $passedRules++;
        }

        $score = (int)round($passedRules / $totalRules * 100);

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'passed_rules' => $passedRules,
            'total_rules' => $totalRules,
            'score' => $score,
        ];
    }
}
