<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 系统设置模型（键值对存储）
 *
 * 复用 i8j_config 表的 name/value 结构，
 * 支持点号命名空间（translate.enabled_languages）作为 key
 */
class Setting extends Model
{
    protected $name = 'config';

    protected $autoWriteTimestamp = false;

    /**
     * 获取设置值
     */
    public static function get(string $key, string $default = ''): string
    {
        $row = Db::name('config')->where('name', $key)->find();
        return $row ? ($row['value'] ?? $default) : $default;
    }

    /**
     * 设置值（存在则更新，不存在则新增）
     */
    public static function save(string $key, string $value): void
    {
        $exists = Db::name('config')->where('name', $key)->find();
        if ($exists) {
            Db::name('config')->where('name', $key)->update(['value' => $value]);
        } else {
            Db::name('config')->insert(['name' => $key, 'value' => $value, 'group' => '']);
        }
    }
}
