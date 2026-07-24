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

namespace app\common\service\ai;

use think\facade\Log;

/**
 * 自定义Prompt节点处理器 — V2.9.39 AI-DEEP-3
 *
 * 允许用户在工作流中定义任意自定义Prompt模板
 * 支持变量替换：{input}, {title}, {content} 等上下文变量
 */
class CustomPromptNodeHandler
{
    /**
     * 执行自定义Prompt节点
     * @param array $config 节点配置
     * @param array $targetIds 目标内容ID列表
     * @param array $context 上游节点输出上下文
     * @return array ['output' => [], 'ai_calls' => int, 'ai_cost' => float]
     */
    public function execute(array $config, array $targetIds, array $context = []): array
    {
        $promptTemplate = $config['prompt'] ?? '';
        $systemPrompt = $config['system_prompt'] ?? '';
        $variables = $config['variables'] ?? [];

        if (empty($promptTemplate)) {
            throw new \RuntimeException('CustomPrompt节点缺少prompt配置');
        }

        // 变量替换
        $prompt = $this->replaceVariables($promptTemplate, $context, $variables);

        $options = [
            'temperature' => $config['temperature'] ?? 0.7,
            'max_tokens'  => $config['max_tokens'] ?? 2000,
        ];
        if (!empty($systemPrompt)) {
            $options['system_prompt'] = $systemPrompt;
        }

        try {
            $provider = AiProviderFactory::getDefault();
            $result = $provider->write($prompt, $options);

            return [
                'output' => [
                    'result'  => $result,
                    'prompt'  => mb_substr($prompt, 0, 500),
                ],
                'ai_calls' => 1,
                'ai_cost'  => 0.01,
            ];
        } catch (\Throwable $e) {
            Log::error("CustomPromptNodeHandler failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 变量替换
     * @param string $template 模板字符串
     * @param array $context 上下文数据
     * @param array $extraVars 额外变量
     * @return string 替换后的字符串
     */
    private function replaceVariables(string $template, array $context, array $extraVars = []): string
    {
        $result = $template;

        // 替换上下文变量 {node_id.field}
        foreach ($context as $nodeId => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $result = str_replace('{' . $nodeId . '.' . $key . '}', (string) $value, $result);
                    }
                }
            } elseif (is_scalar($data)) {
                $result = str_replace('{' . $nodeId . '}', (string) $data, $result);
            }
        }

        // 替换内置变量
        $builtinVars = [
            '{input}'   => $context['input'] ?? '',
            '{title}'   => $context['title']['title'] ?? '',
            '{content}' => $context['content']['content'] ?? '',
        ];
        $result = strtr($result, $builtinVars);

        // 替换额外变量
        foreach ($extraVars as $key => $value) {
            if (is_scalar($value)) {
                $result = str_replace('{' . $key . '}', (string) $value, $result);
            }
        }

        return $result;
    }

    /**
     * 获取节点配置schema（供前端编辑器使用）
     * @return array
     */
    public static function getConfigSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'prompt',
                    'label' => 'Prompt模板',
                    'type' => 'textarea',
                    'required' => true,
                    'description' => '支持变量替换：{input}, {title}, {content}, {node_id.field}',
                ],
                [
                    'name' => 'system_prompt',
                    'label' => '系统提示词',
                    'type' => 'textarea',
                    'required' => false,
                ],
                [
                    'name' => 'temperature',
                    'label' => '温度',
                    'type' => 'number',
                    'default' => 0.7,
                    'min' => 0,
                    'max' => 2,
                ],
                [
                    'name' => 'max_tokens',
                    'label' => '最大Token',
                    'type' => 'number',
                    'default' => 2000,
                ],
            ],
        ];
    }
}
