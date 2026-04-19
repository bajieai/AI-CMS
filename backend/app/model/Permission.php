<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 权限模型
 */
class Permission extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_permissions';

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
        'type' => 'string',
        'parent_id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 父级权限
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 子级权限
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    /**
     * 角色关联
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'role_id', 'permission_id');
    }

    /**
     * 获取菜单树
     */
    public static function getMenuTree(): array
    {
        $permissions = self::where('type', '=', 'menu')
            ->where('status', '=', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
        
        return self::buildTree($permissions);
    }

    /**
     * 构建树形结构
     */
    public static function buildTree(array $permissions, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($permissions as $permission) {
            if ($permission['parent_id'] === $parentId) {
                $children = self::buildTree($permissions, $permission['id']);
                if (!empty($children)) {
                    $permission['children'] = $children;
                }
                $tree[] = $permission;
            }
        }
        
        return $tree;
    }

    /**
     * 获取权限路径(用于面包屑)
     */
    public function getPath(): array
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }
        
        return $path;
    }

    /**
     * 获取所有后代权限ID
     */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];
        $children = $this->children;
        
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        
        return $ids;
    }

    /**
     * 检查是否有子权限
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * 根据Slug查找
     */
    public static function findBySlug(string $slug): ?Permission
    {
        return self::where('slug', '=', $slug)->find();
    }

    /**
     * 获取权限基本信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'icon' => $this->icon,
            'path' => $this->path,
            'sort' => $this->sort,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 类型常量
     */
    const TYPE_ROUTE = 'route';
    const TYPE_MENU = 'menu';
    const TYPE_BUTTON = 'button';
    const TYPE_API = 'api';

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}
