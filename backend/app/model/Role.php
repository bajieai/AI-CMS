<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 角色模型
 */
class Role extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_roles';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     */
    protected $type = [
        'status' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'i8j_aicms_user_roles', 'user_id', 'role_id');
    }

    /**
     * 权限关联
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'permission_id', 'role_id');
    }

    /**
     * 获取角色基本信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'sort' => $this->sort,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 获取角色权限ID列表
     */
    public function getPermissionIds(): array
    {
        return $this->permissions()->column('id');
    }

    /**
     * 分配权限
     */
    public function assignPermissions(array $permissionIds): bool
    {
        $this->permissions()->detach();
        $this->permissions()->saveAll($permissionIds);
        return true;
    }

    /**
     * 检查是否有指定权限
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', '=', $permissionSlug)->count() > 0;
    }

    /**
     * 根据Slug查找
     */
    public static function findBySlug(string $slug): ?Role
    {
        return self::where('slug', '=', $slug)->find();
    }

    /**
     * 获取所有启用的角色
     */
    public static function getActiveRoles(): array
    {
        return self::where('status', '=', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}
