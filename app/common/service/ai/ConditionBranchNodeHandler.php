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
 * 条件分支节点处理器 — V2.9.39 AI-DEEP-3
 *
 * 根据条件表达式决定工作流走向
 * 支持：比较运算、逻辑运算、变量引用、多分支
 *
 * 分支规则：
 *   - 条件为true → 走true分支
 *   - 条件为false → 走false分支
 *   - 多条件匹配 → 返回匹配的分支名
 */
class ConditionBranchNodeHandler
{
    /** 比较运算符 */
    private const OPERATORS = ['==', '!=', '>', '<', '>=', '<=', 'contains', 'not_contains', 'starts_with', 'ends_with'];

    /**
     * 执行条件分支节点
     * @param array $config 节点配置
     * @param array $targetIds 目标内容ID列表
     * @param array $context 上游节点输出上下文
     * @return array ['output' => [], 'ai_calls' => int, 'ai_cost' => float, 'branch' => string]
     */
    public function execute(array $config, array $targetIds, array $context = []): array
    {
        $conditions = $config['conditions'] ?? [];
        $defaultBranch = $config['default_branch'] ?? 'true';

        if (empty($conditions)) {
            // 简单条件模式
            $expression = $config['expression'] ?? 'true';
            $branch = $this->evaluateExpression($expression, $context) ? 'true' : 'false';
        } else {
            // 多条件分支模式
            $branch = $defaultBranch;
            foreach ($conditions as $condition) {
                $expr = $condition['expression'] ?? '';
                $branchName = $condition['branch'] ?? '';
                if (!empty($expr) && $this->evaluateExpression($expr, $context)) {
                    $branch = $branchName;
                    break;
                }
            }
        }

        return [
            'output' => [
                'branch'     => $branch,
                'conditions' => $conditions,
            ],
            'ai_calls' => 0,
            'ai_cost'  => 0,
            'branch'   => $branch,
        ];
    }

    /**
     * 评估条件表达式
     * @param string $expression 表达式
     * @param array $context 上下文
     * @return bool
     */
    public function evaluateExpression(string $expression, array $context): bool
    {
        // 替换变量
        $expr = $this->replaceVariables($expression, $context);

        // 简单布尔
        $expr = trim(strtolower($expr));
        if ($expr === 'true' || $expr === '1') return true;
        if ($expr === 'false' || $expr === '0') return false;

        // 比较运算
        foreach (self::OPERATORS as $op) {
            if (str_contains($expr, $op)) {
                return $this->evaluateComparison($expr, $op);
            }
        }

        // 逻辑运算（AND/OR）
        if (str_contains($expr, ' AND ') || str_contains($expr, ' && ')) {
            $parts = preg_split('/\s+(?:AND|&&)\s+/i', $expr);
            foreach ($parts as $part) {
                if (!$this->evaluateExpression($part, $context)) {
                    return false;
                }
            }
            return true;
        }

        if (str_contains($expr, ' OR ') || str_contains($expr, ' || ')) {
            $parts = preg_split('/\s+(?:OR|\|\|)\s+/i', $expr);
            foreach ($parts as $part) {
                if ($this->evaluateExpression($part, $context)) {
                    return true;
                }
            }
            return false;
        }

        // 非空判断
        return !empty($expr);
    }

    /**
     * 评估比较表达式
     * @param string $expr 表达式
     * @param string $operator 运算符
     * @return bool
     */
    private function evaluateComparison(string $expr, string $operator): bool
    {
        $parts = explode($operator, $expr, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $left = trim($parts[0]);
        $right = trim($parts[1]);

        // 去除引号
        $left = trim($left, '"\'');
        $right = trim($right, '"\'');

        // 尝试数值比较
        $leftNum = is_numeric($left) ? (float) $left : null;
        $rightNum = is_numeric($right) ? (float) $right : null;

        switch ($operator) {
            case '==':
                return $leftNum !== null && $rightNum !== null
                    ? $leftNum == $rightNum
                    : $left === $right;

            case '!=':
                return $leftNum !== null && $rightNum !== null
                    ? $leftNum != $rightNum
                    : $left !== $right;

            case '>':
                return $leftNum !== null && $rightNum !== null && $leftNum > $rightNum;

            case '<':
                return $leftNum !== null && $rightNum !== null && $leftNum < $rightNum;

            case '>=':
                return $leftNum !== null && $rightNum !== null && $leftNum >= $rightNum;

            case '<=':
                return $leftNum !== null && $rightNum !== null && $leftNum <= $rightNum;

            case 'contains':
                return str_contains($left, $right);

            case 'not_contains':
                return !str_contains($left, $right);

            case 'starts_with':
                return str_starts_with($left, $right);

            case 'ends_with':
                return str_ends_with($left, $right);

            default:
                return false;
        }
    }

    /**
     * 变量替换
     * @param string $template 模板字符串
     * @param array $context 上下文数据
     * @return string
     */
    private function replaceVariables(string $template, array $context): string
    {
        $result = $template;

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

        return $result;
    }

    /**
     * 获取节点配置schema
     * @return array
     */
    public static function getConfigSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'expression',
                    'label' => '条件表达式（简单模式）',
                    'type' => 'text',
                    'required' => false,
                    'description' => '支持：==, !=, >, <, >=, <=, contains, starts_with, ends_with, AND, OR。变量格式：{node_id.field}',
                ],
                [
                    'name' => 'conditions',
                    'label' => '多条件分支（高级模式）',
                    'type' => 'dynamic_list',
                    'required' => false,
                    'description' => '每个条件包含expression和branch字段，按顺序匹配',
                    'itemFields' => [
                        ['name' => 'expression', 'label' => '条件', 'type' => 'text'],
                        ['name' => 'branch', 'label' => '分支名', 'type' => 'text'],
                    ],
                ],
                [
                    'name' => 'default_branch',
                    'label' => '默认分支',
                    'type' => 'text',
                    'default' => 'true',
                ],
            ],
        ];
    }
}
