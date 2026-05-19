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
 * 系统配置模型
 * 注意：Config是ThinkPHP内置类名，使用别名ConfigModel避免冲突
 */
class Config extends Model
{
    protected $name = 'config';

    // 不使用自动时间戳
    protected $autoWriteTimestamp = false;

    // 类型转换
    protected $type = [
        'sort' => 'integer',
    ];

    /**
     * 根据name获取配置值
     */
    public static function getValue(string $name, $default = null)
    {
        $config = static::where('name', $name)->find();
        return $config ? $config->value : $default;
    }

    /**
     * 根据group获取配置组
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->column('value', 'name');
    }
}
