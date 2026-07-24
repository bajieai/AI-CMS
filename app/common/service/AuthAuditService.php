<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 SEC-5: 权限审计服务
 * 审计权限分配合理性，检测越权风险
 */
class AuthAuditService
{
    /**
     * 审计权限分配
     */
    public function auditPermissionAssignment(): array
    {
        $issues = [];

        // 1. 检查空权限角色组（有角色无权限规则）
        $emptyGroups = Db::name('auth_group')
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('rules')->whereOr('rules', '')->whereOr('rules', '0');
            })
            ->select()
            ->toArray();

        foreach ($emptyGroups as $group) {
            $issues[] = [
                'type'        => 'empty_permission',
                'severity'    => 'medium',
                'description' => "角色组「{$group['title']}」没有任何权限规则",
                'group_id'    => $group['id'],
            ];
        }

        // 2. 检查过度授权（拥有系统管理权限的非超级管理员角色）
        $systemRules = Db::name('auth_rule')
            ->where('resource_type', 'system')
            ->where('is_system', 1)
            ->column('id');

        if (!empty($systemRules)) {
            $overPrivileged = Db::name('auth_group')
                ->where('id', '<>', 1) // 排除超级管理员
                ->where('status', 1)
                ->whereRaw('FIND_IN_SET(?, REPLACE(rules, ",", ","))', [implode(',', $systemRules)])
                ->select()
                ->toArray();

            foreach ($overPrivileged as $group) {
                $issues[] = [
                    'type'        => 'over_privilege',
                    'severity'    => 'high',
                    'description' => "角色组「{$group['title']}」拥有系统管理权限",
                    'group_id'    => $group['id'],
                ];
            }
        }

        // 3. 检查未使用的权限规则
        $allRules = Db::name('auth_rule')->where('status', 1)->column('id');
        $usedRules = [];
        $groups = Db::name('auth_group')->where('status', 1)->column('rules');
        foreach ($groups as $rules) {
            if ($rules) {
                $usedRules = array_merge($usedRules, explode(',', $rules));
            }
        }
        $usedRules = array_unique(array_map('intval', $usedRules));
        $unusedRules = array_diff($allRules, $usedRules);

        foreach ($unusedRules as $ruleId) {
            $rule = Db::name('auth_rule')->where('id', $ruleId)->find();
            if ($rule && empty($rule['is_system'])) {
                $issues[] = [
                    'type'        => 'unused_rule',
                    'severity'    => 'low',
                    'description' => "权限规则「{$rule['name']}」未被任何角色组使用",
                    'rule_id'     => $ruleId,
                ];
            }
        }

        return [
            'total_issues' => count($issues),
            'issues'       => $issues,
            'audited_at'   => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取权限分配概览
     */
    public function getOverview(): array
    {
        $totalGroups = Db::name('auth_group')->count();
        $activeGroups = Db::name('auth_group')->where('status', 1)->count();
        $totalRules = Db::name('auth_rule')->count();
        $systemRules = Db::name('auth_rule')->where('is_system', 1)->count();

        // 各资源类型规则分布
        $byResourceType = Db::name('auth_rule')
            ->field('resource_type, COUNT(*) as count')
            ->group('resource_type')
            ->select()
            ->toArray();

        return [
            'total_groups'    => $totalGroups,
            'active_groups'   => $activeGroups,
            'total_rules'     => $totalRules,
            'system_rules'    => $systemRules,
            'by_resource_type' => $byResourceType,
        ];
    }
}
