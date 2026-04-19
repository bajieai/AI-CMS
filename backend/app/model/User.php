<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_users';

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
     * 时间戳格式
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 隐藏字段
     */
    protected $hidden = ['password', 'delete_time'];

    /**
     * 类型转换
     */
    protected $type = [
        'status' => 'integer',
        'last_login_time' => 'timestamp',
        'last_login_ip' => 'string',
    ];

    /**
     * 角色关联
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'role_id', 'user_id');
    }

    /**
     * 文章关联
     */
    public function articles()
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    /**
     * 操作日志关联
     */
    public function operationLogs()
    {
        return $this->hasMany(OperationLog::class, 'user_id');
    }

    /**
     * AI任务关联
     */
    public function aiTasks()
    {
        return $this->hasMany(AiTask::class, 'user_id');
    }

    /**
     * 设置密码(自动加密)
     */
    public function setPasswordAttr($value): string
    {
        if (str_starts_with($value, '$2y$') || str_starts_with($value, '$2a$')) {
            return $value;
        }
        return password_hash($value, PASSWORD_BCRYPT);
    }

    /**
     * 验证密码
     */
    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * 获取用户权限列表
     */
    public function getPermissions(): array
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[$permission->id] = $permission->slug;
            }
        }
        return array_values($permissions);
    }

    /**
     * 检查是否有权限
     */
    public function hasPermission(string $permission): bool
    {
        // 超级管理员
        foreach ($this->roles as $role) {
            if ($role->slug === 'super_admin') {
                return true;
            }
        }
        
        $permissions = $this->getPermissions();
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * 检查是否是超级管理员
     */
    public function isSuperAdmin(): bool
    {
        foreach ($this->roles as $role) {
            if ($role->slug === 'super_admin') {
                return true;
            }
        }
        return false;
    }

    /**
     * 更新最后登录信息
     */
    public function updateLastLogin(string $ip): void
    {
        $this->last_login_ip = $ip;
        $this->last_login_time = time();
        $this->save();
    }

    /**
     * 获取用户基本信息
     */
    public function getBasicInfo(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'roles' => $this->roles->column('name'),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 根据用户名查找
     */
    public static function findByUsername(string $username): ?User
    {
        return self::where('username', '=', $username)->find();
    }

    /**
     * 根据邮箱查找
     */
    public static function findByEmail(string $email): ?User
    {
        return self::where('email', '=', $email)->find();
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return $this->status === self::STATUS_ENABLED ? '正常' : '禁用';
    }
}
