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

namespace app\common\command;

/**
 * 模板语法校验器 - V3.0 Phase 2
 * 校验ThinkPHP模板语法的标签配对、格式规范
 */
class TemplateValidator
{
    /**
     * 校验整个主题目录
     * @param string $themePath 主题目录路径
     * @return array
     */
    public function validate(string $themePath): array
    {
        $results = [];

        if (!is_dir($themePath)) {
            return [['level' => 'error', 'message' => '目录不存在: ' . $themePath]];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['html', 'css', 'js'], true)) {
                continue;
            }
            $fileResults = $this->validateFile($file->getPathname());
            $results = array_merge($results, $fileResults);
        }

        return $results;
    }

    /**
     * 校验单个文件
     * @param string $filePath 文件路径
     * @return array
     */
    public function validateFile(string $filePath): array
    {
        $results = [];

        if (!is_file($filePath)) {
            return [['level' => 'error', 'message' => '文件不存在: ' . $filePath]];
        }

        $content = file_get_contents($filePath);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($ext === 'html') {
            // === V2.9.7 AI主题生成常见错误模式检测 ===

            // 检测1: {__CONTENT__}（与{extend}不兼容）
            if (str_contains($content, '{__CONTENT__}')) {
                $results[] = [
                    'level'   => 'error',
                    'rule_id' => 'TPL-AI-001',
                    'message' => '使用了{__CONTENT__}，应改为{block name="content"}{/block}',
                ];
            }

            // 检测2: {/elseif ...}（elseif无闭合标签）
            if (preg_match('/\{\/elseif\s/i', $content)) {
                $results[] = [
                    'level'   => 'error',
                    'rule_id' => 'TPL-AI-002',
                    'message' => '使用了{/elseif}，elseif无闭合标签，正确写法{elseif condition="..."}',
                ];
            }

            // 检测3: |date='Y-m-d',###}（无效语法）
            if (preg_match('/,\s*###\s*\}/', $content)) {
                $results[] = [
                    'level'   => 'error',
                    'rule_id' => 'TPL-AI-003',
                    'message' => '日期过滤器使用了,###}，应去掉,###改为|date="Y-m-d"}',
                ];
            }

            // 检测4: /assets/ 硬编码路径
            if (preg_match('#[\'"]\/assets\/(css|js|images|placeholder)\/#i', $content)) {
                $results[] = [
                    'level'   => 'error',
                    'rule_id' => 'TPL-AI-004',
                    'message' => '使用了/assets/硬编码路径，应改用{$skin}css/或{$skin}js/等',
                ];
            }

            // 检测5: {include file="pc/xxx"}（路径重复pc段）
            if (preg_match('/\{include\s+file\s*=\s*["\']pc\//i', $content)) {
                $results[] = [
                    'level'   => 'warning',
                    'rule_id' => 'TPL-AI-005',
                    'message' => '{include file="pc/xxx"}路径重复pc段，应改为{include file="xxx"}',
                ];
            }

            // 检测6: {extend name="pc/layout"}（路径重复pc段）
            if (preg_match('/\{extend\s+name\s*=\s*["\']pc\//i', $content)) {
                $results[] = [
                    'level'   => 'error',
                    'rule_id' => 'TPL-AI-006',
                    'message' => '{extend name="pc/layout"}路径重复pc段，应改为{extend name="layout"}',
                ];
            }

            // === 原有标签配对检查 ===

            // 检查{volist}/{/volist}配对
            $openCount = preg_match_all('/\{volist\s+[^}]*\}/i', $content);
            $closeCount = preg_match_all('/\{\/volist\}/i', $content);
            if ($openCount !== $closeCount) {
                $results[] = [
                    'level'   => 'error',
                    'message' => "{volist}标签不配对: 开始{$openCount}个, 结束{$closeCount}个",
                ];
            }

            // 检查{if}/{/if}配对
            $ifOpenCount = preg_match_all('/\{if\s+[^}]*\}/i', $content);
            $ifCloseCount = preg_match_all('/\{\/if\}/i', $content);
            if ($ifOpenCount !== $ifCloseCount) {
                $results[] = [
                    'level'   => 'warning',
                    'message' => "{if}标签不配对: 开始{$ifOpenCount}个, 结束{$ifCloseCount}个",
                ];
            }

            // 检查{extend}存在时是否有对应的{block}
            if (preg_match('/\{extend\s+name\s*=\s*["\'][^"\']+["\']\s*\/?\s*\}/i', $content)) {
                if (!preg_match('/\{block\s+name\s*=\s*["\']content["\']/i', $content)) {
                    $results[] = [
                        'level'   => 'warning',
                        'rule_id' => 'TPL-AI-007',
                        'message' => '使用{extend}但缺少{block name="content"}，子模板应覆盖内容区块',
                    ];
                }
            }
        }

        return $results;
    }
}
