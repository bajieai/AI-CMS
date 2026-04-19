<?php
declare(strict_types=1);

namespace app\service;

use app\model\Role;
use app\model\Permission;
use app\model\User;
use think\facade\Cache;

/**
 * RBAC权限服务
 */
class RbacService
{
    /**
     * 缓存前缀
     */
    protected string $cachePrefix = 'rbac:';

    /**
     * 缓存有效期(秒)
     */
    protected int $cacheTtl = 3600;

    /**
     * 超级管理员角色标识
     */
    protected const SUPER_ADMIN_FLAG = 'super_admin';

    /**
     * 获取用户权限列表
     */
    public function getUserPermissions(int $userId): array
    {
        $cacheKey = $this->cachePrefix . "user:permissions:{$userId}";
        
        return Cache::remember($cacheKey, function () use ($userId) {
            $permissions = [];
            
            // 获取用户角色
            $user = User::with('roles.permissions')->find($userId);
            if (!$user) {
                return $permissions;
            }
            
            // 检查是否是超级管理员
            foreach ($user->roles as $role) {
                if ($role->slug === self::SUPER_ADMIN_FLAG) {
                    // 超级管理员拥有所有权限
                    return ['*'];
                }
                
                // 收集权限
                foreach ($role->permissions as $permission) {
                    $permissions[$permission->id] = $permission->slug;
                }
            }
            
            return array_values($permissions);
        }, $this->cacheTtl);
    }

    /**
     * 检查用户是否有指定权限
     */
    public function hasPermission(int $userId, string $permission): bool
    {
        // 获取用户权限
        $permissions = $this->getUserPermissions($userId);
        
        // 超级管理员拥有所有权限
        if (in_array('*', $permissions)) {
            return true;
        }
        
        return in_array($permission, $permissions);
    }

    /**
     * 检查用户是否有指定角色
     */
    public function hasRole(int $userId, string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        $user = User::with('roles')->find($userId);
        if (!$user) {
            return false;
        }
        
        foreach ($user->roles as $role) {
            if (in_array($role->slug, $roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 检查是否是超级管理员
     */
    public function isSuperAdmin(int $userId): bool
    {
        return $this->hasRole($userId, self::SUPER_ADMIN_FLAG);
    }

    /**
     * 获取菜单树
     */
    public function getMenuTree(int $userId): array
    {
        $cacheKey = $this->cachePrefix . "user:menu:{$userId}";
        
        return Cache::remember($cacheKey, function () use ($userId) {
            $permissions = $this->getUserPermissions($userId);
            
            // 超级管理员获取所有菜单
            if (in_array('*', $permissions)) {
                $permissionList = Permission::where('type', '=', 'menu')
                    ->where('status', '=', 1)
                    ->order('sort_order', 'asc')
                    ->select()
                    ->toArray();
            } else {
                $permissionList = Permission::where('type', '=', 'menu')
                    ->where('status', '=', 1)
                    ->whereIn('slug', $permissions)
                    ->order('sort_order', 'asc')
                    ->select()
                    ->toArray();
            }
            
            return $this->buildMenuTree($permissionList);
        }, $this->cacheTtl);
    }

    /**
     * 构建菜单树
     */
    protected function buildMenuTree(array $permissions): array
    {
        $tree = [];
        $indexed = [];
        
        // 索引所有节点
        foreach ($permissions as $permission) {
            $permission['children'] = [];
            $indexed[$permission['id']] = $permission;
        }
        
        // 构建树形结构
        foreach ($indexed as $id => $item) {
            $parentId = $item['parent_id'] ?? 0;
            if ($parentId == 0 || !isset($indexed[$parentId])) {
                $tree[] = &$indexed[$id];
            } else {
                if (!isset($indexed[$parentId]['children'])) {
                    $indexed[$parentId]['children'] = [];
                }
                $indexed[$parentId]['children'][] = &$indexed[$id];
            }
        }
        
        return $tree;
    }

    /**
     * 获取所有权限树
     */
    public function getAllPermissionTree(): array
    {
        $cacheKey = $this->cachePrefix . 'all:permissions';
        
        return Cache::remember($cacheKey, function () {
            $permissions = Permission::where('status', '=', 1)
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();
            
            return $this->buildMenuTree($permissions);
        }, $this->cacheTtl);
    }

    /**
     * 清除用户权限缓存
     */
    public function clearUserCache(int $userId): void
    {
        Cache::delete($this->cachePrefix . "user:permissions:{$userId}");
        Cache::delete($this->cachePrefix . "user:menu:{$userId}");
    }

    /**
     * 清除所有权限缓存
     */
    public function clearAllCache(): void
    {
        // 注意: 实际项目中应该使用标签来批量清除缓存
        Cache::delete($this->cachePrefix . 'all:permissions');
    }

    /**
     * 分配角色给用户
     */
    public function assignRoles(int $userId, array $roleIds): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        $user->roles()->saveAll($roleIds);
        $this->clearUserCache($userId);
        
        return true;
    }

    /**
     * 获取用户角色列表
     */
    public function getUserRoles(int $userId): array
    {
        $user = User::with('roles')->find($userId);
        if (!$user) {
            return [];
        }
        
        return $user->roles->toArray();
    }
}
