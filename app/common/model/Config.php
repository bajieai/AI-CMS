<?php
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
