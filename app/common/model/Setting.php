<?php

declare(strict_types=1);

namespace app\common\model;

use think\facade\Db;

/**
 * 系统设置工具类（键值对持久化）
 *
 * 复用 i8j_config 表，不继承 Model 避免方法名冲突
 */
class Setting
{
    public static function get(string $key, string $default = ''): string
    {
        $row = Db::name('config')->where('name', $key)->find();
        return $row ? ($row['value'] ?? $default) : $default;
    }

    public static function put(string $key, string $value): void
    {
        $exists = Db::name('config')->where('name', $key)->find();
        if ($exists) {
            Db::name('config')->where('name', $key)->update(['value' => $value]);
        } else {
            Db::name('config')->insert(['name' => $key, 'value' => $value, 'group' => '']);
        }
    }
}
