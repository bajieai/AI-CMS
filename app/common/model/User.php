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

namespace app\common\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    protected $name = 'user';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'role_id' => 'integer',
        'status' => 'integer',
        'last_login_time' => 'integer',
    ];

    // 隐藏字段
    protected $hidden = ['password'];

    /**
     * 获取角色文本
     */
    public function getRoleTextAttr($value, $data): string
    {
        $map = [1 => '超级管理员', 2 => '管理员', 3 => '编辑'];
        return $map[$data['role_id']] ?? '未知';
    }

    /**
     * 密码修改器（自动加密）
     */
    public function setPasswordAttr($value)
    {
        if (!empty($value) && !str_starts_with($value, '$2y$')) {
            return password_hash($value, PASSWORD_DEFAULT);
        }
        return $value;
    }
}
