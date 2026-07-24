<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 SEC-5: 资源级权限服务
 * 模块→控制器→方法→资源行 四级权限粒度
 * 数据范围: 全部/本部门/本人
 */
class ResourcePermissionService
{
    /**
     * 资源类型映射
     */
    protected array $resourceTypes = [
        'content'  => '内容',
        'template' => '模板',
        'member'   => '会员',
        'menu'     => '菜单',
        'system'   => '系统',
    ];

    /**
     * 检查资源级权限
     * @param int $userId 用户ID
     * @param string $resourceType 资源类型
     * @param string $action 操作类型: view/edit/delete
     * @param int|null $resourceId 资源ID
     * @return bool
     */
    public function check(int $userId, string $resourceType, string $action, ?int $resourceId = null): bool
    {
        // 超级管理员直接放行
        $roleId = (int) session('role_id');
        if ($roleId === 1) {
            return true;
        }

        // 获取用户角色组的权限规则
        $rules = $this->getUserRules($userId);
        if (empty($rules)) {
            return false;
        }

        // 遍历规则检查
        foreach ($rules as $rule) {
            if ($this->matchRule($rule, $resourceType, $action, $resourceId, $userId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取数据范围过滤条件
     * @return array [scope => all|department|self, department_id => int]
     */
    public function getDataScope(int $userId): array
    {
        $roleId = (int) session('role_id');
        if ($roleId === 1) {
            return ['scope' => 'all', 'department_id' => 0];
        }

        // 获取用户角色的数据范围配置
        $role = Db::name('auth_group')
            ->where('id', $roleId)
            ->find();

        if (!$role) {
            return ['scope' => 'self', 'department_id' => 0];
        }

        $scope = $role['data_scope'] ?? 'self';
        $departmentId = (int) ($role['department_id'] ?? 0);

        return [
            'scope'         => $scope,
            'department_id' => $departmentId,
        ];
    }

    /**
     * 根据数据范围构建查询条件
     */
    public function applyDataScope($query, int $userId, string $tableAlias = ''): void
    {
        $scope = $this->getDataScope($userId);
        $prefix = $tableAlias ? $tableAlias . '.' : '';

        switch ($scope['scope']) {
            case 'all':
                // 全部数据，不加条件
                break;
            case 'department':
                // 本部门数据
                if ($scope['department_id'] > 0) {
                    $query->where($prefix . 'department_id', $scope['department_id']);
                }
                break;
            case 'self':
            default:
                // 仅本人数据
                $query->where($prefix . 'user_id', $userId);
                break;
        }
    }

    /**
     * 获取用户权限规则
     */
    protected function getUserRules(int $userId): array
    {
        $roleId = (int) session('role_id');
        if ($roleId <= 0) {
            return [];
        }

        // 获取角色组的规则ID列表
        $group = Db::name('auth_group')->where('id', $roleId)->find();
        if (!$group || empty($group['rules'])) {
            return [];
        }

        $ruleIds = explode(',', $group['rules']);
        $ruleIds = array_map('intval', $ruleIds);
        $ruleIds = array_filter($ruleIds);

        if (empty($ruleIds)) {
            return [];
        }

        return Db::name('auth_rule')
            ->whereIn('id', $ruleIds)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 匹配权限规则
     */
    protected function matchRule(array $rule, string $resourceType, string $action, ?int $resourceId, int $userId): bool
    {
        // 检查资源类型
        $ruleResourceType = $rule['resource_type'] ?? '';
        if ($ruleResourceType && $ruleResourceType !== $resourceType) {
            return false;
        }

        // 检查操作权限（从rule的name字段解析，如 content.edit）
        $ruleName = $rule['name'] ?? '';
        if ($ruleName && !str_contains($ruleName, $action) && $ruleName !== $resourceType) {
            return false;
        }

        // 检查资源条件
        $condition = $rule['resource_condition'] ?? null;
        if ($condition && $resourceId) {
            $condition = is_string($condition) ? json_decode($condition, true) : $condition;
            if (is_array($condition)) {
                // 条件过滤（如: 只能操作特定分类的内容）
                // TODO: 根据业务场景实现条件匹配
            }
        }

        return true;
    }

    /**
     * 获取资源类型列表
     */
    public function getResourceTypes(): array
    {
        return $this->resourceTypes;
    }
}
